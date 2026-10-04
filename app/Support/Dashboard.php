<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Asset;
use App\Models\CardTransaction;
use App\Models\Category;
use App\Models\Contribution;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Agregações da dashboard (KPIs + configs dos gráficos ApexCharts).
 *
 * Tudo é calculado por grupo, respeitando o mês de referência
 * (?mes=YYYY-MM), em poucas queries (sem N+1).
 */
final class Dashboard
{
    private const PALETTE = [
        '#059669', '#3B82F6', '#F59E0B', '#EC4899', '#8B5CF6', '#06B6D4',
        '#F97316', '#10B981', '#6366F1', '#EF4444', '#14B8A6', '#D946EF',
    ];

    /** Tons quentes (vermelho/laranja/âmbar) p/ despesas. */
    private const WARM = ['#EF4444', '#F97316', '#F59E0B', '#DC2626', '#EA580C', '#FB7185', '#FACC15', '#B91C1C'];

    /** Tons frios (verdes/azuis) p/ receitas. */
    private const COOL = ['#059669', '#10B981', '#22C55E', '#0D9488', '#14B8A6', '#34D399', '#16A34A', '#2DD4BF'];

    public static function data(string $mes): array
    {
        $group = Fin::group();
        $fid = $group->id;
        [$y, $m] = array_map('intval', explode('-', $mes));

        // Cache curto: mês corrente expira rápido, mês passado pode durar mais.
        $isCorrente = $mes === Carbon::now()->format('Y-m');
        $ttl = $isCorrente ? 300 : 3600;

        return Cache::remember("dash:{$fid}:{$mes}:v1", $ttl, function () use ($group, $fid, $mes, $y, $m) {
            return self::compute($group, $fid, $mes, $y, $m);
        });
    }

    /** Invalida todo o cache de dashboard do grupo (chamar nos observers). */
    public static function forgetGroup(int $groupId): void
    {
        // Varre o ano corrente + anterior (chaves mensais conhecidas); barato e sem tags.
        $now = Carbon::now();
        for ($k = 0; $k < 24; $k++) {
            $mes = $now->copy()->subMonths($k)->format('Y-m');
            Cache::forget("dash:{$groupId}:{$mes}:v1");
        }
    }

    private static function compute($group, int $fid, string $mes, int $y, int $m): array
    {

        $hoje = Carbon::today();
        $mesAtual = Carbon::create($y, $m, 1);
        $isMesCorrente = $mes === Carbon::now()->format('Y-m');

        $kpi = self::kpis($fid, $mes, $y, $isMesCorrente, $mesAtual, $hoje);

        $saldos = Account::balancesForGroup($fid);
        $saldoContas = array_sum($saldos);
        $kinds = Account::where('group_id', $fid)->pluck('kind', 'id')->all();
        $saldoFisico = 0.0;
        $saldoDigital = 0.0;
        foreach ($saldos as $accountId => $v) {
            if (($kinds[$accountId] ?? '') === 'carteira') {
                $saldoFisico += $v;
            } else {
                $saldoDigital += $v;
            }
        }
        $investido = (float) Asset::where('group_id', $fid)->sum('current_value');
        $faturaAberto = (float) CardTransaction::where('group_id', $fid)->where('status', 'pendente')->sum('amount');
        $patrimonio = $saldoContas + $investido - $faturaAberto;

        $aPagar = self::aPagar($fid, $hoje);
        $cartoes = self::cartoes($group, $hoje);
        $inv = self::investimentos($fid, $mes, $y);
        $membros = self::membros($group, $mes);

        $charts = [
            'fluxo' => self::chartFluxo($fid, $y),
            'resultado' => self::chartResultado($fid, $y),
            'categorias' => self::chartCategorias($fid, $mes, 'despesa', self::WARM),
            'receitasCat' => self::chartCategorias($fid, $mes, 'receita', self::COOL),
            'saldos' => self::chartSaldos($group, $saldos),
            'alocacao' => self::chartAlocacao($fid),
            'dias' => self::chartDias($fid, $mes, $hoje, $isMesCorrente),
            'cartoes' => self::chartCartoes($group),
        ];

        return compact(
            'mes', 'y', 'm', 'isMesCorrente',
            'kpi', 'saldoContas', 'saldoFisico', 'saldoDigital', 'investido', 'faturaAberto', 'patrimonio',
            'aPagar', 'cartoes', 'inv', 'membros', 'charts',
        );
    }

