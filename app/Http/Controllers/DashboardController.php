<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
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
        $fid = $family->id;

        $accounts = $family->accounts()->where('active', true)->get();
        $balances = Account::balancesForFamily($fid);
        $accounts->each(fn ($a) => $a->setAttribute('computed_balance', $balances[$a->id] ?? (float) $a->initial_balance));
        $saldo = $accounts->sum('computed_balance');

        // Regime caixa: só pago entra no resultado. Pendente é expectativa.
        $receitasMes = (float) Transaction::ofFamily($fid)->where('type', 'receita')
            ->where('status', 'pago')->inMonth($mes)->sum('amount');
        $despesasPagas = (float) Transaction::ofFamily($fid)->where('type', 'despesa')
            ->where('status', 'pago')->inMonth($mes)->sum('amount');
        // Mantido para o KPI "Despesas do Mês" (caixa + comprometido).
        $despesasMes = (float) Transaction::ofFamily($fid)->where('type', 'despesa')
            ->whereIn('status', ['pago', 'pendente'])->inMonth($mes)->sum('amount');

        $aportesMes = Transaction::ofFamily($fid)->where('type', 'aporte')
            ->where('status', 'pago')->inMonth($mes)->sum('amount');
        $patrimonio = $family->portfolios()->withSum('assets as total', 'current_value')->get()->sum('total');

        // Fluxo semanal — 1 query + bucket em PHP (antes: 8 SUMs).
        // Compatível com SQLite/MySQL (sem DAY()).
        $weeks = Fin::weeksOfMonth($mes);
        $movs = Transaction::ofFamily($fid)->where('status', 'pago')
            ->whereIn('type', ['receita', 'despesa'])
            ->inMonth($mes)
            ->get(['type', 'occurred_on', 'amount']);
        $fluxo = [];
        foreach ($weeks as [$rotulo, $d1, $d2]) {
            $receitas = 0.0;
            $despesas = 0.0;
            foreach ($movs as $mv) {
                $dia = (int) $mv->occurred_on->format('d');
                if ($dia < $d1 || $dia > $d2) {
                    continue;
                }
                if ($mv->type === 'receita') {
                    $receitas += (float) $mv->amount;
                } else {
                    $despesas += (float) $mv->amount;
                }
            }
            $fluxo[] = ['rotulo' => $rotulo, 'receitas' => $receitas, 'despesas' => $despesas];
        }
        $maxFluxo = max(1, ...array_map(fn ($w) => max($w['receitas'], $w['despesas']), $fluxo));

        // ── Saúde financeira: resultado, poupança, ticket, comparativo ──
        $resultado = $receitasMes - $despesasPagas;
        $taxaPoupanca = $receitasMes > 0 ? round($resultado / $receitasMes * 100, 1) : 0;

        $qtdDespesas = Transaction::ofFamily($fid)->where('type', 'despesa')
            ->where('status', 'pago')->inMonth($mes)->count();
        $ticketMedio = $qtdDespesas > 0 ? $despesasPagas / $qtdDespesas : 0;

        $mesCarbon = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
        $mesAnt = $mesCarbon->copy()->subMonth()->format('Y-m');
        $receitasAnt = (float) Transaction::ofFamily($fid)->where('type', 'receita')
            ->where('status', 'pago')->inMonth($mesAnt)->sum('amount');
        $despesasAnt = (float) Transaction::ofFamily($fid)->where('type', 'despesa')
            ->where('status', 'pago')->inMonth($mesAnt)->sum('amount');
        $varReceitas = $receitasAnt > 0 ? round(($receitasMes - $receitasAnt) / $receitasAnt * 100, 1) : null;
        $varDespesas = $despesasAnt > 0 ? round(($despesasPagas - $despesasAnt) / $despesasAnt * 100, 1) : null;

        // ── Evolução 6 meses (regime caixa) — 1 query + bucket em PHP ──
        $mesesEvo = [];
        for ($i = 5; $i >= 0; $i--) {
            $mesesEvo[] = $mesCarbon->copy()->subMonths($i)->format('Y-m');
        }
        $evoRows = Transaction::ofFamily($fid)->whereIn('type', ['receita', 'despesa'])
            ->where('status', 'pago')
            ->whereDate('occurred_on', '>=', $mesesEvo[0].'-01')
            ->get(['type', 'occurred_on', 'amount']);
        $evoReceitas = [];
        $evoDespesas = [];
        $evoResultado = [];
        $evoLabels = [];
        foreach ($mesesEvo as $mm) {
            $r = 0.0;
            $d = 0.0;
            foreach ($evoRows as $row) {
                if ($row->occurred_on->format('Y-m') !== $mm) {
                    continue;
                }
                if ($row->type === 'receita') {
                    $r += (float) $row->amount;
                } else {
                    $d += (float) $row->amount;
                }
            }
            $evoReceitas[] = round($r, 2);
            $evoDespesas[] = round($d, 2);
            $evoResultado[] = round($r - $d, 2);
            $evoLabels[] = ucfirst(Carbon::createFromFormat('Y-m', $mm)->locale('pt_BR')->isoFormat('MMM'));
        }
        $mediaDespesas3m = round(array_sum(array_slice($evoDespesas, -3)) / 3, 2);

        // ── Despesas do mês por categoria (doughnut) ──
        $doughRows = Transaction::ofFamily($fid)->where('type', 'despesa')
            ->whereIn('status', ['pago', 'pendente'])->inMonth($mes)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')->orderByDesc('total')->get();
        $catNames = Category::where('family_id', $fid)->whereIn('id', $doughRows->pluck('category_id')->filter()->all())
            ->pluck('name', 'id');
        $doughLabels = [];
        $doughValues = [];
        $outros = 0.0;
        foreach ($doughRows as $idx => $row) {
            $v = round((float) $row->total, 2);
            if ($idx < 6) {
                $doughLabels[] = $catNames[$row->category_id] ?? 'Sem categoria';
                $doughValues[] = $v;
            } else {
                $outros += $v;
            }
        }
        if ($outros > 0) {
            $doughLabels[] = 'Outras';
            $doughValues[] = round($outros, 2);
        }
        $totalDough = max(1, array_sum($doughValues));
        $topShare = $totalDough > 0 && ! empty($doughValues) ? round(max($doughValues) / $totalDough * 100, 1) : 0;
        $topCategoria = ! empty($doughValues) ? $doughLabels[array_search(max($doughValues), $doughValues)] : null;

        // ── Pendências vencidas (toda a conta) ──
        $venc = Transaction::ofFamily($fid)->where('type', 'despesa')->where('status', 'pendente')
            ->whereDate('due_on', '<', Fin::today()->toDateString())
            ->selectRaw('COUNT(*) as n, COALESCE(SUM(amount), 0) as total')->first();
        $vencidasQtd = (int) ($venc->n ?? 0);
        $vencidasTotal = (float) ($venc->total ?? 0);

        // ── Faturas vs limite ──
        $cards = $family->creditCards()->where('active', true)
            ->withSum(['items as fat' => fn ($q) => $q->where('status', 'pendente')], 'amount')->get();
        $fatTotal = (float) $cards->sum('fat');
        $limTotal = (float) $cards->sum('credit_limit');
        $usoFatura = $limTotal > 0 ? round($fatTotal / $limTotal * 100, 1) : 0;

        // ── Reserva em meses de despesa ──
        $reservaTotal = (float) $family->portfolios()->where('kind', 'reserva')
            ->withSum('assets as total', 'current_value')->get()->sum('total');
        $reservaMeses = $mediaDespesas3m > 0 ? round($reservaTotal / $mediaDespesas3m, 1) : null;

        // ── Score de saúde (0-100) + insights automáticos ──
        $score = 100;
        if ($resultado < 0) {
            $score -= 30;
        } elseif ($taxaPoupanca < 10) {
            $score -= 10;
        } elseif ($taxaPoupanca < 20) {
            $score -= 5;
        }
        if ($varDespesas !== null && $varDespesas > 20) {
            $score -= 10;
        } elseif ($varDespesas !== null && $varDespesas > 10) {
            $score -= 5;
        }
        if ($vencidasTotal > 0) {
            $score -= 15;
        }
        if ($usoFatura > 90) {
            $score -= 10;
        } elseif ($usoFatura > 70) {
            $score -= 5;
        }
        if ($topShare > 50) {
            $score -= 5;
        }
        $score = max(0, min(100, $score));
        $scoreLabel = $score >= 80 ? 'Excelente' : ($score >= 60 ? 'Saudável' : ($score >= 40 ? 'Atenção' : 'Crítica'));
        $scoreType = $score >= 80 ? 'success' : ($score >= 60 ? 'info' : ($score >= 40 ? 'warning' : 'critical'));

        $insights = [];
        $insights[] = $resultado >= 0
            ? ['type' => 'success', 'icon' => 'trending_up', 'title' => 'Superávit de '.Fin::money($resultado),
                'text' => "Taxa de poupança de {$taxaPoupanca}% das receitas. ".($taxaPoupanca >= 20 ? 'Patamar excelente — considere investir o excedente.' : 'Tente poupar ao menos 20% das receitas.')]
            : ['type' => 'critical', 'icon' => 'trending_down', 'title' => 'Déficit de '.Fin::money(abs($resultado)),
                'text' => 'As despesas pagas superaram as receitas. Revise os maiores gastos do mês.'];
        if ($varDespesas !== null) {
            $up = $varDespesas > 0;
            $insights[] = ['type' => $varDespesas > 15 ? 'warning' : ($up ? 'info' : 'success'), 'icon' => $up ? 'north' : 'south',
                'title' => 'Despesas '.($up ? 'subiram' : 'caíram').' '.abs($varDespesas).'% vs mês anterior',
                'text' => $varDespesas > 15 ? 'Alta relevante — identifique o que puxou a alta.' : 'Evolução sob controle.'];
        }
        if ($varReceitas !== null) {
            $up = $varReceitas >= 0;
            $insights[] = ['type' => $up ? 'success' : 'warning', 'icon' => $up ? 'north' : 'south',
                'title' => 'Receitas '.($up ? 'subiram' : 'caíram').' '.abs($varReceitas).'% vs mês anterior',
                'text' => $up ? 'Bom momento para reforçar a reserva.' : 'Queda de receita exige conter despesas variáveis.'];
        }
        if ($topCategoria && $topShare >= 30) {
            $insights[] = ['type' => $topShare >= 45 ? 'warning' : 'info', 'icon' => 'pie_chart',
                'title' => "{$topCategoria} concentra {$topShare}% das despesas",
                'text' => $topShare >= 45 ? 'Concentração alta — avalie cortes nessa categoria.' : 'Categoria dominante do mês.'];
        }
        if ($vencidasQtd > 0) {
            $insights[] = ['type' => 'critical', 'icon' => 'event_busy',
                'title' => Fin::money($vencidasTotal)." em {$vencidasQtd} conta(s) vencida(s)",
                'text' => 'Liquide as pendências para evitar juros e multas.'];
        }
        if ($limTotal > 0 && $usoFatura > 50) {
            $insights[] = ['type' => $usoFatura > 70 ? 'warning' : 'info', 'icon' => 'credit_card',
                'title' => "Faturas usam {$usoFatura}% do limite",
                'text' => $usoFatura > 70 ? 'Uso alto — evite novas compras no cartão.' : 'Uso moderado do crédito.'];
        }
        if ($reservaMeses !== null) {
            $insights[] = ['type' => $reservaMeses >= 6 ? 'success' : ($reservaMeses >= 3 ? 'warning' : 'critical'), 'icon' => 'shield_with_heart',
                'title' => "Reserva cobre {$reservaMeses} meses de despesas",
                'text' => $reservaMeses >= 6 ? 'Colchão saudável (meta: 6 meses).' : 'Meta ideal: 6 meses de despesas na reserva.'];
        } elseif ($reservaTotal <= 0) {
            $insights[] = ['type' => 'info', 'icon' => 'savings',
                'title' => 'Sem reserva de emergência',
                'text' => 'Comece uma carteira do tipo reserva, mesmo com aportes pequenos.'];
        }
        $insights[] = ['type' => 'info', 'icon' => 'receipt_long',
            'title' => "{$qtdDespesas} despesas pagas · ticket médio ".Fin::money($ticketMedio),
            'text' => 'Média por lançamento no mês.'];

        return view('pages.dashboard', compact(
            'mes', 'saldo', 'receitasMes', 'despesasMes', 'despesasPagas', 'aportesMes', 'patrimonio',
            'fluxo', 'maxFluxo', 'accounts',
            'resultado', 'taxaPoupanca', 'ticketMedio', 'qtdDespesas',
            'varReceitas', 'varDespesas', 'vencidasQtd', 'vencidasTotal',
            'evoLabels', 'evoReceitas', 'evoDespesas', 'evoResultado',
            'doughLabels', 'doughValues',
            'score', 'scoreLabel', 'scoreType', 'insights'
        ));
    }
}
