@extends('layouts.app')
@section('title', ($categoria ? 'Editar' : 'Nova').' Categoria')
@section('breadcrumb', 'Categorias / '.($categoria ? 'Editar' : 'Nova'))
@section('nav-active', 'categorias')

@section('content')
@php
    $action = $categoria ? route('categorias.update', $categoria) : route('categorias.store');
    $val = fn($k, $d = null) => old($k, $categoria?->$k ?? $d);
    $subsTexto = old('subcategories', $categoria ? $categoria->subcategories->pluck('name')->join("\n") : '');
@endphp
<div class="max-w-2xl mx-auto w-full">
    <a href="{{ route('categorias') }}" class="inline-flex items-center gap-1 text-[12px] font-bold text-gray-500 hover:text-gray-800"><span class="material-symbols-outlined text-[16px]">arrow_back</span> Voltar para categorias</a>
    <h1 class="text-[24px] font-extrabold tracking-tight mt-1">{{ $categoria ? 'Editar categoria' : 'Nova categoria' }}</h1>
    <p class="text-[13px] text-gray-500">Organize o orçamento com tetos mensais por categoria.</p>

    <form method="POST" action="{{ $action }}" class="mt-5 bg-white rounded-lg border border-gray-500 p-5 lg:p-6 space-y-4">
        @csrf
        @if($categoria) @method('PATCH') @endif

        <div>
            <label class="text-[12px] font-bold text-gray-600" for="f-nome">Nome *</label>
            <input id="f-nome" name="name" required value="{{ $val('name') }}" placeholder="Digite o nome (ex.: Alimentação)"
                class="mt-1.5 w-full h-12 rounded-lg border px-4 text-[14px] outline-none focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('name') ? 'border-red-400 focus:border-red-500' : 'border-gray-500 focus:border-emerald-600' }}">
            @error('name')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-tipo">Tipo *</label>
                @if($categoria)
                <input value="{{ $categoria->type === 'despesa' ? 'Despesa' : 'Receita' }} (não pode mudar)" disabled class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 bg-slate-50 px-4 text-[14px] text-gray-500">
                @else
                <select id="f-tipo" name="type" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-2 text-[14px] bg-white">
                    <option value="despesa" {{ old('type', 'despesa') === 'despesa' ? 'selected' : '' }}>Despesa</option>
                    <option value="receita" {{ old('type') === 'receita' ? 'selected' : '' }}>Receita</option>
                </select>
                @endif
                @error('type')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-teto">Teto mensal (R$)</label>
                <input id="f-teto" name="monthly_cap" inputmode="decimal" value="{{ $val('monthly_cap') }}" placeholder="Digite o teto (ex.: 1500,00)"
                    class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-4 text-[14px] num outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-500/10">
                @error('monthly_cap')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="text-[12px] font-bold text-gray-600" for="f-icone">Ícone</label>
            <input id="f-icone" name="icon" value="{{ $val('icon', 'tag') }}" placeholder="Digite o ícone (ex.: shopping_cart)"
                class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-4 text-[14px] outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-500/10">
            <p class="text-[11px] text-gray-500 mt-1">Use um nome do Material Symbols (ex.: restaurant, home, school).</p>
        </div>

        @if(!$categoria)
        <div>
            <label class="text-[12px] font-bold text-gray-600" for="f-subs">Subcategorias</label>
            <textarea id="f-subs" name="subcategories" rows="3" placeholder="Digite uma subcategoria por linha (ex.: Supermercado)" class="mt-1.5 w-full rounded-lg border border-gray-500 px-4 py-3 text-[14px] outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-500/10">{{ $subsTexto }}</textarea>
        </div>
        @elseif($categoria->subcategories->isNotEmpty())
        <div class="rounded-lg bg-slate-50 border border-gray-500 p-3.5">
            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Subcategorias ({{ $categoria->subcategories->count() }})</p>
            <ul class="mt-1.5 space-y-1 text-[13px] text-gray-600">@foreach($categoria->subcategories as $s)<li>• {{ $s->name }}</li>@endforeach</ul>
        </div>
        @endif

        @if($categoria)
        <label class="flex items-center gap-2 text-[13px] text-gray-600"><input type="checkbox" name="archived" value="1" {{ old('archived', $categoria->archived) ? 'checked' : '' }} class="w-4 h-4 accent-amber-500"> Arquivar categoria</label>
        @endif

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('categorias') }}" class="h-11 px-5 rounded-lg border border-gray-500 bg-white text-[13px] font-bold flex items-center">Cancelar</a>
            <button class="h-11 px-6 rounded-lg bg-slate-900 text-white text-[13px] font-bold">{{ $categoria ? 'Salvar alterações' : 'Salvar categoria' }}</button>
        </div>
    </form>
</div>
@endsection
