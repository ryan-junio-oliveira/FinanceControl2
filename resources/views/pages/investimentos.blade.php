@extends('layouts.app')
@section('title','Investimentos')
@section('breadcrumb','Patrimônio / Investimentos')

@section('content')
@php use App\Support\Fin; @endphp

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[24px] font-extrabold tracking-tight text-gray-900">Meus Investimentos</h1>
        <p class="text-[13px] text-gray-400 mt-0.5 font-medium">Todos os seus ativos em um só lugar.</p>
    </div>
    <div class="flex gap-2 flex-wrap items-center">
        <x-month-picker :action="route('investimentos')" :mes="$mes" />
        <x-btn-link :href="route('mercado')" color="ghost" icon="candlestick_chart">Ver mercado</x-btn-link>
        <x-btn-link :href="route('investimentos.ativos.create')" color="cyan" icon="add_circle">Adicionar ativo</x-btn-link>
        <x-btn-link :href="route('investimentos.aportes.create')" color="cyan" icon="payments">Novo Aporte</x-btn-link>
    </div>
</div>

{{-- Resumo do mês --}}
<div class="grid sm:grid-cols-3 gap-4">
    <x-kpi-card label="Patrimônio Total" :value="Fin::money($patrimonio)" icon="savings" accent="cyan" />
    <x-kpi-card label="Aportes no Mês" :value="Fin::money($aportesMes)" icon="add_circle" accent="emerald" />
    <x-kpi-card label="Rendimentos no Mês" :value="Fin::money($rendMes)" icon="trending_up" accent="green" />
</div>

{{-- Metas financeiras --}}
<x-section-card title="Metas" subtitle="Objetivo, faltam e aporte mensal para o prazo">
    <x-slot:action>
        <x-btn-link :href="route('investimentos.carteiras.create')" color="cyan" size="sm" icon="flag">Nova meta</x-btn-link>
    </x-slot:action>
    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($metas as $m)
        <div class="rounded-xl border border-slate-200 p-4">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="font-extrabold text-[14px] text-gray-800 truncate">{{ $m->name }}</p>
                    <p class="text-[11px] text-gray-400 num mt-0.5">
                        {{ Fin::money($m->total) }} de {{ Fin::money($m->target_amount) }}
                        @if($m->deadline) · até {{ $m->deadline->format('m/Y') }}@endif
                    </p>
                </div>
                <span class="text-[13px] font-extrabold num text-cyan-700 shrink-0">{{ $m->progress }}%</span>
            </div>
            <x-progress :value="$m->progress ?? 0" class="mt-3" />
            <div class="flex items-center justify-between gap-2 mt-2.5 text-[12px]">
                <span class="text-gray-500">Faltam <strong class="num text-gray-800">{{ Fin::money($m->remaining) }}</strong></span>
                @if($m->monthly_needed !== null)
                <span class="text-gray-500 num">{{ Fin::money($m->monthly_needed) }}/mês</span>
                @elseif($m->deadline && $m->months_left === 0)
                <span class="font-bold text-red-500">Prazo vencido</span>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <x-empty-state icon="flag" title="Nenhuma meta ainda" hint="Ex.: carro, viagem, reserva — com valor e prazo."
                actionUrl="{{ route('investimentos.carteiras.create') }}" actionLabel="Nova meta" actionIcon="flag" actionColor="cyan" />
        </div>
        @endforelse
    </div>
</x-section-card>

