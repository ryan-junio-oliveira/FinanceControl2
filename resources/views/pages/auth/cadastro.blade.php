@extends('layouts.guest')
@section('title', 'Criar Conta')
@section('wide', '1')

@section('content')
<p class="text-[11px] font-bold uppercase tracking-[0.08em] text-emerald-700">Nova conta</p>
<h1 class="text-xl font-bold tracking-tight text-gray-900 mt-1.5">Criar conta</h1>
<p class="text-[13px] text-gray-500 mt-1 leading-relaxed">Use sozinho ou convide outras pessoas para acompanhar juntos.</p>

@if($errors->any())
<div class="flex gap-2.5 items-start text-[13px] text-red-800 bg-red-50 border border-red-200 rounded-lg p-3.5 mt-4">
    <span class="material-symbols-outlined text-[18px] shrink-0">error</span>
    <ul class="space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form class="mt-5 space-y-4" method="POST" action="{{ route('cadastro.store') }}">
    @csrf
    <div class="flex flex-col gap-1.5">
        <label for="manager_name" class="text-xs font-bold text-gray-600">Seu nome *</label>
        <input id="manager_name" name="manager_name" required value="{{ old('manager_name') }}" placeholder="Digite seu nome completo"
            class="h-12 rounded-lg border px-4 text-sm w-full bg-white text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-emerald-700 focus:ring-[3px] focus:ring-emerald-700/10 {{ $errors->has('manager_name') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10' : 'border-slate-300' }}">
        @error('manager_name')<p class="flex items-center gap-1 text-xs font-semibold text-red-600"><span class="material-symbols-outlined text-[14px]">error</span>{{ $message }}</p>@enderror
    </div>

    <div class="grid sm:grid-cols-2 gap-3">
        <div class="flex flex-col gap-1.5">
            <label for="email" class="text-xs font-bold text-gray-600">E-mail *</label>
            <input id="email" name="email" required type="email" value="{{ old('email') }}" placeholder="Digite seu e-mail"
                class="h-12 rounded-lg border px-4 text-sm w-full bg-white text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-emerald-700 focus:ring-[3px] focus:ring-emerald-700/10 {{ $errors->has('email') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10' : 'border-slate-300' }}">
            @error('email')<p class="flex items-center gap-1 text-xs font-semibold text-red-600"><span class="material-symbols-outlined text-[14px]">error</span>{{ $message }}</p>@enderror
        </div>
        <div class="flex flex-col gap-1.5">
            <label for="group_name" class="text-xs font-bold text-gray-600">Nome da conta *</label>
            <input id="group_name" name="group_name" required value="{{ old('group_name') }}" placeholder="Digite um nome (ex.: Carlos ou Família Silva)"
                class="h-12 rounded-lg border px-4 text-sm w-full bg-white text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-emerald-700 focus:ring-[3px] focus:ring-emerald-700/10 {{ $errors->has('group_name') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10' : 'border-slate-300' }}">
            @error('group_name')<p class="flex items-center gap-1 text-xs font-semibold text-red-600"><span class="material-symbols-outlined text-[14px]">error</span>{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-3">
        <div class="flex flex-col gap-1.5">
            <label for="cad-senha" class="text-xs font-bold text-gray-600">Senha *</label>
            <input id="cad-senha" name="password" required type="password" minlength="8" placeholder="Mínimo 8 caracteres" oninput="forcaSenha(this.value)"
                class="h-12 rounded-lg border px-4 text-sm w-full bg-white text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-emerald-700 focus:ring-[3px] focus:ring-emerald-700/10 {{ $errors->has('password') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10' : 'border-slate-300' }}">
            @error('password')<p class="flex items-center gap-1 text-xs font-semibold text-red-600"><span class="material-symbols-outlined text-[14px]">error</span>{{ $message }}</p>@enderror
        </div>
        <div class="flex flex-col gap-1.5">
            <label for="cad-conf" class="text-xs font-bold text-gray-600">Confirmar senha *</label>
            <input id="cad-conf" name="password_confirmation" required type="password" minlength="8" placeholder="Repita a senha"
                class="h-12 rounded-lg border border-slate-300 px-4 text-sm w-full bg-white text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-emerald-700 focus:ring-[3px] focus:ring-emerald-700/10">
        </div>
    </div>

    <div>
        <p class="text-xs font-semibold text-gray-600">Força da senha: <span id="forca-label" class="text-gray-400 font-normal">—</span></p>
        <div class="h-1 rounded-lg bg-slate-200 overflow-hidden mt-2"><div id="forca-bar" class="h-full rounded-lg transition-all" style="width:0%"></div></div>
        <p class="text-[11px] text-gray-400 mt-1.5">Mínimo 8 caracteres, com maiúscula, número e símbolo.</p>
    </div>

    <label class="flex items-start gap-2.5 text-[13px] text-gray-600 cursor-pointer">
        <input type="checkbox" name="terms" value="1" required {{ old('terms') ? 'checked' : '' }} class="w-4 h-4 mt-0.5 accent-emerald-700 shrink-0">
        <span>Li e concordo com os <a href="{{ route('termos') }}" target="_blank" class="text-[13px] font-semibold text-emerald-700 underline decoration-emerald-200 underline-offset-[3px] hover:text-emerald-800">Termos de Uso</a> e a <a href="{{ route('privacidade') }}" target="_blank" class="text-[13px] font-semibold text-emerald-700 underline decoration-emerald-200 underline-offset-[3px] hover:text-emerald-800">Política de Privacidade</a>.</span>
    </label>

    <x-btn-submit icon="person_add" class="w-full">Criar conta</x-btn-submit>
</form>

<p class="text-center text-[13px] text-gray-500 mt-5">
    Já possui conta?
    <a href="{{ route('login') }}" class="text-[13px] font-semibold text-emerald-700 underline decoration-emerald-200 underline-offset-[3px] hover:text-emerald-800">Fazer login</a>
</p>

@push('scripts')
<script>
function forcaSenha(v){
  let s = 0;
  if(v.length >= 8) s++;
  if(/[A-Z]/.test(v) && /[a-z]/.test(v)) s++;
  if(/\d/.test(v)) s++;
  if(/[^A-Za-z0-9]/.test(v)) s++;
  const pct = [0,25,50,75,100][s];
  const bar = document.getElementById('forca-bar'), label = document.getElementById('forca-label');
  bar.style.width = pct + '%';
  bar.style.background = s<=1 ? '#F87171' : s===2 ? '#FBBF24' : '#059669';
  label.textContent = ['—','Fraca','Razoável','Boa','Forte'][s];
}
</script>
@endpush
@endsection
