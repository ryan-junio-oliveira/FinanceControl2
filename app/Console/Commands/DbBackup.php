<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Backup lógico do MySQL: mysqldump --single-transaction + gzip + manifesto.
 *
 * - Não trava escritas (InnoDB), seguro no horário de pico moderado.
 * - Retenção GFS: diários + semanais (dom) + mensais (dia 1).
 * - Agendado em routes/console.php (03:00). Manual: `php artisan db:backup`.
 */
class DbBackup extends Command
{
    protected $signature = 'db:backup
        {--keep-daily= : sobrescreve BACKUP_KEEP_DAILY}
        {--keep-weekly= : sobrescreve BACKUP_KEEP_WEEKLY}
        {--keep-monthly= : sobrescreve BACKUP_KEEP_MONTHLY}
        {--no-verify : pula a verificação gzip do dump}';

    protected $description = 'Gera backup gzip do banco MySQL com manifesto e retenção';

    public function handle(): int
    {
        $driver = (string) config('database.default');
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->warn("Backup suportado apenas para MySQL/MariaDB (atual: {$driver}). Nada a fazer.");

            return self::SUCCESS;
        }

        $conn = config("database.connections.{$driver}");
        $dir = (string) config('backup.path');
        File::ensureDirectoryExists($dir);

        $stamp = Carbon::now()->format('Y-m-d-His');
        $prefix = (string) config('backup.prefix');
        $file = "{$prefix}-{$stamp}.sql.gz";
        $path = rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file;
        $manifestPath = $path.'.manifest.json';

        $dump = $this->binary((string) config('backup.dump_binary'), ['mysqldump', 'mariadb-dump']);
        if ($dump === null) {
            $this->error('Nenhum binário mysqldump/mariadb-dump encontrado no PATH.');

            return self::FAILURE;
        }

        // Senha via ambiente (nunca na linha de comando → não vaza em `ps`).
        $env = ['MYSQL_PWD' => (string) ($conn['password'] ?? '')];
        $options = preg_split('/\s+/', trim((string) config('backup.dump_options'))) ?: [];
        $command = array_merge(
            [$dump, '-h', (string) ($conn['host'] ?? '127.0.0.1'), '-P', (string) ($conn['port'] ?? '3306'),
                '-u', (string) ($conn['username'] ?? 'root'), (string) $conn['database']],
            $options
        );

        $this->info("Gerando backup {$file} ...");
        $started = microtime(true);
        // Dump → gzip via pipe (sem .sql intermediário em disco).
        $shell = implode(' ', array_map('escapeshellarg', $command)).' | gzip -c > '.escapeshellarg($path);
        $process = Process::fromShellCommandline($shell, null, $env, null, 1800);
        $process->run();

        if (! $process->isSuccessful() || ! File::exists($path) || File::size($path) === 0) {
            File::delete($path);
            $this->error('Falha no dump: '.mb_substr(trim($process->getErrorOutput() ?: $process->getOutput()), 0, 500));
            Log::error('[backup] dump falhou', ['output' => $process->getErrorOutput()]);

            return self::FAILURE;
        }

        $seconds = round(microtime(true) - $started, 1);
        $size = File::size($path);
        $sha = hash_file('sha256', $path);

        if (! $this->option('no-verify') && ! $this->verify($path)) {
            File::delete([$path, $manifestPath]);
            $this->error('Verificação gzip falhou; arquivo descartado.');

            return self::FAILURE;
        }

