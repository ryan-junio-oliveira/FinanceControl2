@extends('layouts.guest')
@section('title', 'Recuperar Senha')

@section('content')
<a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-gray-800 transition mb-5">
    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
    Voltar
</a>

<div class="w-10 h-10 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 grid place-items-center mb-3.5">
    <span class="material-symbols-outlined text-[22px]">lock_reset</span>
</div>

<p class="text-[11px] font-bold uppercase tracking-[0.08em] text-emerald-700">Recuperação de acesso</p>
<h1 class="text-xl font-bold tracking-tight text-gray-900 mt-1.5">Esqueceu a senha?</h1>
<p class="text-[13px] text-gray-500 mt-1 leading-relaxed">Informe o e-mail cadastrado para receber o link de redefinição.</p>

@if(session('status'))
<div class="flex gap-2.5 items-start text-[13px] text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg p-3.5 mt-4">
    <span class="material-symbols-outlined text-[18px] shrink-0">mark_email_read</span>
    <span>{{ session('status') }}</span>
</div>
@endif

<form class="mt-5 space-y-4" method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="flex flex-col gap-1.5">
        <label for="email" class="text-xs font-bold text-gray-600">E-mail</label>
        <input id="email" name="email" required type="email" value="{{ old('email') }}" placeholder="Digite seu e-mail"
            class="h-12 rounded-lg border px-4 text-sm w-full bg-white text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-emerald-700 focus:ring-[3px] focus:ring-emerald-700/10 {{ $errors->has('email') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10' : 'border-gray-500' }}">
        @error('email')<p class="flex items-center gap-1 text-xs font-semibold text-red-600"><span class="material-symbols-outlined text-[14px]">error</span>{{ $message }}</p>@enderror
    </div>

    <x-btn-submit icon="mail" class="w-full">Enviar link</x-btn-submit>
</form>

<div class="flex gap-2.5 items-start text-xs leading-relaxed text-amber-800 bg-amber-50 border border-amber-200 rounded-lg p-3.5 mt-5">
    <span class="material-symbols-outlined text-[18px] shrink-0">shield</span>
    <span>Nunca solicitamos sua senha por e-mail ou WhatsApp. O link expira em 60 minutos.</span>
</div>
@endsection
