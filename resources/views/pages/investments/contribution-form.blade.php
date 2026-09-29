@extends('layouts.app')
@section('title', 'Novo Aporte')
@section('breadcrumb', 'Investimentos / Novo Aporte')
@section('nav-active', 'investimentos')

@section('content')
<div class="max-w-2xl mx-auto w-full">
    <a href="{{ route('investimentos') }}" class="inline-flex items-center gap-1 text-[12px] font-bold text-gray-500 hover:text-gray-800"><span class="material-symbols-outlined text-[16px]">arrow_back</span> Voltar para investimentos</a>
    <h1 class="text-[24px] font-extrabold tracking-tight mt-1">Novo aporte / rendimento</h1>
    <p class="text-[13px] text-gray-500">Aportes saem da conta escolhida; rendimentos só registram o ganho.</p>

    <form method="POST" action="{{ route('investimentos.aportes.store') }}" class="mt-5 bg-white rounded-lg border border-gray-500 p-5 lg:p-6 space-y-4">
        @csrf
        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-cart">Carteira *</label>
                <select id="f-cart" name="portfolio_id" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-2 text-[14px] bg-white">
                    <option value="">Selecione a carteira</option>
                    @foreach($portfolios as $p)<option value="{{ $p->id }}" {{ (string)old('portfolio_id', $selected) === (string)$p->id ? 'selected' : '' }}>{{ $p->name }}</option>@endforeach
                </select>
                @error('portfolio_id')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-tipo">Tipo *</label>
                <select id="f-tipo" name="kind" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-2 text-[14px] bg-white">
                    <option value="aporte" {{ old('kind', 'aporte') === 'aporte' ? 'selected' : '' }}>Aporte (sai da conta)</option>
                    <option value="rendimento" {{ old('kind') === 'rendimento' ? 'selected' : '' }}>Rendimento</option>
                </select>
                @error('kind')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-conta">Conta de origem</label>
                <select id="f-conta" name="account_id" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-2 text-[14px] bg-white">
                    <option value="">Selecione a conta (obrigatória p/ aporte)</option>
                    @foreach($contas as $c)<option value="{{ $c->id }}" {{ (string)old('account_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
                </select>
                @error('account_id')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-valor">Valor (R$) *</label>
                <input id="f-valor" name="amount" required inputmode="decimal" value="{{ old('amount') }}" placeholder="Digite o valor (ex.: 1000,00)"
                    class="mt-1.5 w-full h-12 rounded-lg border px-4 text-[14px] num outline-none focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('amount') ? 'border-red-400 focus:border-red-500' : 'border-gray-500 focus:border-emerald-600' }}">
                @error('amount')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-data">Data *</label>
                <input id="f-data" name="occurred_on" type="date" required value="{{ old('occurred_on', now()->toDateString()) }}" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-3 text-[14px]">
                @error('occurred_on')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-nota">Nota</label>
                <input id="f-nota" name="note" value="{{ old('note') }}" placeholder="Digite uma nota (opcional)" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-4 text-[14px] outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-500/10">
            </div>
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('investimentos') }}" class="h-11 px-5 rounded-lg border border-gray-500 bg-white text-[13px] font-bold flex items-center">Cancelar</a>
            <button class="h-11 px-6 rounded-lg bg-emerald-600 text-white text-[13px] font-bold hover:bg-emerald-700">Registrar</button>
        </div>
    </form>
</div>
@endsection
