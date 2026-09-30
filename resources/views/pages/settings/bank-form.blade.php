@extends('layouts.app')
@section('title', 'Anotar Banco')
@section('breadcrumb', 'Configurações / Novo Banco')
@section('nav-active', 'configuracoes')

@section('content')
<div class="max-w-4xl mx-auto w-full">
    <x-form.header
        title="Anotar banco"
        subtitle="Registre os bancos da família como referência para os lançamentos."
        :backUrl="route('configuracoes')"
        backLabel="Voltar para configurações"
        icon="account_balance"
        iconBg="linear-gradient(135deg,#F1F5F9,#E2E8F0)"
        iconColor="#0F172A" />

    <form method="POST" action="{{ route('configuracoes.bancos.store') }}" class="form-card">
        @csrf
        <div class="form-grid">
            <x-form.field label="Banco" for="f-banco" :required="true" :error="$errors->first('bank')">
                <x-form.input id="f-banco" name="bank" required value="{{ old('bank') }}" placeholder="Ex.: Itaú" />
            </x-form.field>
            <x-form.field label="Detalhes" for="f-det" hint="Opcional.">
                <x-form.input id="f-det" name="details" value="{{ old('details') }}" placeholder="Ex.: CC 1234" />
            </x-form.field>
        </div>
        <x-form.actions :cancelUrl="route('configuracoes')" submitLabel="Salvar banco" submitIcon="add_circle" />
    </form>
</div>
@endsection
