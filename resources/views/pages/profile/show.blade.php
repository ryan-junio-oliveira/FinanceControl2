@extends('layouts.app')
@section('title', 'Meu Perfil')
@section('breadcrumb', 'Conta / Meu Perfil')

@section('content')
<div class="max-w-3xl mx-auto w-full space-y-4">
    <div>
        <h1 class="text-[24px] font-extrabold tracking-tight text-gray-900">Meu Perfil</h1>
        <p class="text-[13px] text-gray-400 mt-0.5 font-medium">{{ $user->email }} · {{ $user->family->name }}</p>
    </div>

    {{-- Informações do perfil --}}
    <x-section-card title="Informações do Perfil" subtitle="Atualize os dados da sua conta">
        <form method="POST" action="{{ route('perfil.update') }}" class="form-grid">
            @csrf @method('PATCH')
            <div class="flex items-center gap-4">
                <span class="w-14 h-14 rounded-lg grid place-items-center text-white text-[16px] font-extrabold shrink-0"
                    style="background:{{ $user->avatarColor() }}">{{ $user->initials() }}</span>
                <div class="min-w-0">
                    <p class="text-[15px] font-extrabold text-gray-900 truncate">{{ $user->name }}</p>
                    <p class="text-[11px] text-gray-400 font-medium">{{ $user->roleLabel() }} · na conta desde {{ $user->created_at->format('M/Y') }}</p>
                </div>
            </div>
            <x-form.field label="Nome completo" for="p-nome" :required="true" :error="$errors->first('name')">
                <x-form.input id="p-nome" name="name" required value="{{ old('name', $user->name) }}" placeholder="Ex.: Maria Silva" />
            </x-form.field>
            <x-form.field label="E-mail" for="p-email" :required="true" :error="$errors->first('email')">
                <x-form.input id="p-email" name="email" required type="email" value="{{ old('email', $user->email) }}" placeholder="Ex.: maria@email.com" />
            </x-form.field>
            <div class="form-grid form-grid-2">
                <x-form.field label="Telefone" for="p-tel" :error="$errors->first('phone')">
                    <x-form.input id="p-tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="Ex.: (11) 99999-8888" inputmode="tel" />
                </x-form.field>
                <x-form.field label="Nascimento" for="p-nasc" :error="$errors->first('birthdate')">
                    <x-form.input id="p-nasc" name="birthdate" type="date" value="{{ old('birthdate', $user->birthdate?->format('Y-m-d')) }}" />
                </x-form.field>
            </div>
            <div>
                <x-btn-submit icon="save">Salvar alterações</x-btn-submit>
            </div>
        </form>
    </x-section-card>

    {{-- Atualizar senha --}}
    <x-section-card title="Atualizar Senha" subtitle="Use uma senha longa e única para sua conta">
        <form method="POST" action="{{ route('perfil.senha.update') }}" class="form-grid">
            @csrf @method('PATCH')
            <x-form.field label="Senha atual" for="p-atual" :required="true" :error="$errors->first('current_password')">
                <x-form.input id="p-atual" name="current_password" required type="password" placeholder="••••••••" autocomplete="current-password" />
            </x-form.field>
            <x-form.field label="Nova senha" for="p-nova" :required="true" :error="$errors->first('password')">
                <x-form.input id="p-nova" name="password" required type="password" minlength="8" placeholder="••••••••" autocomplete="new-password" />
            </x-form.field>
            <x-form.field label="Confirmar nova senha" for="p-conf" :required="true">
                <x-form.input id="p-conf" name="password_confirmation" required type="password" placeholder="••••••••" autocomplete="new-password" />
            </x-form.field>
            <div>
                <x-btn-submit icon="lock_reset">Trocar senha</x-btn-submit>
            </div>
        </form>
    </x-section-card>

    {{-- Bot no celular --}}
    <x-section-card title="Bot no Celular" subtitle="Vincule o Telegram para lançar e consultar pelo chat">
        @if($user->bot_code)
        <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-200">
            <span class="material-symbols-outlined text-emerald-600 text-[22px]">smartphone</span>
            <div class="flex-1 min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-700">Seu código</p>
                <p class="text-[22px] font-extrabold num tracking-widest text-gray-900">{{ $user->bot_code }}</p>
                <p class="text-[12px] text-gray-500 mt-0.5">No Telegram, envie <b>/start {{ $user->bot_code }}</b> para o bot.</p>
            </div>
        </div>
        @else
        <p class="text-[13px] text-gray-500">Você ainda não tem um código. Gere um para vincular o celular.</p>
        @endif
        <form method="POST" action="{{ route('perfil.botcode') }}" class="mt-3">
            @csrf
            <x-btn-submit size="sm" icon="key">{{ $user->bot_code ? 'Gerar novo código' : 'Gerar código' }}</x-btn-submit>
        </form>
    </x-section-card>

    {{-- Meus dados (LGPD) --}}
    <x-section-card title="Meus Dados (LGPD)" subtitle="Direitos de portabilidade e acesso do Art. 18">
        <div class="flex flex-wrap items-center justify-between gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50/40">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-emerald-600 text-[22px] shrink-0">download</span>
                <div>
                    <p class="text-[13px] font-bold text-gray-800">Exportar meus dados</p>
                    <p class="text-[12px] text-gray-500 mt-0.5">Baixe uma cópia de todos os dados da sua família (lançamentos, contas, cartões e investimentos) em formato JSON.</p>
                </div>
            </div>
            <a href="{{ route('perfil.export') }}" class="h-10 px-5 inline-flex items-center gap-2 text-[13px] font-extrabold text-white bg-emerald-700 hover:bg-emerald-800 rounded-xl transition">
                <span class="material-symbols-outlined text-[17px]">file_download</span> Exportar
            </a>
        </div>
    </x-section-card>

    {{-- Encerrar cadastro (só admin) --}}
    <x-section-card title="Encerrar Cadastro" subtitle="Apaga a conta da família inteira">
        @if($user->role === 'admin')
        <div class="flex items-start gap-3 p-3.5 rounded-xl bg-red-50 border border-red-100 mb-4">
            <span class="material-symbols-outlined text-red-500 text-[20px] shrink-0 mt-0.5">warning</span>
            <p class="text-[12px] text-red-800 font-medium leading-relaxed">
                Ao encerrar, <strong>todos os membros da família perdem o acesso</strong> e
                <strong>todos os registros da conta são apagados</strong> (lançamentos, contas, cartões, categorias e convites).
                Essa ação não pode ser desfeita.
            </p>
        </div>
        <form method="POST" action="{{ route('perfil.destroy') }}" onsubmit="return confirm('Encerrar o cadastro e apagar TODOS os registros da família?')" class="form-grid">
            @csrf @method('DELETE')
            <x-form.field label="Sua senha para confirmar" for="p-del" :required="true" :error="$errors->first('password')">
                <x-form.input id="p-del" name="password" required type="password" placeholder="••••••••" autocomplete="current-password" />
            </x-form.field>
            <div>
                <x-btn-submit color="danger" icon="delete_forever">Encerrar cadastro</x-btn-submit>
            </div>
        </form>
        @else
        <div class="flex items-start gap-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200">
            <span class="material-symbols-outlined text-slate-400 text-[20px] shrink-0 mt-0.5">lock</span>
            <p class="text-[12px] text-gray-500 font-medium leading-relaxed">
                Somente o <strong>administrador da conta</strong> pode encerrar o cadastro.
                Fale com o responsável pela sua família.
            </p>
        </div>
        @endif
    </x-section-card>
</div>
@endsection
