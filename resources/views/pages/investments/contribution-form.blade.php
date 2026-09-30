@extends('layouts.app')
@section('title', 'Novo Aporte')
@section('breadcrumb', 'Investimentos / Novo Aporte')
@section('nav-active', 'investimentos')

@section('content')
<div class="max-w-4xl mx-auto w-full">
    <x-form.header
        title="Novo aporte / rendimento"
        subtitle="Aportes saem da conta escolhida; rendimentos atualizam o valor do ativo."
        :backUrl="route('investimentos')"
        backLabel="Voltar para investimentos"
        icon="query_stats"
        iconBg="linear-gradient(135deg,#EFF6FF,#DBEAFE)"
        iconColor="#2563EB" />

    <form method="POST" action="{{ route('investimentos.aportes.store') }}" class="form-card tint-blue">
        @csrf
        <div class="form-grid">
            <div class="form-grid form-grid-2">
                <x-form.field label="Carteira" for="f-cart" :required="true" :error="$errors->first('portfolio_id')">
                    <x-form.select id="f-cart" name="portfolio_id">
                        <option value="">Selecione a carteira</option>
                        @foreach($portfolios as $p)<option value="{{ $p->id }}" {{ (string)old('portfolio_id', $selected) === (string)$p->id ? 'selected' : '' }}>{{ $p->name }}</option>@endforeach
                    </x-form.select>
                </x-form.field>
                <x-form.field label="Tipo" for="f-tipo" :required="true" :error="$errors->first('kind')">
                    <x-form.select id="f-tipo" name="kind">
                        <option value="aporte" {{ old('kind', 'aporte') === 'aporte' ? 'selected' : '' }}>Aporte (sai da conta)</option>
                        <option value="rendimento" {{ old('kind') === 'rendimento' ? 'selected' : '' }}>Rendimento</option>
                    </x-form.select>
                </x-form.field>
            </div>
            <div class="form-grid form-grid-2">
                <x-form.field label="Conta de origem" for="f-conta" hint="Obrigatória p/ aporte." :error="$errors->first('account_id')">
                    <x-form.select id="f-conta" name="account_id">
                        <option value="">Selecione a conta</option>
                        @foreach($contas as $c)<option value="{{ $c->id }}" {{ (string)old('account_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
                    </x-form.select>
                </x-form.field>
                <x-form.field label="Ativo" for="f-ativo" hint="Obrigatório p/ rendimento." :error="$errors->first('asset_id')">
                    <x-form.select id="f-ativo" name="asset_id">
                        <option value="">Selecione o ativo</option>
                        @foreach($ativos as $a)<option value="{{ $a->id }}" data-portfolio="{{ $a->portfolio_id }}" {{ (string)old('asset_id') === (string)$a->id ? 'selected' : '' }}>{{ $a->portfolio->name ?? '' }} · {{ $a->name }} ({{ $a->code }})</option>@endforeach
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
            <x-form.field label="Nota" for="f-nota">
                <x-form.input id="f-nota" name="note" value="{{ old('note') }}" placeholder="Opcional" />
            </x-form.field>
        </div>
        <x-form.actions :cancelUrl="route('investimentos')" submitLabel="Registrar" submitIcon="add_circle" color="blue" />
    </form>
</div>
@endsection
