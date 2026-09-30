<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Models\Transaction;
use App\Notifications\FaturaVencimento;
use App\Support\Notify;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyVencimentos extends Command
{
    protected $signature = 'notify:vencimentos';

    protected $description = 'Notifica contas pendentes vencendo hoje, amanhã ou em até 3 dias';

    public function handle(): int
    {
        $total = 0;
        $today = Carbon::today();

        Family::with(['users', 'settings'])->each(function (Family $family) use ($today, &$total) {
            if (! Notify::enabled($family, 'fatura_vencimento')) {
                return;
            }

            $limite = $today->copy()->addDays(3)->toDateString();
            $pendentes = Transaction::ofFamily($family->id)
                ->where('type', 'despesa')->where('status', 'pendente')
                ->whereNotNull('due_on')->whereDate('due_on', '<=', $limite)
                ->with('member')->orderBy('due_on')->get();

            foreach ($pendentes as $t) {
                $dias = $today->diffInDays(Carbon::parse($t->due_on)->startOfDay(), false);
                $notif = new FaturaVencimento(
                    $t->description, (float) $t->amount,
                    Carbon::parse($t->due_on)->format('d/m/Y'), $dias,
                );
                Notify::memberIf($t->member, $family, 'fatura_vencimento', $notif);
                foreach (Notify::gestores($family) as $gestor) {
                    if ($gestor->id !== $t->user_id) {
                        $gestor->notify($notif);
                    }
                }
                $total++;
            }
        });

        $this->info("Avisos de vencimento enviados: {$total}.");

        return self::SUCCESS;
    }
}
