@extends('layouts.app')
@section('title', 'Novo Ativo')
@section('breadcrumb', 'Investimentos / Novo Ativo')
@section('nav-active', 'investimentos')

@section('content')
@php $val = fn($k, $d = null) => old($k, $ativo?->$k ?? $d); @endphp
<div class="max-w-4xl mx-auto w-full">
    <x-form.header
        :title="$ativo ? 'Editar ativo' : 'Novo ativo'"
        subtitle="Ex.: CDB de R$ 7.000 rendendo 120% do CDI. Carteira é opcional."
        :backUrl="route('investimentos')"
        backLabel="Voltar para investimentos"
        icon="trending_up"
        iconBg="linear-gradient(135deg,#ECFEFF,#CFFAFE)"
        iconColor="#0891B2" />

    <form method="POST" action="{{ $ativo ? route('investimentos.ativos.update', $ativo) : route('investimentos.ativos.store') }}" class="form-card tint-cyan">
        @csrf
        @if($ativo) @method('PATCH') @endif
        <div class="form-grid">
            <div class="form-grid form-grid-2">
                <x-form.field label="Código" for="f-cod" :required="true" :error="$errors->first('code')">
                    <x-form.input id="f-cod" name="code" required value="{{ $val('code') }}" placeholder="Ex.: CDB Inter" />
                </x-form.field>
                <x-form.field label="Nome" for="f-nome" :required="true" :error="$errors->first('name')">
                    <x-form.input id="f-nome" name="name" required value="{{ $val('name') }}" placeholder="Ex.: CDB Banco Inter" />
                </x-form.field>
            </div>
            <div class="form-grid form-grid-2">
                <x-form.field label="Classe" for="f-classe" :required="true" :error="$errors->first('kind')">
                    <x-form.select id="f-classe" name="kind">
                        <option value="">Selecione a classe</option>
                        @foreach(['renda_fixa' => 'Renda Fixa', 'fii' => 'FII', 'acao' => 'Ação', 'etf' => 'ETF', 'previdencia' => 'Previdência'] as $v => $l)<option value="{{ $v }}" {{ $val('kind') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                    </x-form.select>
                </x-form.field>
                <x-form.field label="Valor atual" for="f-valor" :required="true" :error="$errors->first('current_value')">
                    <x-form.money id="f-valor" name="current_value" required value="{{ $val('current_value', '0') }}" placeholder="0,00" />
                </x-form.field>
            </div>
            <div class="form-grid form-grid-2">
                <x-form.field label="Rende quanto?" for="f-yield" hint="Ex.: 120 (% do índice)." :error="$errors->first('yield_percent')">
                    <x-form.money id="f-yield" name="yield_percent" value="{{ $val('yield_percent') }}" placeholder="0,00" prefix="%" />
                </x-form.field>
                <x-form.field label="Indexador" for="f-base" :error="$errors->first('yield_base')">
                    <x-form.select id="f-base" name="yield_base">
                        @foreach(['cdi' => 'CDI', 'selic' => 'Selic', 'ipca' => 'IPCA', 'prefixado' => 'Prefixado (% a.a.)'] as $v => $l)<option value="{{ $v }}" {{ $val('yield_base', 'cdi') === $v ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                    </x-form.select>
                </x-form.field>
            </div>
            <div class="form-grid form-grid-2">
                <x-form.field label="Instituição" for="f-inst">
                    <x-form.input id="f-inst" name="institution" value="{{ $val('institution') }}" placeholder="Ex.: Banco Inter" />
                </x-form.field>
                <x-form.field label="Carteira (opcional)" for="f-cart" hint="Se vazio, entra na carteira geral." :error="$errors->first('portfolio_id')">
                    <x-form.select id="f-cart" name="portfolio_id">
                        <option value="">Sem carteira (geral)</option>
                        @foreach($portfolios as $p)<option value="{{ $p->id }}" {{ (string)$val('portfolio_id', $selected ?? null) === (string)$p->id ? 'selected' : '' }}>{{ $p->name }}</option>@endforeach
                    </x-form.select>
                </x-form.field>
            </div>
        </div>
        <x-form.actions :cancelUrl="route('investimentos')" :submitLabel="$ativo ? 'Salvar alterações' : 'Salvar ativo'" :submitIcon="$ativo ? 'save' : 'add_circle'" color="cyan" />
    </form>
</div>
@endsection
