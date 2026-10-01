@extends('layouts.app')
@section('title', ($account ? 'Editar' : 'Nova').' Conta')
@section('breadcrumb', 'Contas / '.($account ? 'Editar' : 'Nova'))
@section('nav-active', 'contas')

@section('content')
@php
    $action = $account ? route('contas.update', $account) : route('contas.store');
    $val = fn($k, $d = null) => old($k, $account?->$k ?? $d);
    $tiposKind = ['corrente'=>'Corrente','poupanca'=>'Poupança','digital'=>'Digital','investimento'=>'Investimento','carteira'=>'Carteira'];
@endphp

<div class="max-w-4xl mx-auto w-full">
    <x-form.header
        :title="$account ? 'Editar conta' : 'Nova conta'"
        subtitle="Vincule cada conta ao seu banco (a cor vem do banco)."
        :backUrl="route('contas')"
        backLabel="Voltar para contas"
        icon="account_balance"
        iconBg="linear-gradient(135deg,#EFF6FF,#DBEAFE)"
        iconColor="#2563EB" />

    <form method="POST" action="{{ $action }}" class="form-card tint-blue">
        @csrf
        @if($account) @method('PATCH') @endif

        <div class="form-grid">
            <x-form.field label="Banco" for="f-banco" :required="true" :error="$errors->first('bank_id')" hint="A conta usa a cor do banco.">
                <div class="flex items-center gap-3">
                    <span id="bank-swatch" class="w-11 h-11 rounded-xl shadow-sm shrink-0 border border-slate-200"
                        style="background:#0F172A" title="Cor do banco"></span>
                    <x-form.select id="f-banco" name="bank_id" class="flex-1">
                        <option value="">Selecione o banco</option>
                        @foreach($bancos as $b)
                            <option value="{{ $b->id }}" data-cor="{{ $b->color ?? '#0F172A' }}" {{ (string)$val('bank_id') === (string)$b->id ? 'selected' : '' }}>{{ $b->label }}</option>
                        @endforeach
                    </x-form.select>
                </div>
            </x-form.field>

            <x-form.field label="Nome da conta" for="f-nome" :required="true" :error="$errors->first('name')">
                <x-form.input id="f-nome" name="name" required value="{{ $val('name') }}" placeholder="Ex.: Itaú Conjunta" />
            </x-form.field>

            <div class="form-grid form-grid-2">
                <x-form.field label="Tipo de conta" for="f-kind" :required="true" :error="$errors->first('kind')">
                    <x-form.select id="f-kind" name="kind">
                        <option value="">Selecione o tipo</option>
                        @foreach($tiposKind as $v => $l)
                            <option value="{{ $v }}" {{ $val('kind', 'corrente') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </x-form.select>
                </x-form.field>

                <x-form.field label="Saldo inicial" for="f-saldo" :required="true" :error="$errors->first('initial_balance')">
                    <x-form.money id="f-saldo" name="initial_balance" required value="{{ $val('initial_balance', '0') }}" placeholder="0,00" />
                </x-form.field>
            </div>

            @if($account)
                <x-form.check name="active" value="1" :checked=" (bool) old('active', $account->active)" label="Conta ativa" hint="Aparece nos lançamentos e transferências." />
            @endif
        </div>

        <x-form.actions :cancelUrl="route('contas')" :submitLabel="$account ? 'Salvar alterações' : 'Salvar conta'" :submitIcon="$account ? 'save' : 'add_circle'" color="blue" />
    </form>
</div>

@push('scripts')
<script>
// Mostra a cor do banco selecionado (a conta herda essa cor).
const bankSel = document.getElementById('f-banco');
const bankSwatch = document.getElementById('bank-swatch');
function atualizaSwatch() {
    const opt = bankSel.options[bankSel.selectedIndex];
    bankSwatch.style.background = (opt && opt.dataset.cor) ? opt.dataset.cor : '#0F172A';
}
bankSel?.addEventListener('change', atualizaSwatch);
atualizaSwatch();
</script>
@endpush
@endsection
