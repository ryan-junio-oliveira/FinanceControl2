@extends('layouts.guest')
@section('title', 'Entrar')

@section('content')
<p class="text-[11px] font-bold uppercase tracking-[0.08em] text-emerald-700">Acesso ao sistema</p>
<h1 class="text-xl font-bold tracking-tight text-gray-900 mt-1.5">Entrar</h1>
<p class="text-[13px] text-gray-500 mt-1 leading-relaxed">Informe suas credenciais para acessar o painel da sua conta.</p>

@if(session('status'))
<div class="flex gap-2.5 items-start text-[13px] text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg p-3.5 mt-4">
    <span class="material-symbols-outlined text-[18px] shrink-0">check_circle</span>
    <span>{{ session('status') }}</span>
</div>
@endif

<form action="{{ route('login.attempt') }}" method="POST" class="mt-5 space-y-4">
    @csrf
    <div class="flex flex-col gap-1.5">
        <label for="email" class="text-xs font-bold text-gray-600">E-mail</label>
        <input id="email" name="email" type="email" required value="{{ old('email') }}" placeholder="Digite seu e-mail"
            class="h-12 rounded-lg border px-4 text-sm w-full bg-white text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-emerald-700 focus:ring-[3px] focus:ring-emerald-700/10 {{ $errors->has('email') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10' : 'border-gray-500' }}">
        @error('email')<p class="flex items-center gap-1 text-xs font-semibold text-red-600"><span class="material-symbols-outlined text-[14px]">error</span>{{ $message }}</p>@enderror
    </div>

    <div class="flex flex-col gap-1.5">
        <label for="senha" class="text-xs font-bold text-gray-600">Senha</label>
        <div class="relative">
            <input id="senha" name="password" type="password" required placeholder="Digite sua senha"
                class="h-12 rounded-lg border px-4 text-sm w-full bg-white text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-emerald-700 focus:ring-[3px] focus:ring-emerald-700/10 {{ $errors->has('password') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10' : 'border-gray-500' }}" style="padding-right:2.75rem">
            <button type="button" tabindex="-1" onclick="const i=document.getElementById('senha');i.type=i.type==='password'?'text':'password';this.querySelector('span').textContent=i.type==='password'?'visibility':'visibility_off'"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 grid place-items-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-slate-100 transition" title="Mostrar senha">
                <span class="material-symbols-outlined text-[19px]">visibility</span>
            </button>
        </div>
        @error('password')<p class="flex items-center gap-1 text-xs font-semibold text-red-600"><span class="material-symbols-outlined text-[14px]">error</span>{{ $message }}</p>@enderror
        <div class="flex justify-end">
            <a href="{{ route('password.recuperar') }}" class="text-xs font-semibold text-emerald-700 underline decoration-emerald-200 underline-offset-[3px] hover:text-emerald-800">Esqueceu a senha?</a>
        </div>
    </div>

    <label class="flex items-start gap-2.5 text-[13px] text-gray-600 cursor-pointer">
        <input type="checkbox" name="remember" value="1" checked class="w-4 h-4 mt-0.5 accent-emerald-700 shrink-0">
        <span>Lembrar deste dispositivo por 30 dias</span>
    </label>

    <button class="flex items-center justify-center gap-2 h-11 px-5 rounded-lg bg-emerald-700 text-white text-sm font-semibold border border-emerald-700 hover:bg-emerald-800 transition w-full">Entrar no sistema</button>
</form>

<p class="text-center text-[13px] text-gray-500 mt-5">
    Ainda não tem conta?
    <a href="{{ route('cadastro') }}" class="text-[13px] font-semibold text-emerald-700 underline decoration-emerald-200 underline-offset-[3px] hover:text-emerald-800">Criar conta</a>
</p>
@endsection
