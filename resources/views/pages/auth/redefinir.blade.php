@extends('layouts.guest')
@section('title', 'Redefinir Senha')

@section('content')
<div class="w-10 h-10 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 grid place-items-center mb-3.5">
    <span class="material-symbols-outlined text-[22px]">password</span>
</div>

<p class="text-[11px] font-bold uppercase tracking-[0.08em] text-emerald-700">Recuperação de acesso</p>
<h1 class="text-xl font-bold tracking-tight text-gray-900 mt-1.5">Nova senha</h1>
<p class="text-[13px] text-gray-500 mt-1 leading-relaxed">Defina a nova senha da conta <strong class="text-gray-700">{{ $email }}</strong>.</p>

<form class="mt-5 space-y-4" method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="email" value="{{ $email }}">
    <div class="flex flex-col gap-1.5">
        <label for="password" class="text-xs font-bold text-gray-600">Nova senha</label>
        <input id="password" name="password" required type="password" minlength="8" placeholder="Mínimo 8 caracteres"
            class="h-12 rounded-lg border px-4 text-sm w-full bg-white text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-emerald-700 focus:ring-[3px] focus:ring-emerald-700/10 {{ $errors->has('password') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10' : 'border-gray-500' }}">
        @error('password')<p class="flex items-center gap-1 text-xs font-semibold text-red-600"><span class="material-symbols-outlined text-[14px]">error</span>{{ $message }}</p>@enderror
        <p class="text-[11px] text-gray-400">Maiúscula, minúscula, número e símbolo.</p>
    </div>
    <div class="flex flex-col gap-1.5">
        <label for="password_confirmation" class="text-xs font-bold text-gray-600">Confirmar senha</label>
        <input id="password_confirmation" name="password_confirmation" required type="password" placeholder="Repita a nova senha"
            class="h-12 rounded-lg border border-gray-500 px-4 text-sm w-full bg-white text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-emerald-700 focus:ring-[3px] focus:ring-emerald-700/10">
    </div>
    <x-btn-submit icon="lock_reset" class="w-full">Salvar nova senha</x-btn-submit>
</form>
@endsection
