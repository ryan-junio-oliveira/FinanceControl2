@extends('layouts.app')
@section('title', 'Transferência Interna')
@section('breadcrumb', 'Contas / Transferência')
@section('nav-active', 'contas')

@section('content')
<div class="max-w-4xl mx-auto w-full">
    <x-form.header
        title="Transferência interna"
        subtitle="Mova valores entre contas sem sair do controle."
        :backUrl="route('contas')"
        backLabel="Voltar para contas"
        icon="swap_horiz"
        iconBg="linear-gradient(135deg,#EFF6FF,#DBEAFE)"
        iconColor="#2563EB" />

    <form method="POST" action="{{ route('contas.transfer') }}" class="form-card tint-blue">
        @csrf

        <div class="form-grid">
            <div class="form-grid form-grid-2">
                <x-form.field label="Conta de origem" for="f-de" :required="true" :error="$errors->first('from_account_id')">
                    <x-form.select id="f-de" name="from_account_id">
                        <option value="">Selecione a origem</option>
                        @foreach($contas as $c)
                            <option value="{{ $c->id }}" {{ (string)old('from_account_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </x-form.select>
                </x-form.field>

                <x-form.field label="Conta de destino" for="f-para" :required="true" :error="$errors->first('to_account_id')">
                    <x-form.select id="f-para" name="to_account_id">
                        <option value="">Selecione o destino</option>
                        @foreach($contas as $c)
                            <option value="{{ $c->id }}" {{ (string)old('to_account_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </x-form.select>
                </x-form.field>
            </div>

            <div class="form-grid form-grid-2">
                <x-form.field label="Valor" for="f-valor" :required="true" :error="$errors->first('amount')">
                    <x-form.money id="f-valor" name="amount" required value="{{ old('amount') }}" placeholder="0,00" />
                </x-form.field>

                <x-form.field label="Data" for="f-data" :required="true" :error="$errors->first('occurred_on')">
                    <x-form.input id="f-data" name="occurred_on" type="date" required value="{{ old('occurred_on', now()->toDateString()) }}" />
                </x-form.field>
            </div>

            <div class="form-grid form-grid-2">
                <x-form.field label="Responsável" for="f-membro" :required="true" :error="$errors->first('user_id')">
                    <x-form.select id="f-membro" name="user_id">
                        <option value="">Selecione o responsável</option>
                        @foreach($membros as $m)
                            <option value="{{ $m->id }}" {{ (string)old('user_id', auth()->id()) === (string)$m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                        @endforeach
                    </x-form.select>
                </x-form.field>

                <x-form.field label="Descrição" for="f-desc" hint="Opcional.">
                    <x-form.input id="f-desc" name="description" value="{{ old('description') }}" placeholder="Ex.: Reserva do mês" />
                </x-form.field>
            </div>
        </div>

        <x-form.actions :cancelUrl="route('contas')" submitLabel="Transferir" submitIcon="swap_horiz" color="blue" />
    </form>
</div>
@endsection
