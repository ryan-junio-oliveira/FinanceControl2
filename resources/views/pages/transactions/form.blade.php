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

<div class="max-w-2xl mx-auto w-full">
    <a href="{{ $voltar }}" class="back-link">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        Voltar para {{ $isDespesa ? 'despesas' : 'receitas' }}
    </a>

    <div class="flex items-center gap-3 mt-3 mb-6">
        <div class="w-11 h-11 rounded-lg grid place-items-center text-white shadow-sm
            {{ $isDespesa ? '' : '' }}"
            style="background: {{ $isDespesa ? 'linear-gradient(135deg,#FEE2E2,#FCA5A5)' : 'linear-gradient(135deg,#D1FAE5,#6EE7B7)' }}">
            <span class="material-symbols-outlined text-[22px]"
                style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24; color: {{ $isDespesa ? '#DC2626' : '#059669' }}">
                {{ $isDespesa ? 'trending_down' : 'trending_up' }}
            </span>
        </div>
        <div>
            <h1 class="text-[24px] font-extrabold tracking-tight capitalize">{{ $titulo }}</h1>
            <p class="text-[13px] text-gray-400 mt-0.5 font-medium">Preencha os dados do lançamento.</p>
        </div>
    </div>

    <form method="POST" action="{{ $action }}" class="form-card space-y-5">
        @csrf
        @if($transaction) @method('PATCH') @endif

        {{-- Descrição --}}
        <div class="float-field {{ $errors->has('description') ? 'error' : '' }}">
            <input id="f-desc" name="description" required value="{{ $val('description') }}"
                placeholder=" " autocomplete="off">
            <label for="f-desc">Descrição *</label>
            @error('description')
            <p class="text-[12px] text-red-500 font-semibold mt-1.5 flex items-center gap-1">
                <span class="material-symbols-outlined text-[13px]">error</span>{{ $message }}
            </p>
            @enderror
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            {{-- Valor --}}
            <div>
                <p class="text-[11px] font-bold uppercase tracking-widest text-gray-400 mb-1.5">Valor (R$) *</p>
                <div class="input-group {{ $errors->has('amount') ? 'error' : '' }}">
                    <span class="prefix">R$</span>
                    <input id="f-valor" name="amount" required inputmode="decimal"
                        value="{{ $val('amount') }}" placeholder="0,00" class="num">
                </div>
                @error('amount')<p class="text-[12px] text-red-500 font-semibold mt-1.5">{{ $message }}</p>@enderror
            </div>

            {{-- Membro --}}
            <div class="float-field {{ $errors->has('user_id') ? 'error' : '' }}">
                <select id="f-membro" name="user_id">
                    <option value="">Selecione o membro</option>
                    @foreach($membros as $m)
                        <option value="{{ $m->id }}" {{ (string)$val('user_id', auth()->id()) === (string)$m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                    @endforeach
                </select>
                <label for="f-membro">Responsável *</label>
                @error('user_id')<p class="text-[12px] text-red-500 font-semibold mt-1.5">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            {{-- Categoria --}}
            <div class="float-field {{ $errors->has('category_id') ? 'error' : '' }}">
                <select id="f-cat" name="category_id">
                    <option value="">Selecione a categoria</option>
                    @foreach($categorias as $c)
                        <option value="{{ $c->id }}" {{ (string)$val('category_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
                <label for="f-cat">Categoria</label>
                @error('category_id')<p class="text-[12px] text-red-500 font-semibold mt-1.5">{{ $message }}</p>@enderror
            </div>

            {{-- Conta --}}
            <div class="float-field {{ $errors->has('account_id') ? 'error' : '' }}">
                <select id="f-conta" name="account_id">
                    <option value="">Selecione a conta</option>
                    @foreach($contas as $c)
                        <option value="{{ $c->id }}" {{ (string)$val('account_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->label }}</option>
                    @endforeach
                </select>
                <label for="f-conta">Conta</label>
                @error('account_id')<p class="text-[12px] text-red-500 font-semibold mt-1.5">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid sm:grid-cols-3 gap-4">
            {{-- Data --}}
            <div class="float-field {{ $errors->has('occurred_on') ? 'error' : '' }}">
                <input id="f-data" name="occurred_on" type="date" required value="{{ $dataOcorrido }}" placeholder=" ">
                <label for="f-data">Data *</label>
                @error('occurred_on')<p class="text-[12px] text-red-500 font-semibold mt-1.5">{{ $message }}</p>@enderror
            </div>

            {{-- Vencimento --}}
            <div class="float-field {{ $errors->has('due_on') ? 'error' : '' }}">
                <input id="f-venc" name="due_on" type="date" value="{{ $dataVenc }}" placeholder=" ">
                <label for="f-venc">Vencimento</label>
                @error('due_on')<p class="text-[12px] text-red-500 font-semibold mt-1.5">{{ $message }}</p>@enderror
            </div>

            {{-- Status --}}
            <div class="float-field {{ $errors->has('status') ? 'error' : '' }}">
                <select id="f-status" name="status">
                    @foreach(['pago' => $isDespesa ? 'Paga' : 'Recebida', 'pendente' => 'Pendente', 'agendado' => 'Agendada'] as $v => $l)
                        <option value="{{ $v }}" {{ $val('status', 'pago') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <label for="f-status">Situação *</label>
                @error('status')<p class="text-[12px] text-red-500 font-semibold mt-1.5">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Observações --}}
        <div class="float-field">
            <input id="f-obs" name="notes" value="{{ $val('notes') }}" placeholder=" ">
            <label for="f-obs">Observações (opcional)</label>
        </div>

        {{-- Toggle: Fixa --}}
        <label class="toggle-switch">
            <input type="checkbox" name="is_fixed" value="1" {{ $val('is_fixed') ? 'checked' : '' }}>
            <div class="toggle-track">
                <div class="toggle-thumb"></div>
            </div>
            <span class="toggle-label-text">{{ $isDespesa ? 'Despesa' : 'Receita' }} fixa mensal (recorrente)</span>
        </label>

        {{-- Botões --}}
        <div class="flex justify-end gap-3 pt-2 border-t border-gray-500 mt-2">
            <a href="{{ $voltar }}" class="btn-ghost">
                <span class="material-symbols-outlined text-[17px]">close</span>
                Cancelar
            </a>
            <button class="{{ $isDespesa ? '' : 'btn-primary' }} {{ $isDespesa ? 'h-10 px-5 rounded-lg font-bold text-[13px] flex items-center gap-2 text-white shadow-md transition-all' : '' }}"
                style="{{ $isDespesa ? 'background: linear-gradient(135deg,#DC2626,#B91C1C); box-shadow: 0 4px 12px rgba(220,38,38,0.3);' : '' }}">
                <span class="material-symbols-outlined text-[17px]">{{ $transaction ? 'save' : 'add_circle' }}</span>
                {{ $transaction ? 'Salvar alterações' : 'Salvar' }}
            </button>
        </div>
    </form>
</div>
@endsection