    // ───────────────────────────── KPIs ─────────────────────────────

    private static function kpis(int $fid, string $mes, int $y, bool $isCorrente, Carbon $mesAtual, Carbon $hoje): array
    {
        return [
            'receitas_mes' => self::soma($fid, 'receita', $mes),
            'despesas_cash_mes' => self::soma($fid, 'despesa', $mes, null, null, null, 'cash'),
            'despesas_total_mes' => self::soma($fid, 'despesa', $mes),
            'faturas_mes' => self::soma($fid, 'despesa', $mes, null, null, null, 'invoice'),
            'acumulado' => self::soma($fid, 'receita', null, 'pago', $y)
                - self::soma($fid, 'despesa', null, 'pago', $y),
        ];
    }

    private static function aPagar(int $fid, Carbon $hoje): array
    {
        $base = Transaction::where('group_id', $fid)
            ->where('type', 'despesa')->where('status', 'pendente')->whereNotNull('due_on');

        $agregar = function ($q) {
            $row = (clone $q)->selectRaw('COUNT(*) as qtd, COALESCE(SUM(amount), 0) as valor')->first();

            return ['qtd' => (int) $row->qtd, 'valor' => (float) $row->valor];
        };

        $ranges = [7, 15, 30];
        $out = [];
        foreach ($ranges as $d) {
            $q = (clone $base)->whereBetween('due_on', [$hoje->toDateString(), $hoje->copy()->addDays($d)->toDateString()]);
            $out["s{$d}"] = $agregar($q);
        }
        $out['atraso'] = $agregar((clone $base)->whereDate('due_on', '<', $hoje->toDateString()));

        return $out;
    }

    private static function cartoes($group, Carbon $hoje): array
    {
        $cartoes = $group->creditCards()->where('active', true)->get();
        if ($cartoes->isEmpty()) {
            return ['fatura_atual' => 0.0, 'proxima' => 0.0, 'aberto' => 0.0, 'limite' => 0.0, 'disponivel' => 0.0, 'utilizacao' => 0, 'lista' => []];
        }

        $ranges = [];
        foreach ($cartoes as $c) {
            [$ini, $fim] = $c->currentInvoiceRange($hoje);
            $ranges[$c->id] = [$ini->toDateString(), $fim->toDateString()];
        }
        $fid = $group->id;
        $abertoMap = CardTransaction::where('group_id', $fid)->where('status', 'pendente')
            ->groupBy('credit_card_id')->selectRaw('credit_card_id, SUM(amount) as total')->pluck('total', 'credit_card_id');
        $atualMap = CardTransaction::where('group_id', $fid)->where('status', 'pendente')
            ->where(function ($q) use ($ranges) {
                foreach ($ranges as $cardId => [$ini, $fim]) {
                    $q->orWhere(fn ($qq) => $qq->where('credit_card_id', $cardId)->whereBetween('occurred_on', [$ini, $fim]));
                }
            })->groupBy('credit_card_id')->selectRaw('credit_card_id, SUM(amount) as total')->pluck('total', 'credit_card_id');
        $proxMap = CardTransaction::where('group_id', $fid)->where('status', 'pendente')
            ->where(function ($q) use ($ranges) {
                foreach ($ranges as $cardId => [$ini, $fim]) {
                    $q->orWhere(fn ($qq) => $qq->where('credit_card_id', $cardId)->whereDate('occurred_on', '>', $fim));
                }
            })->groupBy('credit_card_id')->selectRaw('credit_card_id, SUM(amount) as total')->pluck('total', 'credit_card_id');

        $faturaAtual = 0.0;
        $proxima = 0.0;
        $aberto = 0.0;
        $limite = 0.0;
        $lista = [];

        foreach ($cartoes as $c) {
            $open = (float) ($abertoMap[$c->id] ?? 0);
            $atual = (float) ($atualMap[$c->id] ?? 0);
            $prox = (float) ($proxMap[$c->id] ?? 0);
            $faturaAtual += $atual;
            $proxima += $prox;
            $aberto += $open;
            $limite += (float) $c->credit_limit;

            $lista[] = [
                'nome' => $c->name,
                'bandeira' => $c->brand_label ?? '—',
                'aberto' => $open,
                'limite' => (float) $c->credit_limit,
                'cor' => $c->display_color,
                'vencimento' => $c->nextDueDate($hoje)->format('d/m/Y'),
                'dias' => $hoje->diffInDays($c->nextDueDate($hoje)->startOfDay(), false),
            ];
        }

        usort($lista, fn ($a, $b) => $a['dias'] <=> $b['dias']);

        return [
            'fatura_atual' => round($faturaAtual, 2),
            'proxima' => round($proxima, 2),
            'aberto' => round($aberto, 2),
            'limite' => $limite,
            'disponivel' => max(0, $limite - $aberto),
            'utilizacao' => $limite > 0 ? round($aberto / $limite * 100, 1) : 0,
            'lista' => $lista,
        ];
    }

