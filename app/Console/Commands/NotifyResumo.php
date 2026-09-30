<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Models\Transaction;
use App\Notifications\ResumoSemanal;
use App\Support\Notify;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyResumo extends Command
{
    protected $signature = 'notify:resumo';

    protected $description = 'Resumo semanal da família para os gestores (segundas 08:00)';

    public function handle(): int
    {
        $total = 0;
        $ini = Carbon::now()->subWeek()->startOfDay();
        $fim = Carbon::now()->endOfDay();
        $periodo = $ini->format('d/m').'–'.$fim->format('d/m');

        Family::with('settings')->each(function (Family $family) use ($ini, $fim, $periodo, &$total) {
            if (! Notify::enabled($family, 'resumo_semanal')) {
                return;
            }

            $base = Transaction::ofFamily($family->id)
                ->where('status', 'pago')
                ->whereBetween('occurred_on', [$ini->toDateString(), $fim->toDateString()]);
            $receitas = (float) (clone $base)->where('type', 'receita')->sum('amount');
            $despesas = (float) (clone $base)->where('type', 'despesa')->sum('amount');
            $venc = Transaction::ofFamily($family->id)->where('type', 'despesa')->where('status', 'pendente')
                ->whereDate('due_on', '<', Carbon::today()->toDateString())
                ->selectRaw('COUNT(*) as n, COALESCE(SUM(amount), 0) as total')->first();

            $notif = new ResumoSemanal($periodo, $receitas, $despesas, (int) ($venc->n ?? 0), (float) ($venc->total ?? 0));
            foreach (Notify::gestores($family) as $gestor) {
                $gestor->notify($notif);
                $total++;
            }
        });

        $this->info("Resumos semanais enviados: {$total}.");

        return self::SUCCESS;
    }
}
