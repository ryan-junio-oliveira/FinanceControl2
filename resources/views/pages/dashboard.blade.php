@extends('layouts.app')
@section('title', 'Dashboard')
@section('breadcrumb', 'Visão Geral / Dashboard')

@section('content')
@php
    use App\Support\Fin;
    $money = fn($v) => Fin::money($v);
    $mesLabel = ucfirst(\Carbon\Carbon::createFromFormat('Y-m', $mes)->translatedFormat('F/Y'));
@endphp

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[24px] font-extrabold tracking-tight text-gray-900">Visão Geral</h1>
        <p class="text-[13px] text-gray-400 mt-0.5 font-medium">Saúde financeira da família {{ auth()->user()->family->name }}</p>
    </div>
    <x-month-picker :action="route('dashboard')" :mes="$mes" />
</div>

{{-- ══════════════ VISÃO GERAL ══════════════ --}}
<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
    <x-kpi-card label="Saldo em Contas" :value="$money($saldoContas)" icon="account_balance_wallet" accent="blue"
        bottom="<span class='text-[11px] text-gray-400 font-medium'>Digital: <b class='num text-emerald-600'>{{ $money($saldoDigital) }}</b> · Físico: <b class='num text-amber-600'>{{ $money($saldoFisico) }}</b></span>" />
    <x-kpi-card label="Receitas no Mês" :value="$money($kpi['receitas_mes'])" icon="trending_up" accent="green" />
    <x-kpi-card label="Despesas no Mês" :value="$money($kpi['despesas_total_mes'])" icon="payments" accent="red"
        bottom="<span class='text-[11px] text-gray-400 font-medium'>Sem faturas: <b class='num text-red-500'>{{ $money($kpi['despesas_cash_mes']) }}</b> · Faturas: <b class='num text-orange-500'>{{ $money($kpi['despesas_total_mes'] - $kpi['despesas_cash_mes']) }}</b> (aberto: <b class='num text-amber-600'>{{ $money($faturaAberto) }}</b>)</span>" />
    <x-kpi-card label="Investimentos" :value="$money($investido)" icon="savings" accent="cyan"
        bottom="<span class='text-[11px] text-gray-400 font-medium'>Rendimentos no mês: <b class='num {{ $inv['rendMes'] >= 0 ? 'text-emerald-600' : 'text-red-500' }}'>{{ $money($inv['rendMes']) }}</b></span>" />
</div>

{{-- ══════════════ GRÁFICOS ══════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    <x-section-card class="lg:col-span-12" title="Receitas × Despesas" :subtitle="'Ano de '.$y">
        <div id="chart-fluxo" class="w-full"></div>
    </x-section-card>

    <x-section-card class="lg:col-span-6" title="Despesas por Categoria" :subtitle="'Em '.$mesLabel">
        <div id="chart-categorias" class="w-full"></div>
    </x-section-card>

    <x-section-card class="lg:col-span-6" title="Receitas por Categoria" :subtitle="'Em '.$mesLabel">
        <div id="chart-receitas" class="w-full"></div>
    </x-section-card>

    <x-section-card class="lg:col-span-6" title="Investimentos por Categoria" :subtitle="'Distribuição do valor investido por classe'">
        <div id="chart-alocacao" class="w-full"></div>
    </x-section-card>

    <x-section-card class="lg:col-span-6" title="Faturas a Vencer" subtitle="Faturas dos cartões nos próximos 15 dias">
        @forelse(collect($cartoes['lista'])->filter(fn($c) => $c['aberto'] > 0 && $c['dias'] <= 15) as $c)
        <div class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-100 hover:border-slate-200 bg-slate-50/40 mb-2.5 transition">
            <span class="w-10 h-10 rounded-lg grid place-items-center shrink-0 text-white" style="background:{{ $c['cor'] }}">
                <span class="material-symbols-outlined text-[18px]">credit_card</span>
            </span>
            <div class="flex-1 min-w-0">
                <p class="text-[13px] font-bold text-gray-800 truncate">{{ $c['nome'] }}</p>
                <p class="text-[11px] text-gray-400">{{ $c['bandeira'] }} · vence {{ $c['vencimento'] }}</p>
            </div>
            <strong class="num text-[13px] text-gray-800 shrink-0">{{ $money($c['aberto']) }}</strong>
            <x-badge :type="$c['dias'] <= 1 ? 'critical' : 'warning'">{{ $c['dias'] <= 0 ? 'Hoje' : ($c['dias'] === 1 ? 'Amanhã' : $c['dias'].' dias') }}</x-badge>
        </div>
        @empty
        <div class="text-center py-10 text-gray-400">
            <span class="material-symbols-outlined text-[44px] text-gray-300">credit_card_off</span>
            <p class="text-[13px] font-bold mt-2 text-gray-500">Nenhuma fatura vencendo nos próximos 15 dias.</p>
        </div>
        @endforelse
    </x-section-card>
</div>

{{-- ══════════════ POR MEMBRO ══════════════ --}}
<x-section-card title="Detalhes por Membro" :subtitle="'Receitas, despesas e gastos no cartão em '.$mesLabel">
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($membros as $u)
        <div class="rounded-xl border border-slate-200 p-4">
            <div class="flex items-center gap-3 mb-3">
                <span class="w-10 h-10 rounded-full grid place-items-center text-white text-[12px] font-extrabold shrink-0"
                    style="background:{{ $u['cor'] }}">{{ $u['iniciais'] }}</span>
                <div class="min-w-0">
                    <p class="text-[14px] font-extrabold text-gray-800 truncate">{{ $u['nome'] }}</p>
                    <p class="text-[11px] text-gray-400">{{ $mesLabel }}</p>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2 text-[12px]">
                <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-2.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Receitas</p>
                    <p class="num font-extrabold text-[13px] text-emerald-700 mt-0.5">{{ $money($u['receitas']) }}</p>
                </div>
                <div class="rounded-xl bg-red-50 border border-red-100 p-2.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-red-500">Despesas</p>
                    <p class="num font-extrabold text-[13px] text-red-700 mt-0.5">{{ $money($u['despesas']) }}</p>
                </div>
                <div class="rounded-xl bg-orange-50 border border-orange-100 p-2.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-orange-600">Cartão</p>
                    <p class="num font-extrabold text-[13px] text-orange-700 mt-0.5">{{ $money($u['cartao']) }}</p>
                </div>
            </div>
            <div class="mt-3">
                <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full" style="width:{{ $u['pct'] }}%;background:{{ $u['cor'] }}"></div>
                </div>
            </div>
        </div>
        @empty
        <p class="text-[13px] text-gray-400 text-center py-8 col-span-full">Nenhum membro na conta.</p>
        @endforelse
    </div>
</x-section-card>

<script>
window.DashboardCharts = {
    'chart-fluxo': @json($charts['fluxo']),
    'chart-categorias': @json($charts['categorias']),
    'chart-receitas': @json($charts['receitasCat']),
    'chart-alocacao': @json($charts['alocacao']),
};
</script>
@endsection