@extends('layouts.app')
@section('title', 'Nova Compra no Cartão')
@section('breadcrumb', 'Cartões / Nova Compra')
@section('nav-active', 'cartoes')

@section('content')
<div class="max-w-2xl mx-auto w-full">
    <a href="{{ route('cartoes') }}" class="inline-flex items-center gap-1 text-[12px] font-bold text-gray-500 hover:text-gray-800"><span class="material-symbols-outlined text-[16px]">arrow_back</span> Voltar para cartões</a>
    <h1 class="text-[24px] font-extrabold tracking-tight mt-1">Nova compra no cartão</h1>
    <p class="text-[13px] text-gray-500">A compra entra na fatura em aberto do cartão.</p>

    <form method="POST" action="{{ route('cartoes.itens.store') }}" class="mt-5 bg-white rounded-lg border border-gray-500 p-5 lg:p-6 space-y-4">
        @csrf
        <div>
            <label class="text-[12px] font-bold text-gray-600" for="f-cartao">Cartão *</label>
            <select id="f-cartao" name="credit_card_id" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-2 text-[14px] bg-white">
                <option value="">Selecione o cartão</option>
                @foreach($cartoes as $c)<option value="{{ $c->id }}" {{ (string)old('credit_card_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
            </select>
            @error('credit_card_id')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="text-[12px] font-bold text-gray-600" for="f-desc">Descrição *</label>
            <input id="f-desc" name="description" required value="{{ old('description') }}" placeholder="Digite a descrição (ex.: Mercado Central)"
                class="mt-1.5 w-full h-12 rounded-lg border px-4 text-[14px] outline-none focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('description') ? 'border-red-400 focus:border-red-500' : 'border-gray-500 focus:border-emerald-600' }}">
            @error('description')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-valor">Valor (R$) *</label>
                <input id="f-valor" name="amount" required inputmode="decimal" value="{{ old('amount') }}" placeholder="Digite o valor (ex.: 250,00)"
                    class="mt-1.5 w-full h-12 rounded-lg border px-4 text-[14px] num outline-none focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('amount') ? 'border-red-400 focus:border-red-500' : 'border-gray-500 focus:border-emerald-600' }}">
                @error('amount')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-data">Data *</label>
                <input id="f-data" name="occurred_on" type="date" required value="{{ old('occurred_on', now()->toDateString()) }}" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-3 text-[14px]">
                @error('occurred_on')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-membro">Responsável *</label>
                <select id="f-membro" name="user_id" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-2 text-[14px] bg-white">
                    <option value="">Selecione quem comprou</option>
                    @foreach($membros as $m)<option value="{{ $m->id }}" {{ (string)old('user_id', auth()->id()) === (string)$m->id ? 'selected' : '' }}>{{ $m->name }}</option>@endforeach
                </select>
                @error('user_id')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-cat">Categoria</label>
                <select id="f-cat" name="category_id" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-2 text-[14px] bg-white">
                    <option value="">Selecione a categoria</option>
                    @foreach($categorias as $c)<option value="{{ $c->id }}" {{ (string)old('category_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
                </select>
                @error('category_id')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('cartoes') }}" class="h-11 px-5 rounded-lg border border-gray-500 bg-white text-[13px] font-bold flex items-center">Cancelar</a>
            <button class="h-11 px-6 rounded-lg bg-emerald-600 text-white text-[13px] font-bold hover:bg-emerald-700">Lançar compra</button>
        </div>
    </form>
</div>
@endsection
