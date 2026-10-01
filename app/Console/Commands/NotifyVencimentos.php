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

    protected $description = 'Notifica faturas de cartão e contas próximas do vencimento (hoje, amanhã ou em até 3 dias)';

    public function handle(): int
    {
        $total = 0;
        $today = Carbon::today();

        Family::with(['users', 'settings'])->each(function (Family $family) use ($today, &$total) {
            $limite = $today->copy()->addDays(3)->toDateString();

            // Contas/lançamentos pendentes próximos do pagamento.
            if (Notify::enabled($family, 'conta_vencimento')) {
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
                    Notify::memberIf($t->member, $family, 'conta_vencimento', $notif);
                    foreach (Notify::gestores($family) as $gestor) {
                        if ($gestor->id !== $t->user_id) {
                            $gestor->notify($notif);
                        }
                    }
                    $total++;
                }
            }

            // Faturas de cartão com vencimento próximo e saldo em aberto.
            if (Notify::enabled($family, 'fatura_vencimento')) {
                $cartoes = $family->creditCards()->where('active', true)->with('holder')->get();

                foreach ($cartoes as $cartao) {
                    $venc = $cartao->nextDueDate($today)->startOfDay();
                    if ($venc->toDateString() > $limite) {
                        continue;
                    }
                    $aberto = (float) $cartao->open_invoice;
                    if ($aberto <= 0) {
                        continue;
                    }
                    $dias = $today->diffInDays($venc, false);
                    $notif = new FaturaVencimento(
                        "Fatura {$cartao->name}", $aberto,
                        $venc->format('d/m/Y'), $dias,
                    );
                    if ($cartao->holder) {
                        Notify::memberIf($cartao->holder, $family, 'fatura_vencimento', $notif);
                    }
                    foreach (Notify::gestores($family) as $gestor) {
                        if (! $cartao->holder || $gestor->id !== $cartao->holder->id) {
                            $gestor->notify($notif);
                        }
                    }
                    $total++;
                }
            }
        });

        $this->info("Avisos de vencimento enviados: {$total}.");

        return self::SUCCESS;
    }
}
