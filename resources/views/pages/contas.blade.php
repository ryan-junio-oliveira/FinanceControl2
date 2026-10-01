@extends('layouts.app')
@section('title','Contas')
@section('breadcrumb','Banco / Contas')

@section('content')
@php use App\Support\Fin; $isGestor = auth()->user()->isAdmin(); @endphp

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[24px] font-extrabold tracking-tight text-gray-900">Contas Bancárias &amp; Saldos</h1>
        <p class="text-[13px] text-gray-400 mt-0.5 font-medium">Saldos calculados a partir dos lançamentos pagos + saldo inicial.</p>
    </div>
    @if($isGestor)
    <div class="flex gap-2 flex-wrap">
        <x-btn-link :href="route('contas.transfer.create')" color="ghost" icon="swap_horiz">Transferência Interna</x-btn-link>
        <x-btn-link :href="route('contas.create')" color="blue" icon="add_circle">Nova Conta</x-btn-link>
    </div>
    @endif
</div>

{{-- Cards de contas --}}
<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
    @forelse($contas as $c)
    <div class="section-card hover:-translate-y-1 hover:shadow-[var(--shadow-lift)] transition-all duration-200">
        <div class="flex items-center gap-3 mb-3">
            <span class="w-11 h-11 rounded-lg grid place-items-center text-white text-[13px] font-extrabold shadow-sm"
                style="background:{{ $c->display_color }}">{{ mb_strtoupper(mb_substr($c->name, 0, 2)) }}</span>
            <div class="flex-1 min-w-0">
                <p class="font-extrabold text-[14px] truncate text-gray-800">{{ $c->name }}</p>
                <p class="text-[11px] text-gray-400">{{ $c->bank->name ?? '—' }} · {{ ucfirst($c->kind) }}</p>
            </div>
            @if(!$c->active)<x-badge type="warning">Inativa</x-badge>@endif
        </div>
        <p class="num text-[24px] font-extrabold text-gray-900">{{ Fin::money($c->balance) }}</p>
        <p class="text-[12px] text-gray-400 num mt-0.5">Inicial: {{ Fin::money($c->initial_balance) }}</p>
        @if($isGestor)
        <div class="mt-4 pt-3 border-t border-slate-200 flex items-center gap-1.5">
            <x-btn-link :href="route('contas.edit', $c)" iconOnly icon="edit" title="Editar conta" />
            <form method="POST" action="{{ route('contas.destroy', $c) }}"
                onsubmit="return confirm('Excluir esta conta? Só é possível sem movimentações.')" class="inline">
                @csrf @method('DELETE')
                <x-btn-submit color="danger" iconOnly icon="delete" title="Excluir conta" />
            </form>
        </div>
        @endif
    </div>
    @empty
    <div class="col-span-full bg-white rounded-2xl border border-dashed border-slate-200">
        <x-empty-state icon="account_balance" title="Nenhuma conta cadastrada" hint="Conecte a primeira conta para começar o controle."
            :actionUrl="$isGestor ? route('contas.create') : null" actionLabel="Nova Conta" />
    </div>
    @endforelse
</div>

{{-- Extrato integrado --}}
<x-section-card title="Extrato Integrado" :subtitle="$extrato->total().' movimentação(ões)'">
    <div class="overflow-x-auto -mx-5 lg:-mx-6 px-5 lg:px-6">
        <table class="w-full text-left min-w-[900px] table-modern">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Operação</th>
                    <th>Conta</th>
                    <th>Responsável</th>
                    <th>Natureza</th>
                    <th class="text-right">Valor</th>
                    <th class="text-right">Status</th>
                </tr>
            </thead>
            <tbody class="text-[13px]">
                @forelse($extrato as $e)
                <tr data-ledger-row>
                    <td class="num text-gray-400 whitespace-nowrap text-[12px]">{{ $e->occurred_on->format('d/m/Y') }}</td>
                    <td class="font-bold text-gray-800">{{ $e->description }}</td>
                    <td class="text-gray-500 text-[12px]">{{ $e->account->name ?? '—' }}</td>
                    <td class="text-gray-600">{{ $e->member->name ?? '—' }}</td>
                    <td><x-badge :type="$e->type === 'receita' ? 'success' : 'neutral'">{{ ucfirst($e->type) }}</x-badge></td>
                    <td class="text-right font-extrabold num {{ $e->type === 'receita' ? 'text-emerald-600' : 'text-gray-800' }}">
                        {{ $e->type === 'receita' ? '+' : '−' }}{{ Fin::money($e->amount) }}
                    </td>
                    <td class="text-right"><x-badge :type="$e->display_status_type">{{ $e->display_status }}</x-badge></td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-gray-400">
                        <x-empty-state icon="receipt_long" title="Nenhuma movimentação registrada" />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $extrato->links() }}</div>
</x-section-card>
@endsection
