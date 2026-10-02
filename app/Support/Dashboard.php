<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Asset;
use App\Models\CardTransaction;
use App\Models\Category;
use App\Models\Contribution;
use App\Models\Portfolio;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Agregações da dashboard (KPIs + configs dos gráficos ApexCharts).
 *
 * Tudo é calculado por família, respeitando o mês de referência
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
        $family = Fin::family();
        $fid = $family->id;
        [$y, $m] = array_map('intval', explode('-', $mes));

        $hoje = Carbon::today();
        $mesAtual = Carbon::create($y, $m, 1);
        $isMesCorrente = $mes === Carbon::now()->format('Y-m');

        $kpi = self::kpis($fid, $mes, $y, $isMesCorrente, $mesAtual, $hoje);

        $saldos = Account::balancesForFamily($fid);
        $saldoContas = array_sum($saldos);
        $investido = (float) Asset::where('family_id', $fid)->sum('current_value');
        $faturaAberto = (float) CardTransaction::where('family_id', $fid)->where('status', 'pendente')->sum('amount');
        $patrimonio = $saldoContas + $investido - $faturaAberto;

        $aPagar = self::aPagar($fid, $hoje);
        $cartoes = self::cartoes($family, $hoje);
        $inv = self::investimentos($fid, $mes, $y);
        $membros = self::membros($family, $mes);
        $proximos = self::proximos($fid, $hoje);

        $charts = [
            'fluxo' => self::chartFluxo($fid, $y),
            'resultado' => self::chartResultado($fid, $y),
            'categorias' => self::chartCategorias($fid, $mes, 'despesa', self::WARM),
            'receitasCat' => self::chartCategorias($fid, $mes, 'receita', self::COOL),
            'saldos' => self::chartSaldos($family, $saldos),
            'alocacao' => self::chartAlocacao($fid),
            'dias' => self::chartDias($fid, $mes, $hoje, $isMesCorrente),
            'cartoes' => self::chartCartoes($family),
        ];

        return compact(
            'mes', 'y', 'm', 'isMesCorrente',
            'kpi', 'saldoContas', 'investido', 'faturaAberto', 'patrimonio',
            'aPagar', 'cartoes', 'inv', 'membros', 'proximos', 'charts',
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
        $base = Transaction::where('family_id', $fid)
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

    private static function cartoes($family, Carbon $hoje): array
    {
        $cartoes = $family->creditCards()->where('active', true)->get();

        $faturaAtual = 0.0;
        $proxima = 0.0;
        $aberto = 0.0;
        $limite = 0.0;
        $lista = [];

        foreach ($cartoes as $c) {
            $open = (float) $c->open_invoice;
            [$ini, $fim] = $c->currentInvoiceRange($hoje);
            $atual = (float) $c->items()->where('status', 'pendente')
                ->whereBetween('occurred_on', [$ini->toDateString(), $fim->toDateString()])->sum('amount');
            $prox = (float) $c->items()->where('status', 'pendente')
                ->whereDate('occurred_on', '>', $fim->toDateString())->sum('amount');

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
            ];
        }

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

        $aportesMes = (float) $mesQuery(Contribution::where('family_id', $fid)->where('kind', 'aporte'))->sum('amount');
        $rendMes = (float) $mesQuery(Contribution::where('family_id', $fid)->where('kind', 'rendimento'))->sum('amount');
        $aportesAno = (float) Contribution::where('family_id', $fid)->where('kind', 'aporte')->whereYear('occurred_on', $y)->sum('amount');

        $metas = Portfolio::where('family_id', $fid)->whereNotNull('target_amount')->orderBy('name')->get()
            ->map(fn ($p) => [
                'nome' => $p->name,
                'total' => $p->total,
                'meta' => (float) $p->target_amount,
                'progresso' => $p->progress,
                'restante' => $p->remaining,
                'mensal' => $p->monthly_needed,
                'prazo' => $p->deadline?->format('d/m/Y'),
            ])->all();

        $monthlyNeeded = array_sum(array_column($metas, 'mensal') ?: [0]);

        $reserva = Portfolio::where('family_id', $fid)->where('kind', 'reserva')->first();
        $reservaTotal = $reserva ? (float) $reserva->total : 0;

        // Despesa mensal média (média dos últimos 6 meses pagos).
        $ultimos = [];
        for ($i = 1; $i <= 6; $i++) {
            $ultimos[] = self::soma($fid, 'despesa', Carbon::now()->subMonthsNoOverflow($i)->format('Y-m'), 'pago');
        }
        $media = count(array_filter($ultimos)) ? round(array_sum($ultimos) / 6, 2) : 0;
        $diasReserva = $media > 0 ? round($reservaTotal / ($media / 30), 1) : 0;

        return compact('aportesMes', 'rendMes', 'aportesAno', 'metas', 'monthlyNeeded', 'reservaTotal', 'diasReserva');
    }

    /** Detalhe por membro: receitas, despesas e gastos no cartão do mês. */
    private static function membros($family, string $mes): array
    {
        [$ano, $m] = array_map('intval', explode('-', $mes));
        $noMes = fn ($q) => $q->whereYear('occurred_on', $ano)->whereMonth('occurred_on', $m);

        $rec = $noMes(Transaction::where('family_id', $family->id)
            ->where('type', 'receita')->whereIn('status', ['pago', 'pendente']))
            ->groupBy('user_id')->selectRaw('user_id, SUM(amount) as total')
            ->pluck('total', 'user_id');
        $des = $noMes(Transaction::where('family_id', $family->id)
            ->where('type', 'despesa')->whereIn('status', ['pago', 'pendente']))
            ->groupBy('user_id')->selectRaw('user_id, SUM(amount) as total')
            ->pluck('total', 'user_id');
        $cartao = $noMes(CardTransaction::where('family_id', $family->id)->where('status', 'pendente'))
            ->groupBy('user_id')->selectRaw('user_id, SUM(amount) as total')
            ->pluck('total', 'user_id');

        $maxDes = $des->max() ?: 0;

        return $family->users()->orderBy('name')->get()->map(function ($u) use ($rec, $des, $cartao, $maxDes) {
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

    private static function proximos(int $fid, Carbon $hoje): array
    {
        return Transaction::where('family_id', $fid)
            ->where('type', 'despesa')->where('status', 'pendente')->whereNotNull('due_on')
            ->whereBetween('due_on', [$hoje->toDateString(), $hoje->copy()->addDays(30)->toDateString()])
            ->with('category')->orderBy('due_on')->take(8)
            ->get()->map(fn ($t) => [
                'descricao' => $t->description,
                'categoria' => $t->category?->name ?? '—',
                'valor' => (float) $t->amount,
                'vencimento' => $t->due_on->format('d/m/Y'),
                'dias' => $hoje->diffInDays($t->due_on, false),
            ])->all();
    }

    // ──────────────────────────── Gráficos ────────────────────────────

    private static function chartFluxo(int $fid, int $y): array
    {
        $labels = [];
        $rec = [];
        $des = [];
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = Carbon::create($y, $i, 1)->translatedFormat('M');
            $rec[] = self::soma($fid, 'receita', null, 'pago', $y, $i);
            $des[] = self::soma($fid, 'despesa', null, 'pago', $y, $i);
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
        $labels = [];
        $result = [];
        $acumulado = [];
        $acc = 0;
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = Carbon::create($y, $i, 1)->translatedFormat('M');
            $r = self::soma($fid, 'receita', null, 'pago', $y, $i) - self::soma($fid, 'despesa', null, 'pago', $y, $i);
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
        $rows = Transaction::where('family_id', $fid)->where('type', $type)
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

    private static function chartSaldos($family, array $saldos): array
    {
        $contas = $family->accounts()->with('bank')->whereIn('id', array_keys(array_filter($saldos)))->get()->keyBy('id');

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
        $rows = Asset::where('family_id', $fid)->groupBy('kind')
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

        $rows = Transaction::where('family_id', $fid)->where('type', 'despesa')->where('status', 'pago')
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

    private static function chartCartoes($family): array
    {
        $cartoes = $family->creditCards()->where('active', true)->orderBy('name')->get();

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
        $q = Transaction::where('family_id', $fid)->where('type', $type);
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