    private static function investimentos(int $fid, string $mes, int $y): array
    {
        [$ano, $m] = array_map('intval', explode('-', $mes));

        $mesQuery = fn ($q) => $q->whereYear('occurred_on', $ano)->whereMonth('occurred_on', $m);

        $aportesMes = (float) $mesQuery(Contribution::where('group_id', $fid)->where('kind', 'aporte'))->sum('amount');
        $rendMes = (float) $mesQuery(Contribution::where('group_id', $fid)->where('kind', 'rendimento'))->sum('amount');
        $aportesAno = (float) Contribution::where('group_id', $fid)->where('kind', 'aporte')->whereYear('occurred_on', $y)->sum('amount');

        return compact('aportesMes', 'rendMes', 'aportesAno');
    }

    /** Detalhe por membro: receitas, despesas e gastos no cartão do mês. */
    private static function membros($group, string $mes): array
    {
        [$ano, $m] = array_map('intval', explode('-', $mes));
        $noMes = fn ($q) => $q->whereYear('occurred_on', $ano)->whereMonth('occurred_on', $m);

        $rec = $noMes(Transaction::where('group_id', $group->id)
            ->where('type', 'receita')->whereIn('status', ['pago', 'pendente']))
            ->groupBy('user_id')->selectRaw('user_id, SUM(amount) as total')
            ->pluck('total', 'user_id');
        $des = $noMes(Transaction::where('group_id', $group->id)
            ->where('type', 'despesa')->whereIn('status', ['pago', 'pendente']))
            ->groupBy('user_id')->selectRaw('user_id, SUM(amount) as total')
            ->pluck('total', 'user_id');
        $cartao = $noMes(CardTransaction::where('group_id', $group->id)->where('status', 'pendente'))
            ->groupBy('user_id')->selectRaw('user_id, SUM(amount) as total')
            ->pluck('total', 'user_id');

        $maxDes = $des->max() ?: 0;

        return $group->users()->orderBy('name')->get()->map(function ($u) use ($rec, $des, $cartao, $maxDes) {
            $despesa = (float) ($des[$u->id] ?? 0);

            return [
                'nome' => $u->name,
                'iniciais' => $u->initials(),
                'cor' => $u->avatarColor(),
                'receitas' => round((float) ($rec[$u->id] ?? 0), 2),
                'despesas' => round($despesa, 2),
                'cartao' => round((float) ($cartao[$u->id] ?? 0), 2),
                'pct' => $maxDes > 0 ? round($despesa / $maxDes * 100) : 0,
            ];
        })->sortByDesc('despesas')->values()->all();
    }

    // ──────────────────────────── Gráficos ────────────────────────────

