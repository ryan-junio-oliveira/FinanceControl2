@extends('layouts.app')
@section('title', 'Configurar Mesada')
@section('breadcrumb', 'Família / Mesada')
@section('nav-active', 'familia')

@section('content')
<div class="max-w-4xl mx-auto w-full">
    <x-form.header
        title="Configurar mesada"
        subtitle="Defina valor, frequência e dia do repasse por pessoa."
        :backUrl="route('familia')"
        backLabel="Voltar para a família"
        icon="calendar_clock"
        iconBg="linear-gradient(135deg,#FFFBEB,#FEF3C7)"
        iconColor="#D97706" />

    <form method="POST" action="{{ route('familia.mesadas.store') }}" class="form-card">
        @csrf
        <div class="form-grid">
            <x-form.field label="Pessoa" for="f-membro" :required="true" :error="$errors->first('user_id')">
                <x-form.select id="f-membro" name="user_id">
                    <option value="">Selecione a pessoa</option>
                    @foreach($membros as $m)<option value="{{ $m->id }}" {{ (string)old('user_id', $selected) === (string)$m->id ? 'selected' : '' }}>{{ $m->name }}</option>@endforeach
                </x-form.select>
            </x-form.field>
            <div class="form-grid form-grid-3">
                <x-form.field label="Valor" for="f-valor" :required="true" :error="$errors->first('amount')">
                    <x-form.money id="f-valor" name="amount" required value="{{ old('amount') }}" placeholder="0,00" />
                </x-form.field>
                <x-form.field label="Frequência" for="f-freq" :required="true" :error="$errors->first('frequency')">
                    <x-form.select id="f-freq" name="frequency">
                        <option value="mensal" {{ old('frequency', 'mensal') === 'mensal' ? 'selected' : '' }}>Mensal</option>
                        <option value="semanal" {{ old('frequency') === 'semanal' ? 'selected' : '' }}>Semanal</option>
                    </x-form.select>
                </x-form.field>
                <x-form.field label="Dia do repasse" for="f-dia" :required="true" :error="$errors->first('payday')">
                    <x-form.input id="f-dia" name="payday" type="number" min="0" max="28" required value="{{ old('payday', '5') }}" placeholder="5" />
                </x-form.field>
            </div>
        </div>
        <x-form.actions :cancelUrl="route('familia')" submitLabel="Salvar mesada" submitIcon="save" />
    </form>
</div>
@endsection
