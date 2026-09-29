@extends('layouts.app')
@section('title', 'Convidar Pessoa')
@section('breadcrumb', 'Pessoas / Convidar')
@section('nav-active', 'familia')

@section('content')
<div class="max-w-2xl mx-auto w-full">
    <a href="{{ route('familia') }}" class="inline-flex items-center gap-1 text-[12px] font-bold text-gray-500 hover:text-gray-800"><span class="material-symbols-outlined text-[16px]">arrow_back</span> Voltar para a família</a>
    <h1 class="text-[24px] font-extrabold tracking-tight mt-1">Convidar pessoa</h1>
    <p class="text-[13px] text-gray-500">Geramos um link de primeiro acesso para a pessoa definir a senha.</p>

    <form method="POST" action="{{ route('familia.convites.store') }}" class="mt-5 bg-white rounded-lg border border-gray-500 p-5 lg:p-6 space-y-4">
        @csrf
        <div>
            <label class="text-[12px] font-bold text-gray-600" for="f-nome">Nome *</label>
            <input id="f-nome" name="name" required value="{{ old('name') }}" placeholder="Digite o nome (ex.: Mariana Silva)"
                class="mt-1.5 w-full h-12 rounded-lg border px-4 text-[14px] outline-none focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('name') ? 'border-red-400 focus:border-red-500' : 'border-gray-500 focus:border-emerald-600' }}">
            @error('name')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="text-[12px] font-bold text-gray-600" for="f-email">E-mail *</label>
            <input id="f-email" name="email" required type="email" value="{{ old('email') }}" placeholder="Digite o e-mail (ex.: pessoa@email.com)"
                class="mt-1.5 w-full h-12 rounded-lg border px-4 text-[14px] outline-none focus:ring-4 focus:ring-emerald-500/10 {{ $errors->has('email') ? 'border-red-400 focus:border-red-500' : 'border-gray-500 focus:border-emerald-600' }}">
            @error('email')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="text-[12px] font-bold text-gray-600" for="f-papel">Papel *</label>
            <select id="f-papel" name="role" class="mt-1.5 w-full h-12 rounded-lg border border-gray-500 px-2 text-[14px] bg-white">
                <option value="">Selecione o papel</option>
                <option value="co_admin" {{ old('role') === 'co_admin' ? 'selected' : '' }}>Co-administrador</option>
                <option value="dependente" {{ old('role', 'dependente') === 'dependente' ? 'selected' : '' }}>Dependente</option>
                <option value="junior" {{ old('role') === 'junior' ? 'selected' : '' }}>Júnior</option>
            </select>
            @error('role')<p class="text-[12px] text-red-600 font-bold mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('familia') }}" class="h-11 px-5 rounded-lg border border-gray-500 bg-white text-[13px] font-bold flex items-center">Cancelar</a>
            <button class="h-11 px-6 rounded-lg bg-emerald-600 text-white text-[13px] font-bold hover:bg-emerald-700">Criar convite</button>
        </div>
    </form>
</div>
@endsection