{{-- Alocação por classe --}}
    <x-section-card title="Alocação por Classe" subtitle="Soma dos ativos por classe">
        @if($porClasse->isNotEmpty())
        @php
            $tot = max(1, $porClasse->sum());
            $cores = [
                'renda_fixa'  => 'bg-emerald-500',
                'fii'         => 'bg-blue-500',
                'acao'        => 'bg-violet-500',
                'etf'         => 'bg-cyan-500',
                'previdencia' => 'bg-amber-500',
            ];
            $gradientes = [
                'renda_fixa'  => 'linear-gradient(90deg, #34D399, #059669)',
                'fii'         => 'linear-gradient(90deg, #60A5FA, #3B82F6)',
                'acao'        => 'linear-gradient(90deg, #A78BFA, #7C3AED)',
                'etf'         => 'linear-gradient(90deg, #67E8F9, #06B6D4)',
                'previdencia' => 'linear-gradient(90deg, #FCD34D, #F59E0B)',
            ];
        @endphp
        <div class="flex h-3 rounded-lg overflow-hidden gap-0.5">
            @foreach($porClasse as $k => $v)
            <div class="rounded-lg" style="width:{{ $v / $tot * 100 }}%; background: {{ $gradientes[$k] ?? '#9ca3af' }}" title="{{ $k }}"></div>
            @endforeach
        </div>
        <div class="mt-4 space-y-3">
            @foreach($porClasse as $k => $v)
            <div class="flex items-center gap-2.5 text-[13px]">
                <span class="w-3 h-3 rounded-full shrink-0" style="background: {{ $gradientes[$k] ?? '#9ca3af' }}"></span>
                <span class="flex-1 font-semibold text-gray-700">{{ \App\Models\Asset::KINDS[$k] ?? $k }}</span>
                <strong class="text-gray-800">{{ round($v / $tot * 100) }}%</strong>
                <span class="num text-gray-400 w-24 text-right text-[12px]">{{ Fin::money($v) }}</span>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-10 text-gray-400">
            <span class="material-symbols-outlined text-[44px] text-gray-300">pie_chart</span>
            <p class="text-[13px] font-bold mt-2 text-gray-500">Sem ativos para alocar.</p>
        </div>
        @endif
    </x-section-card>

{{-- Meus ativos (todos juntos formam a carteira) --}}
<x-section-card title="Meus Ativos" :subtitle="$ativos->total().' ativo(s) · '.Fin::money($patrimonio).' no total'">
    <x-slot:action>
        <x-table-toolbar
            :action="route('investimentos')"
            placeholder="Buscar ativo…"
            :clearUrl="route('investimentos')"
            :hasActiveFilters="request()->filled('q')">
            <x-btn-link :href="route('investimentos.ativos.create')" color="cyan" size="sm" icon="add_circle">Adicionar ativo</x-btn-link>
        </x-table-toolbar>
    </x-slot:action>
    <div class="overflow-x-auto -mx-5 lg:-mx-6 px-5 lg:px-6">
        <table class="w-full text-left min-w-[860px] table-modern">
            <thead>
                <tr>
                    <th>Ativo</th>
                    <th>Rentabilidade</th>
                    <th>Classe</th>
                    <th class="text-right">Valor</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="text-[13px]">
                @forelse($ativos as $a)
                <tr data-ledger-row>
                    <td>
                        <span class="font-bold text-gray-800">{{ $a->name }}</span>
                        <span class="block text-[11px] font-medium text-gray-400">{{ $a->code }}{{ $a->institution ? ' · '.$a->institution : '' }}</span>
                    </td>
                    <td>
                        @if($a->yield_label)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-1 text-[11px] font-extrabold text-emerald-700 whitespace-nowrap">
                            <span class="material-symbols-outlined text-[14px]">trending_up</span>{{ $a->yield_label }}
                        </span>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td><x-badge type="info">{{ $a->kind_label }}</x-badge></td>
                    <td class="text-right font-extrabold num text-gray-800">{{ Fin::money($a->current_value) }}</td>
                    <td class="text-right whitespace-nowrap">
                        <x-btn-link :href="route('investimentos.ativos.edit', $a)" iconOnly icon="edit" title="Editar ativo" />
                        <form method="POST" action="{{ route('investimentos.ativos.destroy', $a) }}"
                            onsubmit="return confirm('Remover ativo?')" class="inline">
                            @csrf @method('DELETE')
                            <x-btn-submit color="danger" iconOnly icon="delete" title="Remover ativo" />
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-gray-400">
                        <x-empty-state icon="query_stats" title="Nenhum ativo cadastrado" />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $ativos->links() }}</div>
</x-section-card>
@endsection
