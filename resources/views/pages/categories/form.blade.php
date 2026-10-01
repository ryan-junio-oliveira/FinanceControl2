@extends('layouts.app')
@section('title', ($category ? 'Editar' : 'Nova').' Categoria')
@section('breadcrumb', 'Categorias / '.($category ? 'Editar' : 'Nova'))
@section('nav-active', 'categorias')

@section('content')
@php
    $action = $category ? route('categorias.update', $category) : route('categorias.store');
    $val = fn($k, $d = null) => old($k, $category?->$k ?? $d);
@endphp
<div class="max-w-4xl mx-auto w-full">
    <x-form.header
        :title="$category ? 'Editar categoria' : 'Nova categoria'"
        subtitle="Organize receitas e despesas por categoria."
        :backUrl="route('categorias')"
        backLabel="Voltar para categorias"
        icon="category"
        iconBg="linear-gradient(135deg,#F5F3FF,#EDE9FE)"
        iconColor="#7C3AED" />

    <form method="POST" action="{{ $action }}" class="form-card tint-violet">
        @csrf
        @if($category) @method('PATCH') @endif

        <div class="form-grid">
            <x-form.field label="Nome" for="f-nome" :required="true" :error="$errors->first('name')">
                <x-form.input id="f-nome" name="name" required value="{{ $val('name') }}" placeholder="Ex.: Alimentação" />
            </x-form.field>

            <div class="form-grid form-grid-2">
                <x-form.field label="Tipo" for="f-tipo" :required="true" :error="$errors->first('type')">
                    @if($category)
                        <div class="flex items-center h-12"><x-tipo-badge :type="$category->type" /></div>
                        <p class="fld-hint">O tipo não pode mudar após a criação.</p>
                    @else
                        <x-form.select id="f-tipo" name="type">
                            <option value="despesa" {{ old('type', 'despesa') === 'despesa' ? 'selected' : '' }}>Despesa</option>
                            <option value="receita" {{ old('type') === 'receita' ? 'selected' : '' }}>Receita</option>
                        </x-form.select>
                    @endif
                </x-form.field>
                <x-form.field label="Ícone" for="f-icone" hint="Material Symbols (ex.: restaurant, home).">
                    <x-form.input id="f-icone" name="icon" value="{{ $val('icon', 'tag') }}" placeholder="Ex.: shopping_cart" />
                </x-form.field>
            </div>

            @if($category)
                <x-form.check name="archived" value="1" :checked="(bool) old('archived', $category->archived)" label="Arquivar categoria" hint="Arquivadas somem dos lançamentos, mas mantêm o histórico." />
            @endif
        </div>

        <x-form.actions :cancelUrl="route('categorias')" :submitLabel="$category ? 'Salvar alterações' : 'Salvar categoria'" :submitIcon="$category ? 'save' : 'add_circle'" color="violet" />
    </form>
</div>
@endsection
