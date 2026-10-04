<?php

namespace App\Jobs;

use App\Models\CardTransaction;
use App\Models\Group;
use App\Models\Transaction;
use App\Notifications\FaturaVencimento;
use App\Support\Notify;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendGroupVencimentos implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $groupId) {}

    public function handle(): int
    {
        $group = Group::with(['users', 'settings'])->find($this->groupId);
        if (! $group) {
            return 0;
        }
        $today = Carbon::today();
        $limite = $today->copy()->addDays(3)->toDateString();
        $total = 0;

        if (Notify::enabled($group, 'conta_vencimento')) {
            $pendentes = Transaction::ofGroup($group->id)
                ->where('type', 'despesa')->where('status', 'pendente')
                ->whereNotNull('due_on')->whereDate('due_on', '<=', $limite)
                ->with('member')->orderBy('due_on')->get();

            foreach ($pendentes as $t) {
                $dias = $today->diffInDays(Carbon::parse($t->due_on)->startOfDay(), false);
                $notif = new FaturaVencimento($t->description, (float) $t->amount, Carbon::parse($t->due_on)->format('d/m/Y'), $dias);
                Notify::memberIf($t->member, $group, 'conta_vencimento', $notif);
                foreach (Notify::gestores($group) as $gestor) {
                    if ($gestor->id !== $t->user_id) {
                        $gestor->notify($notif);
                    }
                }
                $total++;
            }
        }

        if (Notify::enabled($group, 'fatura_vencimento')) {
            // 1 query agregada de faturas em aberto por cartão (evita open_invoice no loop).
            $abertos = CardTransaction::where('group_id', $group->id)
                ->where('status', 'pendente')
                ->groupBy('credit_card_id')->selectRaw('credit_card_id, SUM(amount) as total')
                ->pluck('total', 'credit_card_id');
            $cartoes = $group->creditCards()->where('active', true)->with('holder')->get();

            foreach ($cartoes as $cartao) {
                $venc = $cartao->nextDueDate($today)->startOfDay();
                if ($venc->toDateString() > $limite) {
                    continue;
                }
                $aberto = (float) ($abertos[$cartao->id] ?? 0);
                if ($aberto <= 0) {
                    continue;
                }
                $dias = $today->diffInDays($venc, false);
                $notif = new FaturaVencimento("Fatura {$cartao->name}", $aberto, $venc->format('d/m/Y'), $dias);
                if ($cartao->holder) {
                    Notify::memberIf($cartao->holder, $group, 'fatura_vencimento', $notif);
                }
                foreach (Notify::gestores($group) as $gestor) {
                    if (! $cartao->holder || $gestor->id !== $cartao->holder->id) {
                        $gestor->notify($notif);
                    }
                }
                $total++;
            }
        }

        return $total;
    }
}
