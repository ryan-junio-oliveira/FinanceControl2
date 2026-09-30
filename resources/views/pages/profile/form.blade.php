@extends('layouts.app')
@section('title', 'Editar Perfil')
@section('breadcrumb', 'Conta / Editar Perfil')

@section('content')
<div class="max-w-4xl mx-auto w-full">
    <x-form.header
        title="Editar perfil"
        subtitle="Atualize seus dados pessoais."
        :backUrl="route('perfil')"
        backLabel="Voltar para o perfil"
        icon="manage_accounts"
        iconBg="linear-gradient(135deg,#ECFDF5,#D1FAE5)"
        iconColor="#059669" />

    <form method="POST" action="{{ route('perfil.update') }}" class="form-card">
        @csrf @method('PATCH')

        <div class="form-grid">
            <x-form.field label="Nome completo" for="f-nome" :required="true" :error="$errors->first('name')">
                <x-form.input id="f-nome" name="name" required value="{{ old('name', $user->name) }}" placeholder="Ex.: Maria Silva" />
            </x-form.field>

            <x-form.field label="E-mail" for="f-email" :required="true" :error="$errors->first('email')">
                <x-form.input id="f-email" name="email" required type="email" value="{{ old('email', $user->email) }}" placeholder="Ex.: maria@email.com" />
            </x-form.field>

            <div class="form-grid form-grid-2">
                <x-form.field label="Telefone" for="f-tel" :error="$errors->first('phone')">
                    <x-form.input id="f-tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="Ex.: (11) 99999-8888" inputmode="tel" />
                </x-form.field>
                <x-form.field label="Nascimento" for="f-nasc" :error="$errors->first('birthdate')">
                    <x-form.input id="f-nasc" name="birthdate" type="date" value="{{ old('birthdate', $user->birthdate?->format('Y-m-d')) }}" />
                </x-form.field>
            </div>
        </div>

        <x-form.actions :cancelUrl="route('perfil')" submitLabel="Salvar alterações" submitIcon="save" />
    </form>
</div>
@endsection
