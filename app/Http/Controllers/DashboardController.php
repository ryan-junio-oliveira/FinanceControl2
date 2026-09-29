<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Support\Fin;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $family = Fin::family();
        $mes = Fin::month();
        [$y, $m] = explode('-', $mes);
        $fid = $family->id;

        $accounts = $family->accounts()->where('active', true)->get();
        $saldo = $accounts->sum(fn ($a) => $a->balance);

        $receitasMes = Transaction::ofFamily($fid)->where('type', 'receita')
            ->where('status', 'pago')->inMonth($mes)->sum('amount');
        $despesasMes = Transaction::ofFamily($fid)->where('type', 'despesa')
            ->whereIn('status', ['pago', 'pendente'])->inMonth($mes)->sum('amount');

        $aportesMes = Transaction::ofFamily($fid)->where('type', 'aporte')
            ->where('status', 'pago')->inMonth($mes)->sum('amount');
        $patrimonio = $family->portfolios()->withSum('assets as total', 'current_value')->get()->sum('total');

        // Fluxo semanal
        $fluxo = [];
        foreach (Fin::weeksOfMonth($mes) as [$rotulo, $d1, $d2]) {
            $base = Transaction::ofFamily($fid)->where('status', 'pago')
                ->whereYear('occurred_on', $y)->whereMonth('occurred_on', $m)
                ->whereDay('occurred_on', '>=', $d1)->whereDay('occurred_on', '<=', $d2);
            $fluxo[] = [
                'rotulo' => $rotulo,
                'receitas' => (float) (clone $base)->where('type', 'receita')->sum('amount'),
                'despesas' => (float) (clone $base)->where('type', 'despesa')->sum('amount'),
            ];
        }
        $maxFluxo = max(1, ...array_map(fn ($w) => max($w['receitas'], $w['despesas']), $fluxo));

        // Gastos por membro
        $porMembro = Transaction::ofFamily($fid)->where('type', 'despesa')
            ->whereIn('status', ['pago', 'pendente'])->inMonth($mes)
            ->selectRaw('user_id, SUM(amount) as total')->groupBy('user_id')
            ->orderByDesc('total')->with('member')->get();
        $totalMembros = max(1, (float) $porMembro->sum('total'));

        // Orçamento por categoria (top 5 por gasto)
        $cats = $family->categories()->where('type', 'despesa')->where('archived', false)->get();
        $orcamento = $cats->map(fn ($c) => [
            'nome' => $c->name, 'gasto' => $gasto = $c->spentInMonth($mes),
            'teto' => $c->monthly_cap, 'pct' => $c->monthly_cap > 0 ? round($gasto / (float) $c->monthly_cap * 100, 1) : 0,
        ])->sortByDesc('gasto')->take(5)->values();

        // Cartões resumo
        $cartoes = $family->creditCards()->where('active', true)->with('holder')->get();

        // Próximos vencimentos
        $vencimentos = Transaction::ofFamily($fid)->where('status', 'pendente')
            ->whereNotNull('due_on')->whereDate('due_on', '>=', Carbon::today())
            ->orderBy('due_on')->take(3)->with(['member', 'category'])->get();

        // Últimas transações
        $recent = Transaction::ofFamily($fid)->with(['member', 'category'])
            ->orderByDesc('occurred_on')->orderByDesc('id')->take(6)->get();

        return view('pages.dashboard', compact(
            'mes', 'saldo', 'receitasMes', 'despesasMes', 'aportesMes', 'patrimonio',
            'fluxo', 'maxFluxo', 'porMembro', 'totalMembros', 'orcamento',
            'cartoes', 'vencimentos', 'recent', 'accounts'
        ));
    }
}
