@extends('layouts.app')
@section('title', 'Extrato · '.$conta->name)
@section('breadcrumb', 'Banco / Contas / Extrato')

@section('content')
@php use App\Support\Fin; @endphp

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <a href="{{ route('contas') }}" class="inline-flex items-center gap-1 text-[12px] font-bold text-gray-400 hover:text-gray-600 transition">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span> Voltar para contas
        </a>
        <h1 class="text-[24px] font-extrabold tracking-tight text-gray-900 mt-1">{{ $conta->name }}</h1>
        <p class="text-[13px] text-gray-400 mt-0.5 font-medium">{{ $conta->bank->name ?? 'Sem banco' }} · {{ ucfirst($conta->kind) }}</p>
    </div>
</div>

{{-- Resumo da conta --}}
<div class="grid sm:grid-cols-3 gap-4">
    <x-kpi-card label="Saldo Atual" :value="Fin::money($conta->balance)" icon="account_balance_wallet" accent="blue" />
    <x-kpi-card label="Entradas (pagas)" :value="Fin::money($totais['entradas'])" icon="trending_up" accent="green" />
    <x-kpi-card label="Saídas (pagas)" :value="Fin::money($totais['saidas'])" icon="trending_down" accent="red" />
</div>

{{-- Extrato --}}
<x-section-card title="Extrato" :subtitle="$movs->total().' movimentação(ões) · saldo inicial '.Fin::money($conta->initial_balance)">
    <div class="overflow-x-auto -mx-5 lg:-mx-6 px-5 lg:px-6">
        <table class="w-full text-left min-w-[820px] table-modern">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Categoria</th>
                    <th>Responsável</th>
                    <th>Natureza</th>
                    <th class="text-right">Valor</th>
                    <th class="text-right">Status</th>
                </tr>
            </thead>
            <tbody class="text-[13px]">
                @forelse($movs as $m)
                @php
                    $entrada = $m->type === 'receita' || (int) $m->transfer_to_account_id === (int) $conta->id;
                @endphp
                <tr data-ledger-row>
                    <td class="num text-gray-400 whitespace-nowrap text-[12px]">{{ $m->occurred_on->format('d/m/Y') }}</td>
                    <td class="font-bold text-gray-800">
                        {{ $m->description }}
                        @if($m->is_fixed)<span class="ml-1 text-[10px] font-extrabold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-lg">FIXA</span>@endif
                    </td>
                    <td><x-badge type="neutral">{{ $m->category->name ?? '—' }}</x-badge></td>
                    <td class="text-gray-500">{{ $m->member->name ?? '—' }}</td>
                    <td><x-badge :type="$entrada ? 'success' : 'neutral'">{{ $entrada ? 'Entrada' : 'Saída' }}</x-badge></td>
                    <td class="text-right font-extrabold num {{ $entrada ? 'text-emerald-600' : 'text-gray-800' }}">
                        {{ $entrada ? '+' : '−' }}{{ Fin::money($m->amount) }}
                    </td>
                    <td class="text-right"><x-badge :type="$m->display_status_type">{{ $m->display_status }}</x-badge></td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-gray-400">
                        <x-empty-state icon="receipt_long" title="Nenhuma movimentação nesta conta" hint="Lançe uma receita ou despesa nesta conta para começar." />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $movs->links() }}</div>
</x-section-card>
@endsection