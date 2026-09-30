@extends('layouts.guest')
@section('title', 'Primeiro Acesso')
@section('wide', '1')

@section('content')
<p class="text-[11px] font-bold uppercase tracking-[0.08em] text-emerald-700">Primeiro acesso · {{ $invitation->family->name }}</p>
<h1 class="text-xl font-bold tracking-tight text-gray-900 mt-1.5">Olá, {{ explode(' ', $invitation->name)[0] }}!</h1>
<p class="text-[13px] text-gray-500 mt-1 leading-relaxed">
    Convite de <strong class="text-gray-700">{{ $inviter?->name ?? 'o administrador' }}</strong>
    · perfil <strong class="text-gray-700">{{ \App\Models\User::ROLES[$invitation->role] ?? $invitation->role }}</strong>.
    Defina sua senha para ativar o acesso.
</p>

@if($errors->any())
<div class="flex gap-2.5 items-start text-[13px] text-red-800 bg-red-50 border border-red-200 rounded-lg p-3.5 mt-4">
    <span class="material-symbols-outlined text-[18px] shrink-0">error</span>
    <ul class="space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form action="{{ route('password.primeiro-acesso.store', $invitation->token) }}" method="POST" class="mt-5 space-y-4" onsubmit="return validarPrimeiro(event)">
    @csrf
    <div class="flex flex-col gap-1.5">
        <label for="pa-senha" class="text-xs font-bold text-gray-600">Nova senha</label>
        <input id="pa-senha" name="password" required type="password" minlength="8" placeholder="Mínimo 8 caracteres" oninput="checarPA(this.value)"
            class="h-12 rounded-lg border px-4 text-sm w-full bg-white text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-emerald-700 focus:ring-[3px] focus:ring-emerald-700/10 {{ $errors->has('password') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10' : 'border-gray-500' }}">
        @error('password')<p class="flex items-center gap-1 text-xs font-semibold text-red-600"><span class="material-symbols-outlined text-[14px]">error</span>{{ $message }}</p>@enderror
        <ul class="mt-2 space-y-1.5 text-xs text-gray-400">
            <li id="pa-8" class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[15px]">cancel</span> Mínimo de 8 caracteres</li>
            <li id="pa-aa" class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[15px]">cancel</span> Maiúscula e minúscula</li>
            <li id="pa-num" class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[15px]">cancel</span> Número</li>
            <li id="pa-sim" class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[15px]">cancel</span> Símbolo (@, #, $, ...)</li>
        </ul>
    </div>
    <div class="flex flex-col gap-1.5">
        <label for="pa-conf" class="text-xs font-bold text-gray-600">Confirmar senha</label>
        <input id="pa-conf" name="password_confirmation" required type="password" placeholder="Repita a senha" oninput="checarPA(document.getElementById('pa-senha').value)"
            class="h-12 rounded-lg border border-gray-500 px-4 text-sm w-full bg-white text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-emerald-700 focus:ring-[3px] focus:ring-emerald-700/10">
        <p id="pa-match" class="text-xs mt-1 text-gray-400">As senhas devem coincidir.</p>
    </div>
    <x-btn-submit icon="key" class="w-full">Ativar acesso</x-btn-submit>
</form>

@push('scripts')
<script>
function setCrit(id, ok){
  const el = document.getElementById(id);
  el.classList.toggle('text-emerald-700', ok);
  el.classList.toggle('font-semibold', ok);
  el.classList.toggle('text-gray-400', !ok);
  el.querySelector('span').textContent = ok ? 'check_circle' : 'cancel';
  return ok;
}
function checarPA(v){
  const c = document.getElementById('pa-conf').value;
  const a = setCrit('pa-8', v.length >= 8);
  const b = setCrit('pa-aa', /[A-Z]/.test(v) && /[a-z]/.test(v));
  const d = setCrit('pa-num', /\d/.test(v));
  const e = setCrit('pa-sim', /[^A-Za-z0-9]/.test(v));
  const m = document.getElementById('pa-match');
  const ok = c && v === c;
  m.textContent = !c ? 'As senhas devem coincidir.' : (ok ? 'Senhas coincidem.' : 'As senhas ainda não coincidem.');
  m.className = 'text-xs mt-1 font-semibold ' + (!c ? 'text-gray-400' : ok ? 'text-emerald-700' : 'text-red-500');
  return a && b && d && e && ok;
}
function validarPrimeiro(e){
  if(!checarPA(document.getElementById('pa-senha').value)){ e.preventDefault(); return false; }
  return true;
}
</script>
@endpush
@endsection
