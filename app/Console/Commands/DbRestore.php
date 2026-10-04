<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Restaura um backup gerado por `db:backup` (.sql.gz).
 *
 * - Confere sha256 do manifesto (se existir).
 * - Faz backup de segurança do estado atual antes de restaurar.
 * - Exige --force em produção (operação destrutiva).
 */
class DbRestore extends Command
{
    protected $signature = 'db:restore
        {file : nome do arquivo em BACKUP_PATH (ex.: prumo-db-2026-10-03-030000.sql.gz)}
        {--force : confirma restauração destrutiva (obrigatório em produção)}
        {--no-safety-backup : pula o backup de segurança pré-restore}';

    protected $description = 'Restaura backup MySQL (.sql.gz) com backup de segurança prévio';

    public function handle(): int
    {
        $driver = (string) config('database.default');
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->error("Restore suportado apenas para MySQL/MariaDB (atual: {$driver}).");

            return self::FAILURE;
        }

        $dir = rtrim((string) config('backup.path'), DIRECTORY_SEPARATOR);
        $path = $dir.DIRECTORY_SEPARATOR.basename((string) $this->argument('file'));
        if (! File::exists($path)) {
            $this->error("Arquivo não encontrado: {$path}");
            $this->line('Backups disponíveis:');
            foreach (File::glob($dir.DIRECTORY_SEPARATOR.'*.sql.gz') ?: [] as $f) {
                $this->line('  - '.basename((string) $f));
            }

            return self::FAILURE;
        }

        if (! $this->option('force') && app()->isProduction()) {
            $this->error('Restauração destrutiva: rode com --force em produção.');

            return self::FAILURE;
        }
        if (! $this->option('force') && ! $this->confirm("Restaurar {$path}? DADOS ATUAIS SERÃO SUBSTITUÍDOS.", false)) {
            $this->line('Cancelado.');

            return self::SUCCESS;
        }

        // Confere integridade contra o manifesto.
        $manifest = $path.'.manifest.json';
        if (File::exists($manifest)) {
            $expected = json_decode(File::get($manifest), true)['sha256'] ?? null;
            $actual = hash_file('sha256', $path);
            if ($expected && ! hash_equals($expected, $actual)) {
                $this->error('SHA256 divergente — arquivo possivelmente corrompido. Abortando.');

                return self::FAILURE;
            }
        }

        if (! $this->option('no-safety-backup')) {
            $this->info('Gerando backup de segurança do estado atual...');
            $code = $this->call('db:backup');
            if ($code !== self::SUCCESS) {
                $this->error('Backup de segurança falhou; restore abortado por precaução.');

                return self::FAILURE;
            }
        }

        $conn = config("database.connections.{$driver}");
        $mysql = $this->binary((string) config('backup.mysql_binary'), ['mysql', 'mariadb']);
        if ($mysql === null) {
            $this->error('Nenhum cliente mysql/mariadb encontrado no PATH.');

            return self::FAILURE;
        }

        $this->info('Restaurando... (tabelas serão substituídas)');
        $cmd = 'gzip -dc '.escapeshellarg($path).' | '.implode(' ', array_map('escapeshellarg', [
            $mysql, '-h', (string) ($conn['host'] ?? '127.0.0.1'), '-P', (string) ($conn['port'] ?? '3306'),
            '-u', (string) ($conn['username'] ?? 'root'), (string) $conn['database'],
        ]));
        $process = Process::fromShellCommandline($cmd, null, ['MYSQL_PWD' => (string) ($conn['password'] ?? '')], null, 1800);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('Restore falhou: '.mb_substr(trim($process->getErrorOutput()), 0, 500));
            Log::error('[backup] restore falhou', ['file' => basename($path)]);

            return self::FAILURE;
        }

        $this->info('Restore concluído. Rode `php artisan migrate --force` se o dump for anterior ao schema atual.');
        Log::warning('[backup] restore executado', ['file' => basename($path)]);

        return self::SUCCESS;
    }

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
