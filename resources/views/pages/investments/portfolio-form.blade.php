@extends('layouts.app')
@section('title', 'Nova Carteira')
@section('breadcrumb', 'Investimentos / Nova Carteira')
@section('nav-active', 'investimentos')

@section('content')
<div class="max-w-2xl mx-auto w-full">
    <a href="{{ route('investimentos') }}" class="inline-flex items-center gap-1 text-[12px] font-bold text-gray-500 hover:text-gray-800"><span class="material-symbols-outlined text-[16px]">arrow_back</span> Voltar para investimentos</a>
    <h1 class="text-[24px] font-extrabold tracking-tight mt-1">Nova carteira</h1>
    <p class="text-[13px] text-gray-500">Agrupe ativos por objetivo (reserva, estudos, futuro).</p>

    <form method="POST" action="{{ route('investimentos.carteiras.store') }}" class="mt-5 bg-white rounded-lg border border-gray-500 p-5 lg:p-6 space-y-4">
        @csrf
        <div>
            <label class="text-[12px] font-bold text-gray-600" for="f-nome">Nome *</label>
            <input id="f-nome" name="name" required value="{{ old('name') }}" placeholder="Digite o nome (ex.: Reserva de Emergência)"
                class="mt-1.5 w-full h-12 rounded-lg border px-4 text-[14px] outline-none focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('name') ? 'border-red-400 focus:border-red-500' : 'border-gray-500 focus:border-emerald-600' }}">
            @error('name')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-tipo">Tipo *</label>
                <select id="f-tipo" name="kind" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-2 text-[14px] bg-white">
                    <option value="">Selecione o tipo</option>
                    @foreach(['reserva' => 'Reserva', 'estudos' => 'Educação', 'futuro' => 'Futuro', 'livre' => 'Livre'] as $v => $l)<option value="{{ $v }}" {{ old('kind') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                </select>
                @error('kind')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-meta">Meta (R$)</label>
                <input id="f-meta" name="target_amount" inputmode="decimal" value="{{ old('target_amount') }}" placeholder="Digite a meta (ex.: 50000,00)"
                    class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-4 text-[14px] num outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-500/10">
                @error('target_amount')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label class="text-[12px] font-bold text-gray-600" for="f-obj">Objetivo</label>
            <input id="f-obj" name="objective" value="{{ old('objective') }}" placeholder="Digite o objetivo (ex.: 6 meses de despesas)"
                class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-4 text-[14px] outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-500/10">
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('investimentos') }}" class="h-11 px-5 rounded-lg border border-gray-500 bg-white text-[13px] font-bold flex items-center">Cancelar</a>
            <button class="h-11 px-6 rounded-lg bg-slate-900 text-white text-[13px] font-bold">Salvar carteira</button>
        </div>
    </form>
</div>
@endsection
