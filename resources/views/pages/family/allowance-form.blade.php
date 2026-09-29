@extends('layouts.app')
@section('title', 'Configurar Mesada')
@section('breadcrumb', 'Família / Mesada')
@section('nav-active', 'familia')

@section('content')
<div class="max-w-2xl mx-auto w-full">
    <a href="{{ route('familia') }}" class="inline-flex items-center gap-1 text-[12px] font-bold text-gray-500 hover:text-gray-800"><span class="material-symbols-outlined text-[16px]">arrow_back</span> Voltar para a família</a>
    <h1 class="text-[24px] font-extrabold tracking-tight mt-1">Configurar mesada</h1>
    <p class="text-[13px] text-gray-500">Defina valor, frequência e dia do repasse por pessoa.</p>

    <form method="POST" action="{{ route('familia.mesadas.store') }}" class="mt-5 bg-white rounded-lg border border-gray-500 p-5 lg:p-6 space-y-4">
        @csrf
        <div>
            <label class="text-[12px] font-bold text-gray-600" for="f-membro">Pessoa *</label>
            <select id="f-membro" name="user_id" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-2 text-[14px] bg-white">
                <option value="">Selecione a pessoa</option>
                @foreach($membros as $m)<option value="{{ $m->id }}" {{ (string)old('user_id', $selected) === (string)$m->id ? 'selected' : '' }}>{{ $m->name }}</option>@endforeach
            </select>
            @error('user_id')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="grid sm:grid-cols-3 gap-3">
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-valor">Valor (R$) *</label>
                <input id="f-valor" name="amount" required inputmode="decimal" value="{{ old('amount') }}" placeholder="Digite o valor (ex.: 600,00)"
                    class="mt-1.5 w-full h-12 rounded-lg border px-4 text-[14px] num outline-none focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('amount') ? 'border-red-400 focus:border-red-500' : 'border-gray-500 focus:border-emerald-600' }}">
                @error('amount')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-freq">Frequência *</label>
                <select id="f-freq" name="frequency" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-2 text-[14px] bg-white">
                    <option value="mensal" {{ old('frequency', 'mensal') === 'mensal' ? 'selected' : '' }}>Mensal</option>
                    <option value="semanal" {{ old('frequency') === 'semanal' ? 'selected' : '' }}>Semanal</option>
                </select>
                @error('frequency')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-[12px] font-bold text-gray-600" for="f-dia">Dia do repasse *</label>
                <input id="f-dia" name="payday" type="number" min="0" max="28" required value="{{ old('payday', '5') }}" placeholder="Digite o dia (ex.: 5)"
                    class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-4 text-[14px] outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-500/10">
                @error('payday')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('familia') }}" class="h-11 px-5 rounded-lg border border-gray-500 bg-white text-[13px] font-bold flex items-center">Cancelar</a>
            <button class="h-11 px-6 rounded-lg bg-emerald-600 text-white text-[13px] font-bold hover:bg-emerald-700">Salvar mesada</button>
        </div>
    </form>
</div>
@endsection
