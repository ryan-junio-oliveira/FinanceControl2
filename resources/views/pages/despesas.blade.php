@extends('layouts.app')
@section('title','Despesas')
@section('breadcrumb','Controle / Despesas')

@section('content')
@php use App\Support\Fin; $isGestor = auth()->user()->isAdmin(); @endphp

{{-- Page header --}}
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[24px] font-extrabold tracking-tight text-gray-900">Controle de Despesas</h1>
        <p class="text-[13px] text-gray-400 mt-0.5 font-medium">Todas as saídas em {{ $mes }}.</p>
    </div>
    <div class="flex gap-2">
        <x-btn-link :href="route('despesas.create')" color="danger" icon="add_circle">Nova Despesa</x-btn-link>
    </div>
</div>

<x-section-card title="Ledger de Despesas" :subtitle="'Página '.$ledger->currentPage().' de '.$ledger->lastPage().' · '.$ledger->total().' registro(s)'">
    <x-slot:action>
        <x-table-toolbar
            :action="route('despesas')"
            placeholder="Buscar despesa…"
            :clearUrl="route('despesas', ['mes' => $mes])"
            :hasActiveFilters="request()->filled('q') || request()->filled('status') || request()->filled('fixa') || request()->filled('categoria') || request()->filled('membro')">
            <input type="hidden" name="mes" value="{{ $mes }}">
            <select name="status" onchange="this.form.submit()" class="table-filter" aria-label="Filtrar por situação">
                <option value="">Todos status</option>
                @foreach(['pago'=>'Pagas','pendente'=>'Pendentes','agendado'=>'Agendadas'] as $v=>$l)
                    <option value="{{ $v }}" {{ request('status')===$v?'selected':'' }}>{{ $l }}</option>
                @endforeach
            </select>
            <select name="fixa" onchange="this.form.submit()" class="table-filter" aria-label="Filtrar fixas">
                <option value="">Fixas + Variáveis</option>
                <option value="1" {{ request('fixa')==='1'?'selected':'' }}>Fixas</option>
                <option value="0" {{ request('fixa')==='0'?'selected':'' }}>Variáveis</option>
            </select>
            <select name="categoria" onchange="this.form.submit()" class="table-filter" style="max-width:180px" aria-label="Filtrar por categoria">
                <option value="">Todas categorias</option>
                @foreach($categorias as $c)
                    <option value="{{ $c->id }}" {{ (string)request('categoria')===(string)$c->id?'selected':'' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            <select name="membro" onchange="this.form.submit()" class="table-filter" style="max-width:160px" aria-label="Filtrar por pessoa">
                <option value="">Todas as pessoas</option>
                @foreach($membros as $m)
                    <option value="{{ $m->id }}" {{ (string)request('membro')===(string)$m->id?'selected':'' }}>{{ $m->name }}</option>
                @endforeach
            </select>
        </x-table-toolbar>
    </x-slot:action>

    <div class="overflow-x-auto -mx-5 lg:-mx-6 px-5 lg:px-6">
        <table class="w-full text-left min-w-[860px] table-modern">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Categoria</th>
                    <th>Responsável</th>
                    <th>Conta</th>
                    <th class="text-right">Valor</th>
                    <th class="text-right">Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="text-[13px]">
                @forelse($ledger as $t)
                <tr data-ledger-row>
                    <td class="text-gray-400 num whitespace-nowrap text-[12px]">
                        {{ $t->occurred_on->format('d/m') }}
                        @if($t->is_fixed)
                        <span class="ml-1 text-[10px] font-extrabold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-lg">FIXA</span>
                        @endif
                    </td>
                    <td class="font-bold text-gray-800">{{ $t->description }}</td>
                    <td><x-badge type="neutral">{{ $t->category->name ?? '—' }}</x-badge></td>
                    <td class="text-gray-500">{{ $t->member->name ?? '—' }}</td>
                    <td class="text-gray-500">{{ $t->account->name ?? '—' }}</td>
                    <td class="text-right font-extrabold num text-gray-800">−{{ Fin::money($t->amount) }}</td>
                    <td class="text-right"><x-badge :type="$t->display_status_type">{{ $t->display_status }}</x-badge></td>
                    <td class="text-right whitespace-nowrap">
                        @if($t->status !== 'pago')
                        <form method="POST" action="{{ route('lancamentos.settle', $t) }}" class="inline">
                            @csrf
                            <x-btn-submit color="success" iconOnly icon="check_circle" title="Liquidar" />
                        </form>
                        @endif
                        <x-btn-link :href="route('lancamentos.edit', $t)" iconOnly icon="edit" title="Editar" />
                        @if($isGestor)
                        <form method="POST" action="{{ route('lancamentos.destroy', $t) }}" class="inline" onsubmit="return confirm('Excluir este lançamento?')">
                            @csrf @method('DELETE')
                            <x-btn-submit color="danger" iconOnly icon="delete" title="Excluir" />
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-gray-400">
                        <x-empty-state icon="search_off" title="Nenhuma despesa encontrada" hint="Ajuste os filtros ou registre a primeira despesa."
                            :actionUrl="route('despesas.create')" actionLabel="Nova Despesa" actionColor="danger" />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $ledger->links() }}</div>
</x-section-card>
@endsection
