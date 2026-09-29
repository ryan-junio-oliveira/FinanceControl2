@extends('layouts.app')
@section('title','Investimentos')
@section('breadcrumb','Patrimônio / Investimentos')

@section('content')
@php use App\Support\Fin; @endphp

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[24px] font-extrabold tracking-tight text-gray-900">Patrimônio &amp; Investimentos</h1>
        <p class="text-[13px] text-gray-400 mt-0.5 font-medium">Carteiras e ativos em {{ $mes }}.</p>
    </div>
    <div class="flex gap-2 flex-wrap">
        <a href="{{ route('investimentos.carteiras.create') }}" class="btn-ghost">
            <span class="material-symbols-outlined text-[17px] text-blue-500">add</span>
            Nova Carteira
        </a>
        <a href="{{ route('investimentos.aportes.create') }}" class="btn-secondary">
            <span class="material-symbols-outlined text-[17px]">add_circle</span>
            Novo Aporte
        </a>
    </div>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
    <x-kpi-card label="Patrimônio Total"      :value="Fin::money($patrimonio)"   icon="account_balance"  accent="blue" />
    <x-kpi-card label="Aportes no Mês"        :value="Fin::money($aportesMes)"   icon="payments"         accent="green" />
    <x-kpi-card label="Rendimentos no Mês"    :value="Fin::money($rendimentos)"  icon="paid"             accent="emerald" />
    <x-kpi-card label="Reserva de Emergência" :value="Fin::money($reservaTotal)" icon="shield_with_heart" accent="amber">
        <x-slot:bottom>
            @if($reservaMeta > 0)
                <span class="text-[12px] text-gray-400 num">Meta {{ Fin::money($reservaMeta) }}</span>
            @else
                <span class="text-[12px] text-gray-400">Sem meta definida</span>
            @endif
        </x-slot:bottom>
        @if($reservaMeta > 0)<div class="mt-3"><x-progress :value="min(100, $reservaTotal / $reservaMeta * 100)" /></div>@endif
    </x-kpi-card>
</div>

{{-- Carteiras --}}
<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
    @forelse($portfolios as $p)
    <div class="section-card hover:shadow-[var(--shadow-lift)] hover:-translate-y-1 transition-all duration-200 cursor-default">
        <div class="flex items-center justify-between gap-2 mb-2">
            <p class="font-extrabold text-[14px] text-gray-800 truncate">{{ $p->name }}</p>
            <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 grid place-items-center shrink-0">
                <span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20">account_balance</span>
            </span>
        </div>
        <p class="text-[12px] text-gray-400 font-medium">
            {{ \App\Models\Portfolio::KINDS[$p->kind] ?? $p->kind }}{{ $p->objective ? ' · '.$p->objective : '' }}
        </p>
        <p class="num font-extrabold text-[20px] mt-2 text-gray-900">{{ Fin::money($p->total) }}</p>
        @if($p->target_amount)
        <x-progress :value="$p->progress ?? 0" class="mt-3"/>
        <p class="text-[11px] text-gray-400 mt-1.5 num">{{ $p->progress }}% de {{ Fin::money($p->target_amount) }}</p>
        @endif
        <p class="text-[11px] text-gray-300 mt-1.5">{{ $p->assets->count() }} ativo(s)</p>
    </div>
    @empty
    <div class="col-span-full text-center py-16 text-gray-400 bg-white rounded-lg border border-dashed border-gray-500">
        <span class="material-symbols-outlined text-[52px] text-gray-300">savings</span>
        <p class="text-[15px] font-extrabold mt-4 text-gray-500">Nenhuma carteira ainda</p>
        <p class="text-[13px] mt-1">Crie a primeira carteira (ex.: Reserva de Emergência).</p>
        <a href="{{ route('investimentos.carteiras.create') }}" class="btn-ghost inline-flex mt-5">
            <span class="material-symbols-outlined text-[17px] text-blue-500">add</span>
            Nova Carteira
        </a>
    </div>
    @endforelse
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    {{-- Alocação por classe --}}
    <x-section-card class="lg:col-span-5" title="Alocação por Classe" subtitle="Soma dos ativos por classe">
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

    <div class="lg:col-span-7">
        <x-section-card title="Adicionar Ativo" subtitle="Registre ativos nas suas carteiras">
            <a href="{{ route('investimentos.ativos.create') }}" class="btn-primary w-full justify-center h-12">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                Adicionar ativo
            </a>
            <p class="text-[12px] text-gray-400 mt-3 text-center">Escolha a carteira e preencha os dados na próxima tela.</p>
        </x-section-card>
    </div>
</div>

{{-- Tabela de ativos --}}
<x-section-card title="Carteira Consolidada de Ativos" :subtitle="$ativos->total().' ativo(s)'">
    <div class="overflow-x-auto -mx-5 lg:-mx-6 px-5 lg:px-6">
        <table class="w-full text-left min-w-[860px] table-modern">
            <thead>
                <tr>
                    <th>Ativo</th>
                    <th>Carteira</th>
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
                    <td class="text-gray-500">{{ $a->portfolio->name ?? '—' }}</td>
                    <td><x-badge type="info">{{ $a->kind_label }}</x-badge></td>
                    <td class="text-right font-extrabold num text-gray-800">{{ Fin::money($a->current_value) }}</td>
                    <td class="text-right">
                        <form method="POST" action="{{ route('investimentos.ativos.destroy', $a) }}"
                            onsubmit="return confirm('Remover ativo?')" class="inline">
                            @csrf @method('DELETE')
                            <button class="w-8 h-8 rounded-lg hover:bg-red-50 text-gray-300 hover:text-red-500 transition inline-grid place-items-center">
                                <span class="material-symbols-outlined text-[19px]">delete</span>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-12 text-gray-400">
                        <span class="material-symbols-outlined text-[44px] text-gray-300">query_stats</span>
                        <p class="text-[13px] font-bold mt-3 text-gray-500">Nenhum ativo cadastrado.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $ativos->links() }}</div>
</x-section-card>
@endsection
