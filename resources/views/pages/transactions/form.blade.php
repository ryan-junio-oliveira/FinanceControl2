@extends('layouts.app')
@section('title', ($transaction ? 'Editar' : 'Novo').' '.($type === 'despesa' ? 'Despesa' : 'Receita'))
@section('breadcrumb', ($type === 'despesa' ? 'Despesas' : 'Receitas').' / '.($transaction ? 'Editar' : 'Novo'))
@section('nav-active', $type === 'despesa' ? 'despesas' : 'receitas')

@section('content')
@php
    $isDespesa = $type === 'despesa';
    $titulo = ($transaction ? 'Editar' : 'Nova').' '.($isDespesa ? 'despesa' : 'receita');
    $action = $transaction ? route('lancamentos.update', $transaction) : route($isDespesa ? 'despesas.store' : 'receitas.store');
    $voltar = route($isDespesa ? 'despesas' : 'receitas', ['mes' => $mes]);
    $val = fn($k, $d = null) => old($k, $transaction?->$k ?? $d);
    $dataOcorrido = old('occurred_on', $transaction?->occurred_on?->format('Y-m-d') ?? now()->toDateString());
    $dataVenc = old('due_on', $transaction?->due_on?->format('Y-m-d') ?? '');
@endphp

<div class="max-w-4xl mx-auto w-full">
    <x-form.header
        :title="$titulo"
        subtitle="Preencha os dados do lançamento."
        :backUrl="$voltar"
        :backLabel="'Voltar para ' . ($isDespesa ? 'despesas' : 'receitas')"
        :icon="$isDespesa ? 'trending_down' : 'trending_up'"
        :iconBg="$isDespesa ? 'linear-gradient(135deg,#FEE2E2,#FECACA)' : 'linear-gradient(135deg,#D1FAE5,#A7F3D0)'"
        :iconColor="$isDespesa ? '#DC2626' : '#059669'" />

    <form method="POST" action="{{ $action }}" class="form-card {{ $isDespesa ? 'tint-danger' : 'tint-success' }}">
        @csrf
        @if($transaction) @method('PATCH') @endif

        <div class="form-grid">
            <x-form.field label="Descrição" for="f-desc" :required="true" :error="$errors->first('description')">
                <x-form.input id="f-desc" name="description" required value="{{ $val('description') }}" placeholder="Ex.: Mercado Central" autocomplete="off" :error="$errors->has('description')" />
            </x-form.field>

            <div class="form-grid form-grid-2">
                <x-form.field label="Valor" for="f-valor" :required="true" :error="$errors->first('amount')">
                    <x-form.money id="f-valor" name="amount" required value="{{ $val('amount') }}" placeholder="0,00" :error="$errors->has('amount')" />
                </x-form.field>

                <x-form.field label="Responsável" for="f-membro" :required="true" :error="$errors->first('user_id')">
                    <x-form.select id="f-membro" name="user_id">
                        <option value="">Selecione o membro</option>
                        @foreach($membros as $m)
                            <option value="{{ $m->id }}" {{ (string)$val('user_id', auth()->id()) === (string)$m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                        @endforeach
                    </x-form.select>
                </x-form.field>
            </div>

            <div class="form-grid form-grid-2">
                <x-form.field label="Categoria" for="f-cat" :error="$errors->first('category_id')">
                    <x-form.select id="f-cat" name="category_id">
                        <option value="">Selecione a categoria</option>
                        @foreach($categorias as $c)
                            <option value="{{ $c->id }}" {{ (string)$val('category_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </x-form.select>
                </x-form.field>

                <x-form.field label="Conta" for="f-conta" :error="$errors->first('account_id')">
                    <x-form.select id="f-conta" name="account_id">
                        <option value="">Selecione a conta</option>
                        @foreach($contas as $c)
                            <option value="{{ $c->id }}" {{ (string)$val('account_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->label }}</option>
                        @endforeach
                    </x-form.select>
                </x-form.field>
            </div>

            <div class="form-grid form-grid-3">
                <x-form.field label="Data" for="f-data" :required="true" :error="$errors->first('occurred_on')">
                    <x-form.input id="f-data" name="occurred_on" type="date" required value="{{ $dataOcorrido }}" />
                </x-form.field>

                <x-form.field label="Vencimento" for="f-venc" :error="$errors->first('due_on')">
                    <x-form.input id="f-venc" name="due_on" type="date" value="{{ $dataVenc }}" />
                </x-form.field>

                <x-form.field label="Situação" for="f-status" :required="true" :error="$errors->first('status')">
                    <x-form.select id="f-status" name="status">
                        @foreach(['pago' => $isDespesa ? 'Paga' : 'Recebida', 'pendente' => 'Pendente', 'agendado' => 'Agendada'] as $v => $l)
                            <option value="{{ $v }}" {{ $val('status', 'pago') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </x-form.select>
                </x-form.field>
            </div>

            @if(!$transaction)
                <x-form.field label="Parcelas" for="f-parc" hint="À vista = 1. Parcelado divide o valor em vencimentos mensais.">
                    <x-form.select id="f-parc" name="installments_total">
                        <option value="1" {{ old('installments_total', '1') === '1' ? 'selected' : '' }}>À vista</option>
                        @for($i = 2; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ (string)old('installments_total') === (string)$i ? 'selected' : '' }}>{{ $i }}x</option>
                        @endfor
                        @foreach([18, 24, 36, 48] as $i)
                            <option value="{{ $i }}" {{ (string)old('installments_total') === (string)$i ? 'selected' : '' }}>{{ $i }}x</option>
                        @endforeach
                    </x-form.select>
                </x-form.field>
            @endif

            <x-form.field label="Observações" for="f-obs">
                <x-form.input id="f-obs" name="notes" value="{{ $val('notes') }}" placeholder="Opcional" />
            </x-form.field>

            <x-form.field label="Lançamento fixo" hint="Fixas se repetem todo mês (ex.: aluguel, salário) e podem ser filtradas nas listagens.">
                <label class="toggle-switch mt-1">
                    <input type="checkbox" name="is_fixed" value="1" {{ $val('is_fixed') ? 'checked' : '' }}>
                    <div class="toggle-track">
                        <div class="toggle-thumb"></div>
                    </div>
                    <span class="toggle-label-text">{{ $isDespesa ? 'Despesa' : 'Receita' }} fixa mensal</span>
                </label>
            </x-form.field>
        </div>

        <x-form.actions :cancelUrl="$voltar" :submitLabel="$transaction ? 'Salvar alterações' : 'Salvar'" :submitIcon="$transaction ? 'save' : 'add_circle'" :color="$isDespesa ? 'danger' : 'success'" />
    </form>
</div>
@endsection
