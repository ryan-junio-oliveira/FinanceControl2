@extends('layouts.app')
@section('title', 'Nova Compra no Cartão')
@section('breadcrumb', 'Cartões / Nova Compra')
@section('nav-active', 'cartoes')

@section('content')
<div class="max-w-4xl mx-auto w-full">
    <x-form.header
        title="Nova compra no cartão"
        subtitle="A compra entra na fatura em aberto do cartão."
        :backUrl="route('cartoes')"
        backLabel="Voltar para cartões"
        icon="add_shopping_cart"
        iconBg="linear-gradient(135deg,#FFF7ED,#FFEDD5)"
        iconColor="#EA580C" />

    <form method="POST" action="{{ route('cartoes.itens.store') }}" class="form-card tint-orange">
        @csrf
        <div class="form-grid">
            <x-form.field label="Cartão" for="f-cartao" :required="true" :error="$errors->first('credit_card_id')">
                <x-form.select id="f-cartao" name="credit_card_id">
                    <option value="">Selecione o cartão</option>
                    @foreach($cartoes as $c)<option value="{{ $c->id }}" {{ (string)old('credit_card_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
                </x-form.select>
            </x-form.field>

            <x-form.field label="Descrição" for="f-desc" :required="true" :error="$errors->first('description')">
                <x-form.input id="f-desc" name="description" required value="{{ old('description') }}" placeholder="Ex.: Mercado Central" />
            </x-form.field>

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
                        <option value="">Selecione quem comprou</option>
                        @foreach($membros as $m)<option value="{{ $m->id }}" {{ (string)old('user_id', auth()->id()) === (string)$m->id ? 'selected' : '' }}>{{ $m->name }}</option>@endforeach
                    </x-form.select>
                </x-form.field>
                <x-form.field label="Categoria" for="f-cat" :error="$errors->first('category_id')">
                    <x-form.select id="f-cat" name="category_id">
                        <option value="">Selecione a categoria</option>
                        @foreach($categorias as $c)<option value="{{ $c->id }}" {{ (string)old('category_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
                    </x-form.select>
                </x-form.field>
            </div>

            <x-form.field label="Parcelas" for="f-parc" hint="À vista = 1. Parcelado gera uma parcela por mês na fatura.">
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
        </div>

        <x-form.actions :cancelUrl="route('cartoes')" submitLabel="Lançar compra" submitIcon="add_shopping_cart" color="orange" />
    </form>
</div>
@endsection
