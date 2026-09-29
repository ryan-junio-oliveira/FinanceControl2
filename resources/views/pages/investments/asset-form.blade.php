@extends('layouts.app')
@section('title', 'Novo Ativo')
@section('breadcrumb', 'Investimentos / Novo Ativo')
@section('nav-active', 'investimentos')

@section('content')
<div class="max-w-2xl mx-auto w-full">
    <a href="{{ route('investimentos') }}" class="inline-flex items-center gap-1 text-[12px] font-bold text-gray-500 hover:text-gray-800"><span class="material-symbols-outlined text-[16px]">arrow_back</span> Voltar para investimentos</a>
    <h1 class="text-[24px] font-extrabold tracking-tight mt-1">Novo ativo</h1>
    <p class="text-[13px] text-gray-500">Adicione um ativo a uma carteira da família.</p>

    <form method="POST" action="{{ route('investimentos.ativos.store') }}" class="mt-5 bg-white rounded-lg border border-gray-500 p-5 lg:p-6 space-y-4">
        @csrf
        <div>
            <label class="text-[12px] font-bold text-gray-600" for="f-cart">Carteira *</label>
            <select id="f-cart" name="portfolio_id" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-2 text-[14px] bg-white">
                <option value="">Selecione a carteira</option>
                @foreach($portfolios as $p)<option value="{{ $p->id }}" {{ (string)old('portfolio_id', $selected) === (string)$p->id ? 'selected' : '' }}>{{ $p->name }}</option>@endforeach
            </select>
            @error('portfolio_id')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-cod">Código *</label>
                <input id="f-cod" name="code" required value="{{ old('code') }}" placeholder="Digite o código (ex.: HGLG11)"
                    class="mt-1.5 w-full h-12 rounded-lg border px-4 text-[14px] outline-none focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('code') ? 'border-red-400 focus:border-red-500' : 'border-gray-500 focus:border-emerald-600' }}">
                @error('code')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-nome">Nome *</label>
                <input id="f-nome" name="name" required value="{{ old('name') }}" placeholder="Digite o nome (ex.: CSHG Logística FII)"
                    class="mt-1.5 w-full h-12 rounded-lg border px-4 text-[14px] outline-none focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('name') ? 'border-red-400 focus:border-red-500' : 'border-gray-500 focus:border-emerald-600' }}">
                @error('name')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-classe">Classe *</label>
                <select id="f-classe" name="kind" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-2 text-[14px] bg-white">
                    <option value="">Selecione a classe</option>
                    @foreach(['renda_fixa' => 'Renda Fixa', 'fii' => 'FII', 'acao' => 'Ação', 'etf' => 'ETF', 'previdencia' => 'Previdência'] as $v => $l)<option value="{{ $v }}" {{ old('kind') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                </select>
                @error('kind')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-valor">Valor atual (R$) *</label>
                <input id="f-valor" name="current_value" required inputmode="decimal" value="{{ old('current_value', '0') }}" placeholder="Digite o valor atual"
                    class="mt-1.5 w-full h-12 rounded-lg border px-4 text-[14px] num outline-none focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('current_value') ? 'border-red-400 focus:border-red-500' : 'border-gray-500 focus:border-emerald-600' }}">
                @error('current_value')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-inst">Instituição</label>
                <input id="f-inst" name="institution" value="{{ old('institution') }}" placeholder="Digite a instituição (ex.: XP Invest)"
                    class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-4 text-[14px] outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-500/10">
            </div>
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-rent">Rentabilidade</label>
                <input id="f-rent" name="profitability" value="{{ old('profitability') }}" placeholder="Digite a rentabilidade (ex.: +8,2%)"
                    class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-4 text-[14px] outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-500/10">
            </div>
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('investimentos') }}" class="h-11 px-5 rounded-lg border border-gray-500 bg-white text-[13px] font-bold flex items-center">Cancelar</a>
            <button class="h-11 px-6 rounded-lg bg-emerald-600 text-white text-[13px] font-bold hover:bg-emerald-700">Salvar ativo</button>
        </div>
    </form>
</div>
@endsection
