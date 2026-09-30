@extends('layouts.app')
@section('title', ($conta ? 'Editar' : 'Nova').' Conta')
@section('breadcrumb', 'Contas / '.($conta ? 'Editar' : 'Nova'))
@section('nav-active', 'contas')

@section('content')
@php
    $action = $conta ? route('contas.update', $conta) : route('contas.store');
    $val = fn($k, $d = null) => old($k, $conta?->$k ?? $d);
    $corAtual = old('color', $conta?->color ?? '#059669');
    $cores = ['#059669','#0F172A','#820AD1','#EA580C','#EC7000','#CC092F','#EC0000','#005CA9','#FBC105','#21C25E','#3B82F6','#EC4899'];
    $tiposKind = ['corrente'=>'Corrente','poupanca'=>'Poupança','digital'=>'Digital','investimento'=>'Investimento','carteira'=>'Carteira'];
@endphp

<div class="max-w-4xl mx-auto w-full">
    <x-form.header
        :title="$conta ? 'Editar conta' : 'Nova conta'"
        subtitle="Cadastre bancos, carteiras e caixinhas."
        :backUrl="route('contas')"
        backLabel="Voltar para contas"
        icon="account_balance"
        iconBg="linear-gradient(135deg,#EFF6FF,#DBEAFE)"
        iconColor="#2563EB" />

    <form method="POST" action="{{ $action }}" class="form-card tint-blue">
        @csrf
        @if($conta) @method('PATCH') @endif

        <div class="form-grid">
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

            <div>
                <p class="form-section-title">Cor da conta</p>
                <p class="fld-hint" style="margin-top:.25rem;margin-bottom:.75rem">Cartões vinculados a esta conta herdam esta cor.</p>
                <div class="flex flex-wrap items-center gap-2.5">
                    @foreach($cores as $cor)
                    <button type="button" data-cor="{{ $cor }}"
                        class="color-swatch {{ strtolower($corAtual) === strtolower($cor) ? 'selected' : '' }}"
                        style="background:{{ $cor }}" title="{{ $cor }}" aria-label="Cor {{ $cor }}">
                    </button>
                    @endforeach
                    <label class="w-9 h-9 rounded-xl overflow-hidden cursor-pointer ring-2 ring-offset-2 ring-slate-200 hover:ring-slate-300 transition" title="Cor personalizada">
                        <input id="f-cor" name="color" type="color" value="{{ $corAtual }}" class="w-12 h-12 -ml-1.5 -mt-1.5 cursor-pointer">
                    </label>
                </div>
                @error('color')<p class="fld-msg-error">{{ $message }}</p>@enderror
            </div>

            @if($conta)
                <x-form.check name="active" value="1" :checked=" (bool) old('active', $conta->active)" label="Conta ativa" hint="Aparece nos lançamentos e transferências." />
            @endif
        </div>

        <x-form.actions :cancelUrl="route('contas')" :submitLabel="$conta ? 'Salvar alterações' : 'Salvar conta'" :submitIcon="$conta ? 'save' : 'add_circle'" color="blue" />
    </form>
</div>

@push('scripts')
<script>
document.querySelectorAll('.color-swatch').forEach(b => b.addEventListener('click', () => {
    document.getElementById('f-cor').value = b.dataset.cor;
    document.querySelectorAll('.color-swatch').forEach(x => x.classList.remove('selected'));
    b.classList.add('selected');
}));
document.getElementById('f-cor')?.addEventListener('input', e => {
    document.querySelectorAll('.color-swatch').forEach(x => {
        const on = x.dataset.cor.toLowerCase() === e.target.value.toLowerCase();
        x.classList.toggle('selected', on);
    });
});
</script>
@endpush
@endsection
