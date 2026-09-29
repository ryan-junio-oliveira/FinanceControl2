@extends('layouts.app')
@section('title', 'Anotar Banco')
@section('breadcrumb', 'Configurações / Novo Banco')
@section('nav-active', 'configuracoes')

@section('content')
<div class="max-w-2xl mx-auto w-full">
    <a href="{{ route('configuracoes') }}" class="inline-flex items-center gap-1 text-[12px] font-bold text-gray-500 hover:text-gray-800"><span class="material-symbols-outlined text-[16px]">arrow_back</span> Voltar para configurações</a>
    <h1 class="text-[24px] font-extrabold tracking-tight mt-1">Anotar banco</h1>
    <p class="text-[13px] text-gray-500">Registre os bancos da família como referência para os lançamentos.</p>

    <form method="POST" action="{{ route('configuracoes.bancos.store') }}" class="mt-5 bg-white rounded-lg border border-gray-500 p-5 lg:p-6 space-y-4">
        @csrf
        <div>
            <label class="text-[12px] font-bold text-gray-600" for="f-banco">Banco *</label>
            <input id="f-banco" name="bank" required value="{{ old('bank') }}" placeholder="Digite o banco (ex.: Itaú)"
                class="mt-1.5 w-full h-12 rounded-lg border px-4 text-[14px] outline-none focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('bank') ? 'border-red-400 focus:border-red-500' : 'border-gray-500 focus:border-emerald-600' }}">
            @error('bank')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="text-[12px] font-bold text-gray-600" for="f-det">Detalhes</label>
            <input id="f-det" name="details" value="{{ old('details') }}" placeholder="Digite os detalhes (ex.: CC 1234)"
                class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-4 text-[14px] outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-500/10">
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('configuracoes') }}" class="h-11 px-5 rounded-lg border border-gray-500 bg-white text-[13px] font-bold flex items-center">Cancelar</a>
            <button class="h-11 px-6 rounded-lg bg-emerald-600 text-white text-[13px] font-bold hover:bg-emerald-700">Salvar banco</button>
        </div>
    </form>
</div>
@endsection
