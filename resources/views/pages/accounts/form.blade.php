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

<div class="max-w-2xl mx-auto w-full">
    <a href="{{ route('contas') }}" class="back-link">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        Voltar para contas
    </a>

    <div class="flex items-center gap-3 mt-3 mb-6">
        <div class="w-11 h-11 rounded-lg grid place-items-center"
            style="background: linear-gradient(135deg,#EFF6FF,#DBEAFE);">
            <span class="material-symbols-outlined text-blue-600 text-[22px]"
                style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">account_balance</span>
        </div>
        <div>
            <h1 class="text-[24px] font-extrabold tracking-tight">{{ $conta ? 'Editar conta' : 'Nova conta' }}</h1>
            <p class="text-[13px] text-gray-400 mt-0.5 font-medium">Cadastre bancos, carteiras e caixinhas da sua conta.</p>
        </div>
    </div>

    <form method="POST" action="{{ $action }}" class="form-card space-y-5">
        @csrf
        @if($conta) @method('PATCH') @endif

        {{-- Nome --}}
        <div class="float-field {{ $errors->has('name') ? 'error' : '' }}">
            <input id="f-nome" name="name" required value="{{ $val('name') }}" placeholder=" ">
            <label for="f-nome">Nome da conta *</label>
            @error('name')<p class="text-[12px] text-red-500 font-semibold mt-1.5">{{ $message }}</p>@enderror
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            {{-- Agência --}}
            <div class="float-field {{ $errors->has('agency') ? 'error' : '' }}">
                <input id="f-ag" name="agency" value="{{ $val('agency') }}" placeholder=" ">
                <label for="f-ag">Agência</label>
                @error('agency')<p class="text-[12px] text-red-500 font-semibold mt-1.5">{{ $message }}</p>@enderror
            </div>
            {{-- Número --}}
            <div class="float-field {{ $errors->has('number') ? 'error' : '' }}">
                <input id="f-num" name="number" value="{{ $val('number') }}" placeholder=" ">
                <label for="f-num">Número da conta</label>
                @error('number')<p class="text-[12px] text-red-500 font-semibold mt-1.5">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            {{-- Tipo --}}
            <div class="float-field {{ $errors->has('kind') ? 'error' : '' }}">
                <select id="f-kind" name="kind">
                    <option value="">Selecione o tipo</option>
                    @foreach($tiposKind as $v => $l)
                        <option value="{{ $v }}" {{ $val('kind', 'corrente') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <label for="f-kind">Tipo de conta *</label>
                @error('kind')<p class="text-[12px] text-red-500 font-semibold mt-1.5">{{ $message }}</p>@enderror
            </div>
            {{-- Saldo inicial --}}
            <div>
                <p class="text-[11px] font-bold uppercase tracking-widest text-gray-400 mb-1.5">Saldo inicial (R$) *</p>
                <div class="input-group {{ $errors->has('initial_balance') ? 'error' : '' }}">
                    <span class="prefix">R$</span>
                    <input id="f-saldo" name="initial_balance" required inputmode="decimal"
                        value="{{ $val('initial_balance', '0') }}" placeholder="0,00" class="num">
                </div>
                @error('initial_balance')<p class="text-[12px] text-red-500 font-semibold mt-1.5">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Seletor de cor --}}
        <div>
            <p class="text-[11px] font-bold uppercase tracking-widest text-gray-400 mb-3">Cor da conta</p>
            <p class="text-[12px] text-gray-400 -mt-2 mb-3">Cartões vinculados a esta conta herdam esta cor.</p>
            <div class="flex flex-wrap items-center gap-2.5">
                @foreach($cores as $cor)
                <button type="button" data-cor="{{ $cor }}"
                    class="color-swatch {{ strtolower($corAtual) === strtolower($cor) ? 'selected' : '' }}"
                    style="background:{{ $cor }}" title="{{ $cor }}">
                </button>
                @endforeach
                <label class="w-9 h-9 rounded-lg overflow-hidden cursor-pointer ring-2 ring-offset-2 ring-gray-500 hover:ring-gray-500 transition" title="Cor personalizada">
                    <input id="f-cor" name="color" type="color" value="{{ $corAtual }}" class="w-12 h-12 -ml-1.5 -mt-1.5 cursor-pointer">
                </label>
            </div>
            @error('color')<p class="text-[12px] text-red-500 font-semibold mt-2">{{ $message }}</p>@enderror
        </div>

        {{-- Conta ativa (só edit) --}}
        @if($conta)
        <label class="toggle-switch">
            <input type="checkbox" name="active" value="1" {{ old('active', $conta->active) ? 'checked' : '' }}>
            <div class="toggle-track">
                <div class="toggle-thumb"></div>
            </div>
            <span class="toggle-label-text">Conta ativa (aparece nos lançamentos)</span>
        </label>
        @endif

        {{-- Botões --}}
        <div class="flex justify-end gap-3 pt-2 border-t border-gray-500">
            <a href="{{ route('contas') }}" class="btn-ghost">
                <span class="material-symbols-outlined text-[17px]">close</span> Cancelar
            </a>
            <button class="btn-secondary">
                <span class="material-symbols-outlined text-[17px]">{{ $conta ? 'save' : 'add_circle' }}</span>
                {{ $conta ? 'Salvar alterações' : 'Salvar conta' }}
            </button>
        </div>
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