        File::put($manifestPath, json_encode([
            'file' => $file,
            'database' => $conn['database'],
            'host' => $conn['host'] ?? null,
            'created_at' => Carbon::now()->toIso8601String(),
            'duration_s' => $seconds,
            'size_bytes' => $size,
            'sha256' => $sha,
            'dump_binary' => $dump,
            'verified' => ! $this->option('no-verify'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $pruned = $this->prune($dir, $prefix);
        $this->uploadOffsite($path, $file);
        $this->info("Backup OK: {$file} (".number_format($size / 1048576, 1)." MB, {$seconds}s, sha256 ".mb_substr($sha, 0, 12)."…). Removidos por retenção: {$pruned}.");
        Log::info('[backup] ok', ['file' => $file, 'size' => $size, 'pruned' => $pruned]);

        return self::SUCCESS;
    }

    /** gzip -t + checagem de cabeçalho SQL após gunzip parcial. */
    private function verify(string $path): bool
    {
        $t = Process::fromShellCommandline('gzip -t '.escapeshellarg($path));
        $t->run();
        if (! $t->isSuccessful()) {
            return false;
        }
        $head = Process::fromShellCommandline('gzip -dc '.escapeshellarg($path).' | head -c 4000');
        $head->run();

        return $head->isSuccessful()
            && (str_contains($head->getOutput(), 'MySQL dump') || str_contains($head->getOutput(), 'MariaDB dump'));
    }

    /**
     * Retenção GFS: mantém N diários recentes + domingos (semanais) +
     * dia 1 (mensais). Arquivos fora da política são apagados com manifesto.
     */
    private function prune(string $dir, string $prefix): int
    {
        $keepD = (int) ($this->option('keep-daily') ?: config('backup.keep_daily'));
        $keepW = (int) ($this->option('keep-weekly') ?: config('backup.keep_weekly'));
        $keepM = (int) ($this->option('keep-monthly') ?: config('backup.keep_monthly'));

        $files = collect(File::glob(rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$prefix.'-*.sql.gz') ?: [])
            ->map(fn ($p) => basename((string) $p))
            ->filter(fn ($f) => (bool) preg_match('/^'.preg_quote($prefix, '/').'-(\d{4})-(\d{2})-(\d{2})-(\d{6})\.sql\.gz$/', $f, $m) ? $this->parseDate($f) !== null : false)
            ->sort()->values();

        $dates = $files->mapWithKeys(fn ($f) => [$f => $this->parseDate($f)]);
        $daily = $dates->sortDesc()->take(max(0, $keepD));

        $weeklies = $dates->filter(fn ($d, $f) => ! $daily->has($f) && $d->isSunday())->sortDesc()->take(max(0, $keepW));
        $monthlies = $dates->filter(fn ($d, $f) => ! $daily->has($f) && ! $weeklies->has($f) && $d->day === 1)
            ->sortDesc()->take(max(0, $keepM));

        $keep = $daily->keys()->merge($weeklies->keys())->merge($monthlies->keys());
        $removed = 0;
        foreach ($dates->keys() as $f) {
            if (! $keep->contains($f)) {
                File::delete([rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$f, rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$f.'.manifest.json']);
                $removed++;
            }
        }

        return $removed;
    }

    private function parseDate(string $file): ?Carbon
    {
        if (! preg_match('/-(\d{4})-(\d{2})-(\d{2})-(\d{6})\.sql\.gz$/', $file, $m)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d-His', "{$m[1]}-{$m[2]}-{$m[3]}-{$m[4]}");
        } catch (\Throwable) {
            return null;
        }
    }

    /** Upload offsite opcional (S3/MinIO). Falha nunca quebra o backup local. */
    private function uploadOffsite(string $localPath, string $file): void
    {
        $disk = config('backup.s3_disk');
        if (! $disk) {
            return;
        }
        try {
            Storage::disk($disk)->putFileAs((string) config('backup.s3_prefix'), new \Illuminate\Http\File($localPath), $file);
            Storage::disk($disk)->put((string) config('backup.s3_prefix').$file.'.manifest.json', File::get($localPath.'.manifest.json'));
            $this->info("Offsite S3 OK ({$disk}).");
        } catch (\Throwable $e) {
            $this->warn('Offsite S3 falhou (backup local preservado): '.$e->getMessage());
            Log::warning('[backup] offsite falhou', ['error' => $e->getMessage()]);
        }
    }

    /** Primeiro binário disponível no PATH. */
    private function binary(string $configured, array $candidates): ?string
    {
        if ($configured !== 'auto') {
            return $configured;
        }
        foreach ($candidates as $bin) {
            $which = Process::fromShellCommandline('command -v '.escapeshellarg($bin));
            $which->run();
            if ($which->isSuccessful()) {
                return $bin;
            }
        }

        return null;
    }
}
