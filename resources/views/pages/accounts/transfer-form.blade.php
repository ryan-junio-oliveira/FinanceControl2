@extends('layouts.app')
@section('title', 'Transferência Interna')
@section('breadcrumb', 'Contas / Transferência')
@section('nav-active', 'contas')

@section('content')
<div class="max-w-2xl mx-auto w-full">
    <a href="{{ route('contas') }}" class="back-link">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        Voltar para contas
    </a>

    <div class="flex items-center gap-3 mt-3 mb-6">
        <div class="w-11 h-11 rounded-lg grid place-items-center"
            style="background: linear-gradient(135deg,#EFF6FF,#DBEAFE);">
            <span class="material-symbols-outlined text-blue-600 text-[22px]"
                style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">swap_horiz</span>
        </div>
        <div>
            <h1 class="text-[24px] font-extrabold tracking-tight">Transferência interna</h1>
            <p class="text-[13px] text-gray-400 mt-0.5 font-medium">Mova valores entre contas da sua conta sem sair do controle.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('contas.transfer') }}" class="form-card space-y-5">
        @csrf

        {{-- Origem → Destino --}}
        <div class="grid sm:grid-cols-2 gap-4">
            <div class="float-field {{ $errors->has('from_account_id') ? 'error' : '' }}">
                <select id="f-de" name="from_account_id">
                    <option value="">Selecione a origem</option>
                    @foreach($contas as $c)
                        <option value="{{ $c->id }}" {{ (string)old('from_account_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
                <label for="f-de">Conta de origem *</label>
                @error('from_account_id')<p class="text-[12px] text-red-500 font-semibold mt-1.5">{{ $message }}</p>@enderror
            </div>

            <div class="float-field {{ $errors->has('to_account_id') ? 'error' : '' }}">
                <select id="f-para" name="to_account_id">
                    <option value="">Selecione o destino</option>
                    @foreach($contas as $c)
                        <option value="{{ $c->id }}" {{ (string)old('to_account_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
                <label for="f-para">Conta de destino *</label>
                @error('to_account_id')<p class="text-[12px] text-red-500 font-semibold mt-1.5">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Ícone de seta centralizado --}}
        <div class="flex justify-center -my-2">
            <div class="w-9 h-9 rounded-lg bg-blue-50 border border-blue-100 grid place-items-center">
                <span class="material-symbols-outlined text-blue-400 text-[20px]"
                    style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20">south</span>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            {{-- Valor --}}
            <div>
                <p class="text-[11px] font-bold uppercase tracking-widest text-gray-400 mb-1.5">Valor (R$) *</p>
                <div class="input-group {{ $errors->has('amount') ? 'error' : '' }}">
                    <span class="prefix">R$</span>
                    <input id="f-valor" name="amount" required inputmode="decimal" value="{{ old('amount') }}" placeholder="0,00" class="num">
                </div>
                @error('amount')<p class="text-[12px] text-red-500 font-semibold mt-1.5">{{ $message }}</p>@enderror
            </div>

            {{-- Data --}}
            <div class="float-field {{ $errors->has('occurred_on') ? 'error' : '' }}">
                <input id="f-data" name="occurred_on" type="date" required value="{{ old('occurred_on', now()->toDateString()) }}" placeholder=" ">
                <label for="f-data">Data *</label>
                @error('occurred_on')<p class="text-[12px] text-red-500 font-semibold mt-1.5">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            {{-- Responsável --}}
            <div class="float-field {{ $errors->has('user_id') ? 'error' : '' }}">
                <select id="f-membro" name="user_id">
                    <option value="">Selecione o responsável</option>
                    @foreach($membros as $m)
                        <option value="{{ $m->id }}" {{ (string)old('user_id', auth()->id()) === (string)$m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                    @endforeach
                </select>
                <label for="f-membro">Responsável *</label>
                @error('user_id')<p class="text-[12px] text-red-500 font-semibold mt-1.5">{{ $message }}</p>@enderror
            </div>

            {{-- Descrição --}}
            <div class="float-field">
                <input id="f-desc" name="description" value="{{ old('description') }}" placeholder=" ">
                <label for="f-desc">Descrição (opcional)</label>
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-2 border-t border-gray-500">
            <a href="{{ route('contas') }}" class="btn-ghost">
                <span class="material-symbols-outlined text-[17px]">close</span> Cancelar
            </a>
            <button class="h-10 px-5 rounded-lg font-bold text-[13px] flex items-center gap-2 text-white transition-all"
                style="background: linear-gradient(135deg,#3B82F6,#2563EB); box-shadow: 0 4px 12px rgba(59,130,246,0.3);">
                <span class="material-symbols-outlined text-[17px]">swap_horiz</span>
                Transferir
            </button>
        </div>
    </form>
</div>
@endsection
