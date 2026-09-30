@extends('layouts.app')
@section('title', 'Categorias')
@section('breadcrumb', 'Sistema / Categorias')

@section('content')
@php $isGestor = auth()->user()->isAdmin(); @endphp

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[24px] font-extrabold tracking-tight text-gray-900">Categorias</h1>
        <p class="text-[13px] text-gray-400 mt-0.5 font-medium">{{ $categorias->total() }} categoria(s) cadastrada(s).</p>
    </div>
    @if($isGestor)
    <x-btn-link :href="route('categorias.create')" color="violet" icon="add_circle">Nova Categoria</x-btn-link>
    @endif
</div>

<x-section-card title="Todas as Categorias" subtitle="Cadastro simples, sem cálculos">
    <x-slot:action>
        <x-table-toolbar
            :action="route('categorias')"
            placeholder="Buscar categoria…"
            :clearUrl="route('categorias')"
            :hasActiveFilters="request()->filled('q') || request()->filled('tipo') || request()->boolean('arquivadas')">
            <select name="tipo" onchange="this.form.submit()" class="table-filter" aria-label="Filtrar por tipo">
                <option value="">Despesas + Receitas</option>
                <option value="despesa" {{ request('tipo')==='despesa'?'selected':'' }}>Despesas</option>
                <option value="receita" {{ request('tipo')==='receita'?'selected':'' }}>Receitas</option>
            </select>
        </x-table-toolbar>
    </x-slot:action>

    <div class="overflow-x-auto -mx-5 lg:-mx-6 px-5 lg:px-6">
        <table class="w-full text-left min-w-[640px] table-modern">
            <thead>
                <tr>
                    <th class="w-10">#</th>
                    <th>Categoria</th>
                    <th>Tipo</th>
                    @if($isGestor)<th class="text-right">Ações</th>@endif
                </tr>
            </thead>
            <tbody class="text-[13px]">
                @forelse($categorias as $i => $c)
                <tr data-ledger-row>
                    <td class="num text-gray-400 text-[12px]">{{ $categorias->firstItem() + $i }}</td>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-lg grid place-items-center shrink-0 bg-slate-100 text-gray-500">
                                <span class="material-symbols-outlined text-[18px]"
                                    style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20">{{ $c->icon }}</span>
                            </span>
                            <span class="font-bold text-gray-800">{{ $c->name }}</span>
                            @if($c->archived)<x-badge type="warning">Arquivada</x-badge>@endif
                        </div>
                    </td>
                    <td><x-tipo-badge :type="$c->type" /></td>
                    @if($isGestor)
                    <td class="text-right whitespace-nowrap">
                        <a title="Editar categoria" href="{{ route('categorias.edit', $c) }}"
                            class="w-8 h-8 rounded-lg hover:bg-blue-50 text-blue-500 hover:text-blue-700 transition inline-grid place-items-center">
                            <span class="material-symbols-outlined text-[19px]">edit</span>
                        </a>
                        <form method="POST" action="{{ route('categorias.destroy', $c) }}" class="inline"
                            onsubmit="return confirm('Excluir {{ $c->name }}? Só é possível sem lançamentos.')">
                            @csrf @method('DELETE')
                            <button title="Excluir categoria" class="w-8 h-8 rounded-lg hover:bg-red-50 text-red-400 hover:text-red-600 transition inline-grid place-items-center">
                                <span class="material-symbols-outlined text-[19px]">delete</span>
                            </button>
                        </form>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $isGestor ? 4 : 3 }}" class="text-center text-gray-400">
                        <x-empty-state icon="category" title="Nenhuma categoria encontrada"
                            :actionUrl="$isGestor ? route('categorias.create') : null" actionLabel="Criar a primeira" />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $categorias->links() }}</div>
</x-section-card>
@endsection
