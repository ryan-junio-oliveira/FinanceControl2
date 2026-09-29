@extends('layouts.app')
@section('title','Receitas')
@section('breadcrumb','Fluxo / Receitas')

@section('content')
@php use App\Support\Fin; $isGestor = auth()->user()->isAdmin(); @endphp

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[24px] font-extrabold tracking-tight text-gray-900">Gestão de Receitas Familiares</h1>
        <p class="text-[13px] text-gray-400 mt-0.5 font-medium">Entradas em {{ $mes }}.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('receitas.create') }}" class="btn-primary">
            <span class="material-symbols-outlined text-[17px]">add_circle</span>
            Nova Receita
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
    <x-kpi-card label="Total no Mês"       :value="Fin::money($total)"    icon="payments"   accent="green" />
    <x-kpi-card label="Fixas"              :value="Fin::money($fixas)"    icon="schedule"   accent="emerald" />
    <x-kpi-card label="Variáveis / Extras" :value="Fin::money($variaveis)" icon="hub"        accent="blue" />
    <x-kpi-card label="A Receber"          :value="Fin::money($aVencer)"  icon="event_busy" accent="amber" />
</div>

<x-section-card title="Ledger de Receitas" :subtitle="'Página '.$ledger->currentPage().' de '.$ledger->lastPage().' · '.$ledger->total().' registro(s)'">
    <x-slot:action>
        <form method="GET" action="{{ route('receitas') }}" class="flex flex-wrap gap-2 items-center">
            <input type="hidden" name="mes" value="{{ $mes }}">
            <select name="status" onchange="this.form.submit()"
                class="h-9 rounded-lg border border-gray-500 text-[12px] font-bold px-3 bg-white text-gray-700 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/10 outline-none transition">
                <option value="">Todos status</option>
                @foreach(['pago'=>'Recebidas','pendente'=>'Pendentes','agendado'=>'Agendadas'] as $v=>$l)
                    <option value="{{ $v }}" {{ request('status')===$v?'selected':'' }}>{{ $l }}</option>
                @endforeach
            </select>
            <select name="categoria" onchange="this.form.submit()"
                class="h-9 rounded-lg border border-gray-500 text-[12px] font-bold px-3 bg-white text-gray-700 max-w-[180px] focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/10 outline-none transition">
                <option value="">Todas categorias</option>
                @foreach($categorias as $c)
                    <option value="{{ $c->id }}" {{ (string)request('categoria')===(string)$c->id?'selected':'' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            <select name="membro" onchange="this.form.submit()"
                class="h-9 rounded-lg border border-gray-500 text-[12px] font-bold px-3 bg-white text-gray-700 max-w-[160px] focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/10 outline-none transition">
                <option value="">Todas as pessoas</option>
                @foreach($membros as $m)
                    <option value="{{ $m->id }}" {{ (string)request('membro')===(string)$m->id?'selected':'' }}>{{ $m->name }}</option>
                @endforeach
            </select>
            @if(request('q') || request('status') || request('categoria') || request('membro'))
            <a href="{{ route('receitas', ['mes' => $mes]) }}"
                class="h-9 px-3 rounded-lg text-[12px] font-bold text-gray-400 hover:text-red-500 hover:bg-red-50 flex items-center gap-1 transition">
                <span class="material-symbols-outlined text-[15px]">close</span> Limpar
            </a>
            @endif
        </form>
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
                    <td class="text-gray-400 num whitespace-nowrap text-[12px]">{{ $t->occurred_on->format('d/m') }}</td>
                    <td class="font-bold text-gray-800">{{ $t->description }}</td>
                    <td><x-badge type="neutral">{{ $t->category->name ?? '—' }}</x-badge></td>
                    <td class="text-gray-500">{{ $t->member->name ?? '—' }}</td>
                    <td class="text-gray-500">{{ $t->account->name ?? '—' }}</td>
                    <td class="text-right font-extrabold num text-emerald-600">+{{ Fin::money($t->amount) }}</td>
                    <td class="text-right"><x-badge :type="$t->display_status_type">{{ $t->display_status }}</x-badge></td>
                    <td class="text-right whitespace-nowrap">
                        @if($t->status !== 'pago')
                        <form method="POST" action="{{ route('lancamentos.settle', $t) }}" class="inline">
                            @csrf
                            <button title="Marcar recebido" class="w-8 h-8 rounded-lg hover:bg-emerald-50 text-emerald-500 hover:text-emerald-700 transition inline-grid place-items-center">
                                <span class="material-symbols-outlined text-[19px]">check_circle</span>
                            </button>
                        </form>
                        @endif
                        <a title="Editar" href="{{ route('lancamentos.edit', $t) }}"
                            class="w-8 h-8 rounded-lg hover:bg-slate-100 text-gray-400 hover:text-gray-600 transition inline-grid place-items-center">
                            <span class="material-symbols-outlined text-[19px]">edit</span>
                        </a>
                        @if($isGestor)
                        <form method="POST" action="{{ route('lancamentos.destroy', $t) }}" class="inline" onsubmit="return confirm('Excluir esta receita?')">
                            @csrf @method('DELETE')
                            <button title="Excluir" class="w-8 h-8 rounded-lg hover:bg-red-50 text-gray-300 hover:text-red-500 transition inline-grid place-items-center">
                                <span class="material-symbols-outlined text-[19px]">delete</span>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-14 text-gray-400">
                        <span class="material-symbols-outlined text-[48px] text-gray-300">search_off</span>
                        <p class="text-[14px] font-bold mt-3 text-gray-500">Nenhuma receita encontrada</p>
                        <p class="text-[12px] mt-1">Ajuste os filtros ou registre a primeira receita.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $ledger->links() }}</div>
</x-section-card>
@endsection