    /** Totais mensais pagos por tipo em 1 query (usado por fluxo + resultado). */
    private static function monthlyTotals(int $fid, int $y): array
    {
        $rows = Transaction::where('group_id', $fid)->where('status', 'pago')
            ->whereYear('occurred_on', $y)
            ->groupBy('type', 'mes')
            ->selectRaw('type, '.self::monthExpr().' as mes, SUM(amount) as total')
            ->get();
        $map = ['receita' => array_fill(1, 12, 0.0), 'despesa' => array_fill(1, 12, 0.0)];
        foreach ($rows as $r) {
            $map[$r->type][(int) $r->mes] = round((float) $r->total, 2);
        }

        return $map;
    }

    private static function monthExpr(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', occurred_on) AS INTEGER)"
            : 'MONTH(occurred_on)';
    }

    private static function chartFluxo(int $fid, int $y): array
    {
        $totals = self::monthlyTotals($fid, $y);
        $labels = [];
        $rec = [];
        $des = [];
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = Carbon::create($y, $i, 1)->translatedFormat('M');
            $rec[] = $totals['receita'][$i];
            $des[] = $totals['despesa'][$i];
        }

        return [
            'type' => 'area',
            'labels' => $labels,
            'series' => [
                ['name' => 'Receitas', 'data' => $rec],
                ['name' => 'Despesas', 'data' => $des],
            ],
            'colors' => ['#059669', '#EF4444'],
            'yformat' => 'money',
        ];
    }

    private static function chartResultado(int $fid, int $y): array
    {
        $totals = self::monthlyTotals($fid, $y);
        $labels = [];
        $result = [];
        $acumulado = [];
        $acc = 0;
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = Carbon::create($y, $i, 1)->translatedFormat('M');
            $r = $totals['receita'][$i] - $totals['despesa'][$i];
            $result[] = round($r, 2);
            $acc += $r;
            $acumulado[] = round($acc, 2);
        }

        return [
            'type' => 'bar',
            'labels' => $labels,
            'series' => [
                ['name' => 'Resultado', 'data' => $result, 'type' => 'bar'],
                ['name' => 'Acumulado', 'data' => $acumulado, 'type' => 'line'],
            ],
            'colors' => ['#3B82F6', '#8B5CF6'],
            'yformat' => 'money',
        ];
    }

    private static function chartCategorias(int $fid, string $mes, string $type, array $palette): array
    {
        $rows = Transaction::where('group_id', $fid)->where('type', $type)
            ->whereIn('status', ['pago', 'pendente'])->inMonth($mes)->whereNotNull('category_id')
            ->groupBy('category_id')->selectRaw('category_id, SUM(amount) as total')
            ->orderByDesc('total')->get();

        if ($rows->isEmpty()) {
            return ['type' => 'donut', 'labels' => [], 'series' => [['name' => 'total', 'data' => []]], 'colors' => [], 'yformat' => 'money'];
        }

        $categorias = Category::whereIn('id', $rows->pluck('category_id'))->get()->keyBy('id');

        $main = $rows->take(8);
        $outros = (float) $rows->skip(8)->sum('total');

        $labels = $main->map(fn ($r) => $categorias[$r->category_id]?->name ?? 'Sem categoria')->values()->all();
        $data = $main->map(fn ($r) => round((float) $r->total, 2))->values()->all();

        if ($outros > 0) {
            $labels[] = 'Outros';
            $data[] = round($outros, 2);
        }

        return [
            'type' => 'donut',
            'labels' => $labels,
            'series' => [['name' => 'total', 'data' => $data]],
            'colors' => array_slice($palette, 0, count($labels)),
            'yformat' => 'money',
        ];
    }

    private static function chartSaldos($group, array $saldos): array
    {
        $contas = $group->accounts()->with('bank')->whereIn('id', array_keys(array_filter($saldos)))->get()->keyBy('id');

        $positivas = collect($saldos)->filter(fn ($v) => $v > 0)->sortByDesc(fn ($v) => $v);

        return [
            'type' => 'donut',
            'labels' => $positivas->keys()->map(fn ($id) => $contas[$id]->name ?? "Conta #{$id}")->values()->all(),
            'series' => [['name' => 'total', 'data' => $positivas->map(fn ($v) => round($v, 2))->values()->all()]],
            'colors' => $positivas->keys()->map(fn ($id) => $contas[$id]?->bank?->color ?? '#64748b')->values()->all(),
            'yformat' => 'money',
        ];
    }

    private static function chartAlocacao(int $fid): array
    {
        $rows = Asset::where('group_id', $fid)->groupBy('kind')
            ->selectRaw('kind, SUM(current_value) as total')->orderByDesc('total')->get();

        return [
            'type' => 'donut',
            'labels' => $rows->map(fn ($r) => Asset::KINDS[$r->kind] ?? $r->kind)->values()->all(),
            'series' => [['name' => 'total', 'data' => $rows->map(fn ($r) => round((float) $r->total, 2))->values()->all()]],
            'colors' => array_slice(self::PALETTE, 0, max(1, $rows->count())),
            'yformat' => 'money',
        ];
    }

    private static function chartDias(int $fid, string $mes, Carbon $hoje, bool $isCorrente): array
    {
        [$y, $m] = array_map('intval', explode('-', $mes));
        $diasTotal = Carbon::create($y, $m, 1)->daysInMonth;
        $ate = $isCorrente ? min($diasTotal, $hoje->day) : $diasTotal;

        $rows = Transaction::where('group_id', $fid)->where('type', 'despesa')->where('status', 'pago')
            ->whereYear('occurred_on', $y)->whereMonth('occurred_on', $m)
            ->groupBy('day')->selectRaw(self::dayExpr().' as day, SUM(amount) as total')
            ->pluck('total', 'day');

        $data = [];
        for ($d = 1; $d <= $diasTotal; $d++) {
            $data[] = $d <= $ate ? round((float) ($rows[$d] ?? 0), 2) : 0;
        }

        return [
            'type' => 'bar',
            'labels' => range(1, $diasTotal),
            'series' => [['name' => 'Gastos', 'data' => $data]],
            'colors' => ['#F59E0B'],
            'yformat' => 'money',
        ];
    }

    private static function chartCartoes($group): array
    {
        $cartoes = $group->creditCards()->where('active', true)->orderBy('name')->get();

        return [
            'type' => 'bar',
            'horizontal' => true,
            'labels' => $cartoes->pluck('name')->values()->all(),
            'series' => [['name' => 'Fatura em aberto', 'data' => $cartoes->map(fn ($c) => round((float) $c->open_invoice, 2))->values()->all()]],
            'colors' => $cartoes->map(fn ($c) => $c->display_color)->values()->all(),
            'yformat' => 'money',
            'height' => 260,
        ];
    }

    // ───────────────────────────── helpers ─────────────────────────────

    /** Soma de lançamentos por tipo/status/mês/ano/fonte. */
    private static function soma(int $fid, string $type, ?string $mes, ?string $status = null, ?int $year = null, ?int $month = null, ?string $source = null): float
    {
        $q = Transaction::where('group_id', $fid)->where('type', $type);
        if ($status) {
            $q->where('status', $status);
        }
        if ($source) {
            $q->where('source', $source);
        }
        if ($mes) {
            $q->inMonth($mes);
        }
        if ($year) {
            $q->whereYear('occurred_on', $year);
        }
        if ($month) {
            $q->whereMonth('occurred_on', $month);
        }

        return round((float) $q->sum('amount'), 2);
    }

    private static function pct(float $atual, float $anterior): float
    {
        if ($anterior > 0) {
            return round(($atual - $anterior) / $anterior * 100, 1);
        }

        return $atual > 0 ? 100.0 : 0.0;
    }

    /** Extrai o dia da data de forma compatível com sqlite/mysql. */
    private static function dayExpr(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%d', occurred_on) AS INTEGER)"
            : 'DAY(occurred_on)';
    }
}
