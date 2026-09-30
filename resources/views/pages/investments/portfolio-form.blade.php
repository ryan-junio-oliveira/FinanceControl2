@extends('layouts.app')
@section('title', 'Nova Carteira')
@section('breadcrumb', 'Investimentos / Nova Carteira')
@section('nav-active', 'investimentos')

@section('content')
<div class="max-w-4xl mx-auto w-full">
    <x-form.header
        title="Nova carteira"
        subtitle="Agrupe ativos por objetivo (reserva, estudos, futuro)."
        :backUrl="route('investimentos')"
        backLabel="Voltar para investimentos"
        icon="savings"
        iconBg="linear-gradient(135deg,#EFF6FF,#DBEAFE)"
        iconColor="#2563EB" />

    <form method="POST" action="{{ route('investimentos.carteiras.store') }}" class="form-card tint-blue">
        @csrf
        <div class="form-grid">
            <x-form.field label="Nome" for="f-nome" :required="true" :error="$errors->first('name')">
                <x-form.input id="f-nome" name="name" required value="{{ old('name') }}" placeholder="Ex.: Reserva de Emergência" />
            </x-form.field>
            <div class="form-grid form-grid-2">
                <x-form.field label="Tipo" for="f-tipo" :required="true" :error="$errors->first('kind')">
                    <x-form.select id="f-tipo" name="kind">
                        <option value="">Selecione o tipo</option>
                        @foreach(['reserva' => 'Reserva', 'estudos' => 'Educação', 'futuro' => 'Futuro', 'livre' => 'Livre'] as $v => $l)<option value="{{ $v }}" {{ old('kind') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                    </x-form.select>
                </x-form.field>
                <x-form.field label="Meta" for="f-meta" :error="$errors->first('target_amount')">
                    <x-form.money id="f-meta" name="target_amount" value="{{ old('target_amount') }}" placeholder="0,00" />
                </x-form.field>
            </div>
            <x-form.field label="Objetivo" for="f-obj">
                <x-form.input id="f-obj" name="objective" value="{{ old('objective') }}" placeholder="Ex.: 6 meses de despesas" />
            </x-form.field>
        </div>
        <x-form.actions :cancelUrl="route('investimentos')" submitLabel="Salvar carteira" submitIcon="add_circle" color="blue" />
    </form>
</div>
@endsection
