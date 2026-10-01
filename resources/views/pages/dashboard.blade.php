@extends('layouts.app')
@section('title', 'Dashboard')
@section('breadcrumb', 'Visão Geral / Dashboard')

@section('content')
@php
    use App\Support\Fin;
    $money = fn($v) => Fin::money($v);
    $mesLabel = ucfirst(\Carbon\Carbon::createFromFormat('Y-m', $mes)->translatedFormat('F/Y'));
    $bottomPagar = '<span class="text-[11px] text-gray-400 font-medium">'.$aPagar['s30']['qtd'].' conta(s) · '.$aPagar['atraso']['qtd'].' vencida(s)</span>';
@endphp

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[24px] font-extrabold tracking-tight text-gray-900">Visão Geral</h1>
        <p class="text-[13px] text-gray-400 mt-0.5 font-medium">Saúde financeira da família {{ auth()->user()->family->name }}</p>
    </div>
    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
        <label class="text-[12px] font-bold text-gray-500" for="mes">Mês de referência</label>
        <input type="month" name="mes" id="mes" value="{{ $mes }}" onchange="this.form.submit()"
            class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-[13px] font-bold text-gray-700 focus:border-emerald-500 outline-none transition">
    </form>
</div>

{{-- ══════════════ O ESSENCIAL ══════════════ --}}
<div class="grid sm:grid-cols-2 xl:grid-cols-5 gap-4">
    <x-kpi-card label="Patrimônio Líquido" :value="$money($patrimonio)" icon="workspace_premium" accent="emerald" />
    <x-kpi-card label="Receitas no Mês" :value="$money($kpi['receitas']['atual'])" icon="trending_up" accent="green" />
    <x-kpi-card label="Despesas no Mês" :value="$money($kpi['despesas']['atual'])" icon="trending_down" accent="red" />
    <x-kpi-card label="Resultado do Mês" :value="$money($kpi['resultado']['atual'])" icon="balance" accent="emerald" />
    <x-kpi-card label="A Pagar (30 dias)" :value="$money($aPagar['s30']['valor'])" icon="event" accent="amber" :bottom="$bottomPagar" />
</div>

{{-- ══════════════ GRÁFICOS ══════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    <x-section-card class="lg:col-span-7" title="Receitas × Despesas" :subtitle="'Ano de '.$y">
        <div id="chart-fluxo" class="w-full"></div>
    </x-section-card>

    <x-section-card class="lg:col-span-5" title="Despesas por Categoria" :subtitle="'Em '.$mesLabel">
        <div id="chart-categorias" class="w-full"></div>
    </x-section-card>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    <x-section-card class="lg:col-span-5" title="Resultado Mensal" subtitle="Barras = resultado · linha = acumulado no ano">
        <div id="chart-resultado" class="w-full"></div>
    </x-section-card>

    <x-section-card class="lg:col-span-7" title="Saldo por Conta" subtitle="Cores da marca de cada banco">
        <div id="chart-saldos" class="w-full"></div>
    </x-section-card>
</div>

{{-- ══════════════ LISTAS ══════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    <x-section-card class="lg:col-span-7" title="Próximos Vencimentos" subtitle="Contas pendentes nos próximos 30 dias">
        @forelse($proximos as $v)
        <div class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-100 hover:border-slate-200 bg-slate-50/40 mb-2.5 transition">
            <span class="w-10 h-10 rounded-lg grid place-items-center shrink-0 bg-amber-50 text-amber-500">
                <span class="material-symbols-outlined text-[19px]">event</span>
            </span>
            <div class="flex-1 min-w-0">
                <p class="text-[13px] font-bold text-gray-800 truncate">{{ $v['descricao'] }}</p>
                <p class="text-[11px] text-gray-400">{{ $v['categoria'] }} · vence {{ $v['vencimento'] }}</p>
            </div>
            <strong class="num text-[13px] text-gray-800 shrink-0">{{ $money($v['valor']) }}</strong>
            <x-badge :type="$v['dias'] <= 1 ? 'critical' : 'warning'">{{ $v['dias'] <= 0 ? 'Hoje' : ($v['dias'] === 1 ? 'Amanhã' : $v['dias'].' dias') }}</x-badge>
        </div>
        @empty
        <div class="text-center py-10 text-gray-400">
            <span class="material-symbols-outlined text-[44px] text-gray-300">event_available</span>
            <p class="text-[13px] font-bold mt-2 text-gray-500">Nada vencendo nos próximos 30 dias.</p>
        </div>
        @endforelse
    </x-section-card>

    <div class="lg:col-span-5 space-y-4">
        <x-section-card title="Gastos por Membro" :subtitle="'Em '.$mesLabel">
            @forelse($porMembro as $g)
            <div class="flex items-center gap-3 py-2">
                <span class="w-9 h-9 rounded-full grid place-items-center text-white text-[11px] font-extrabold shrink-0"
                    style="background:{{ $g['cor'] }}">{{ $g['iniciais'] }}</span>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-[13px] font-bold text-gray-800 truncate">{{ $g['nome'] }}</p>
                        <span class="num text-[12px] font-extrabold text-gray-700 shrink-0">{{ $money($g['total']) }}</span>
                    </div>
                    <div class="mt-1.5 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full" style="width:{{ $g['pct'] }}%;background:{{ $g['cor'] }}"></div>
                    </div>
                </div>
            </div>
            @empty
            <p class="text-[13px] text-gray-400 text-center py-8">Nenhum gasto no período.</p>
            @endforelse
        </x-section-card>

        <x-section-card title="Metas de Investimento" subtitle="Progresso até o objetivo">
            @forelse($inv['metas'] as $meta)
            <div class="py-2.5">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[13px] font-bold text-gray-800 truncate">{{ $meta['nome'] }}</p>
                    <span class="text-[11px] font-extrabold num text-gray-600 shrink-0">{{ round($meta['progresso'] ?? 0) }}%</span>
                </div>
                <div class="mt-1.5 h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-600 transition-all"
                        style="width:{{ min(100, $meta['progresso'] ?? 0) }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-[13px] text-gray-400 text-center py-8">Nenhuma meta configurada.</p>
            @endforelse
        </x-section-card>
    </div>
</div>

<script>
window.DashboardCharts = {
    'chart-fluxo': @json($charts['fluxo']),
    'chart-resultado': @json($charts['resultado']),
    'chart-categorias': @json($charts['categorias']),
    'chart-saldos': @json($charts['saldos']),
};
</script>
@endsection
