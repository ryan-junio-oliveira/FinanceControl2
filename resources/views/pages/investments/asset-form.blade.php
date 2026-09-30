@extends('layouts.app')
@section('title', 'Novo Ativo')
@section('breadcrumb', 'Investimentos / Novo Ativo')
@section('nav-active', 'investimentos')

@section('content')
<div class="max-w-4xl mx-auto w-full">
    <x-form.header
        title="Novo ativo"
        subtitle="Adicione um ativo a uma carteira da família."
        :backUrl="route('investimentos')"
        backLabel="Voltar para investimentos"
        icon="trending_up"
        iconBg="linear-gradient(135deg,#EFF6FF,#DBEAFE)"
        iconColor="#2563EB" />

    <form method="POST" action="{{ route('investimentos.ativos.store') }}" class="form-card tint-blue">
        @csrf
        <div class="form-grid">
            <x-form.field label="Carteira" for="f-cart" :required="true" :error="$errors->first('portfolio_id')">
                <x-form.select id="f-cart" name="portfolio_id">
                    <option value="">Selecione a carteira</option>
                    @foreach($portfolios as $p)<option value="{{ $p->id }}" {{ (string)old('portfolio_id', $selected) === (string)$p->id ? 'selected' : '' }}>{{ $p->name }}</option>@endforeach
                </x-form.select>
            </x-form.field>
            <div class="form-grid form-grid-2">
                <x-form.field label="Código" for="f-cod" :required="true" :error="$errors->first('code')">
                    <x-form.input id="f-cod" name="code" required value="{{ old('code') }}" placeholder="Ex.: HGLG11" />
                </x-form.field>
                <x-form.field label="Nome" for="f-nome" :required="true" :error="$errors->first('name')">
                    <x-form.input id="f-nome" name="name" required value="{{ old('name') }}" placeholder="Ex.: CSHG Logística FII" />
                </x-form.field>
            </div>
            <div class="form-grid form-grid-2">
                <x-form.field label="Classe" for="f-classe" :required="true" :error="$errors->first('kind')">
                    <x-form.select id="f-classe" name="kind">
                        <option value="">Selecione a classe</option>
                        @foreach(['renda_fixa' => 'Renda Fixa', 'fii' => 'FII', 'acao' => 'Ação', 'etf' => 'ETF', 'previdencia' => 'Previdência'] as $v => $l)<option value="{{ $v }}" {{ old('kind') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                    </x-form.select>
                </x-form.field>
                <x-form.field label="Valor atual" for="f-valor" :required="true" :error="$errors->first('current_value')">
                    <x-form.money id="f-valor" name="current_value" required value="{{ old('current_value', '0') }}" placeholder="0,00" />
                </x-form.field>
            </div>
            <div class="form-grid form-grid-2">
                <x-form.field label="Instituição" for="f-inst">
                    <x-form.input id="f-inst" name="institution" value="{{ old('institution') }}" placeholder="Ex.: XP Invest" />
                </x-form.field>
                <x-form.field label="Rentabilidade" for="f-rent">
                    <x-form.input id="f-rent" name="profitability" value="{{ old('profitability') }}" placeholder="Ex.: +8,2%" />
                </x-form.field>
            </div>
        </div>
        <x-form.actions :cancelUrl="route('investimentos')" submitLabel="Salvar ativo" submitIcon="add_circle" color="blue" />
    </form>
</div>
@endsection
