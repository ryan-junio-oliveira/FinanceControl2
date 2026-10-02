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
