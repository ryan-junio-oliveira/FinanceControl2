@extends('layouts.app')
@section('title', 'Trocar Senha')
@section('breadcrumb', 'Conta / Trocar Senha')

@section('content')
<div class="max-w-2xl mx-auto w-full">
    <a href="{{ route('perfil') }}" class="back-link">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        Voltar para o perfil
    </a>

    <div class="flex items-center gap-3 mt-3 mb-6">
        <div class="w-11 h-11 rounded-lg grid place-items-center"
            style="background: linear-gradient(135deg,#FEF3C7,#FDE68A);">
            <span class="material-symbols-outlined text-amber-600 text-[22px]"
                style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">lock_reset</span>
        </div>
        <div>
            <h1 class="text-[24px] font-extrabold tracking-tight">Trocar senha</h1>
            <p class="text-[13px] text-gray-400 mt-0.5 font-medium">Confirme a senha atual e defina a nova senha.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('perfil.senha.update') }}" class="form-card space-y-5">
        @csrf @method('PATCH')

        {{-- Dica de segurança --}}
        <div class="flex items-start gap-3 p-3.5 rounded-lg bg-amber-50 border border-amber-100">
            <span class="material-symbols-outlined text-amber-500 text-[20px] shrink-0 mt-0.5"
                style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20">info</span>
            <p class="text-[12px] text-amber-800 font-medium leading-relaxed">
                Use ao menos <strong>8 caracteres</strong> com letras maiúsculas, minúsculas, números e símbolos para uma senha forte.
            </p>
        </div>

        {{-- Senha atual --}}
        <div class="float-field {{ $errors->has('current_password') ? 'error' : '' }}">
            <div class="relative">
                <input id="f-atual" name="current_password" required type="password" placeholder=" " class="pr-12">
                <button type="button"
                    onclick="togglePwd('f-atual', this)"
                    class="absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 grid place-items-center rounded-lg text-gray-300 hover:text-gray-600 hover:bg-slate-100 transition">
                    <span class="material-symbols-outlined text-[19px]">visibility</span>
                </button>
            </div>
            <label for="f-atual">Senha atual *</label>
            @error('current_password')<p class="text-[12px] text-red-500 font-semibold mt-1.5 flex items-center gap-1">
                <span class="material-symbols-outlined text-[13px]">error</span>{{ $message }}
            </p>@enderror
        </div>

        {{-- Nova senha --}}
        <div class="float-field {{ $errors->has('password') ? 'error' : '' }}">
            <div class="relative">
                <input id="f-nova" name="password" required type="password" minlength="8" placeholder=" " class="pr-12"
                    oninput="checkStrength(this.value)">
                <button type="button"
                    onclick="togglePwd('f-nova', this)"
                    class="absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 grid place-items-center rounded-lg text-gray-300 hover:text-gray-600 hover:bg-slate-100 transition">
                    <span class="material-symbols-outlined text-[19px]">visibility</span>
                </button>
            </div>
            <label for="f-nova">Nova senha *</label>
            {{-- Indicador de força --}}
            <div id="pwd-strength" class="mt-2 flex items-center gap-2 opacity-0 transition-opacity duration-300">
                <div class="flex gap-1 flex-1">
                    <div id="s1" class="h-1.5 flex-1 rounded-lg bg-slate-200 transition-all duration-300"></div>
                    <div id="s2" class="h-1.5 flex-1 rounded-lg bg-slate-200 transition-all duration-300"></div>
                    <div id="s3" class="h-1.5 flex-1 rounded-lg bg-slate-200 transition-all duration-300"></div>
                    <div id="s4" class="h-1.5 flex-1 rounded-lg bg-slate-200 transition-all duration-300"></div>
                </div>
                <span id="s-label" class="text-[11px] font-bold w-16 text-right"></span>
            </div>
            @error('password')<p class="text-[12px] text-red-500 font-semibold mt-1.5 flex items-center gap-1">
                <span class="material-symbols-outlined text-[13px]">error</span>{{ $message }}
            </p>@enderror
        </div>

        {{-- Confirmar senha --}}
        <div class="float-field">
            <div class="relative">
                <input id="f-conf" name="password_confirmation" required type="password" placeholder=" " class="pr-12">
                <button type="button"
                    onclick="togglePwd('f-conf', this)"
                    class="absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 grid place-items-center rounded-lg text-gray-300 hover:text-gray-600 hover:bg-slate-100 transition">
                    <span class="material-symbols-outlined text-[19px]">visibility</span>
                </button>
            </div>
            <label for="f-conf">Confirmar nova senha *</label>
        </div>

        <div class="flex justify-end gap-3 pt-2 border-t border-gray-500">
            <a href="{{ route('perfil') }}" class="btn-ghost">
                <span class="material-symbols-outlined text-[17px]">close</span> Cancelar
            </a>
            <button class="btn-primary">
                <span class="material-symbols-outlined text-[17px]">lock_reset</span>
                Trocar senha
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function togglePwd(id, btn) {
    const i = document.getElementById(id);
    i.type = i.type === 'password' ? 'text' : 'password';
    btn.querySelector('span').textContent = i.type === 'password' ? 'visibility' : 'visibility_off';
}
function checkStrength(v) {
    const el = document.getElementById('pwd-strength');
    el.style.opacity = v.length ? '1' : '0';
    const checks = [v.length >= 8, /[A-Z]/.test(v), /[0-9]/.test(v), /[^A-Za-z0-9]/.test(v)];
    const score = checks.filter(Boolean).length;
    const bars = ['s1','s2','s3','s4'];
    const colors = ['bg-red-400','bg-orange-400','bg-amber-400','bg-emerald-500'];
    const labels = [['Fraca','text-red-500'],['Razoável','text-orange-500'],['Boa','text-amber-500'],['Forte','text-emerald-600']];
    bars.forEach((id, i) => {
        const el = document.getElementById(id);
        el.className = 'h-1.5 flex-1 rounded-lg transition-all duration-300 ' + (i < score ? colors[score - 1] : 'bg-slate-200');
    });
    const lbl = document.getElementById('s-label');
    if(score > 0) { lbl.textContent = labels[score-1][0]; lbl.className = 'text-[11px] font-bold w-16 text-right ' + labels[score-1][1]; }
}
</script>
@endpush
@endsection
