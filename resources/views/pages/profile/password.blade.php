@extends('layouts.app')
@section('title', 'Trocar Senha')
@section('breadcrumb', 'Conta / Trocar Senha')

@section('content')
<div class="max-w-4xl mx-auto w-full">
    <x-form.header
        title="Trocar senha"
        subtitle="Confirme a senha atual e defina a nova senha."
        :backUrl="route('perfil')"
        backLabel="Voltar para o perfil"
        icon="lock_reset"
        iconBg="linear-gradient(135deg,#FEF3C7,#FDE68A)"
        iconColor="#D97706" />

    <form method="POST" action="{{ route('perfil.senha.update') }}" class="form-card">
        @csrf @method('PATCH')

        <div class="form-grid">
            <div class="flex items-start gap-3 p-3.5 rounded-xl bg-amber-50 border border-amber-100">
                <span class="material-symbols-outlined text-amber-500 text-[20px] shrink-0 mt-0.5"
                    style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20">info</span>
                <p class="text-[12px] text-amber-800 font-medium leading-relaxed">
                    Use ao menos <strong>8 caracteres</strong> com letras maiúsculas, minúsculas, números e símbolos para uma senha forte.
                </p>
            </div>

            <x-form.field label="Senha atual" for="f-atual" :required="true" :error="$errors->first('current_password')">
                <div class="relative">
                    <x-form.input id="f-atual" name="current_password" required type="password" placeholder="••••••••" class="pr-12" />
                    <button type="button"
                        onclick="togglePwd('f-atual', this)"
                        class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 grid place-items-center rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                    </button>
                </div>
            </x-form.field>

            <x-form.field label="Nova senha" for="f-nova" :required="true" :error="$errors->first('password')">
                <div class="relative">
                    <x-form.input id="f-nova" name="password" required type="password" minlength="8" placeholder="••••••••" class="pr-12" oninput="checkStrength(this.value)" />
                    <button type="button"
                        onclick="togglePwd('f-nova', this)"
                        class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 grid place-items-center rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                    </button>
                </div>
                <div id="pwd-strength" class="mt-2 flex items-center gap-2 opacity-0 transition-opacity duration-300">
                    <div class="flex gap-1 flex-1">
                        <div id="s1" class="h-1.5 flex-1 rounded-full bg-slate-200 transition-all duration-300"></div>
                        <div id="s2" class="h-1.5 flex-1 rounded-full bg-slate-200 transition-all duration-300"></div>
                        <div id="s3" class="h-1.5 flex-1 rounded-full bg-slate-200 transition-all duration-300"></div>
                        <div id="s4" class="h-1.5 flex-1 rounded-full bg-slate-200 transition-all duration-300"></div>
                    </div>
                    <span id="s-label" class="text-[11px] font-bold w-16 text-right"></span>
                </div>
            </x-form.field>

            <x-form.field label="Confirmar nova senha" for="f-conf" :required="true">
                <div class="relative">
                    <x-form.input id="f-conf" name="password_confirmation" required type="password" placeholder="••••••••" class="pr-12" />
                    <button type="button"
                        onclick="togglePwd('f-conf', this)"
                        class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 grid place-items-center rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                    </button>
                </div>
            </x-form.field>
        </div>

        <x-form.actions :cancelUrl="route('perfil')" submitLabel="Trocar senha" submitIcon="lock_reset" />
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
        el.className = 'h-1.5 flex-1 rounded-full transition-all duration-300 ' + (i < score ? colors[score - 1] : 'bg-slate-200');
    });
    const lbl = document.getElementById('s-label');
    if(score > 0) { lbl.textContent = labels[score-1][0]; lbl.className = 'text-[11px] font-bold w-16 text-right ' + labels[score-1][1]; }
}
</script>
@endpush
@endsection
