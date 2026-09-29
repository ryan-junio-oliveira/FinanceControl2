@extends('layouts.app')
@section('title', 'Editar Perfil')
@section('breadcrumb', 'Conta / Editar Perfil')

@section('content')
<div class="max-w-2xl mx-auto w-full">
    <a href="{{ route('perfil') }}" class="back-link">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        Voltar para o perfil
    </a>

    <div class="flex items-center gap-3 mt-3 mb-6">
        <div class="w-11 h-11 rounded-lg grid place-items-center"
            style="background: linear-gradient(135deg,#ECFDF5,#D1FAE5);">
            <span class="material-symbols-outlined text-emerald-600 text-[22px]"
                style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">manage_accounts</span>
        </div>
        <div>
            <h1 class="text-[24px] font-extrabold tracking-tight">Editar perfil</h1>
            <p class="text-[13px] text-gray-400 mt-0.5 font-medium">Atualize seus dados pessoais.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('perfil.update') }}" class="form-card space-y-5">
        @csrf @method('PATCH')

        <div class="float-field {{ $errors->has('name') ? 'error' : '' }}">
            <input id="f-nome" name="name" required value="{{ old('name', $user->name) }}" placeholder=" ">
            <label for="f-nome">Nome completo *</label>
            @error('name')<p class="text-[12px] text-red-500 font-semibold mt-1.5 flex items-center gap-1">
                <span class="material-symbols-outlined text-[13px]">error</span>{{ $message }}
            </p>@enderror
        </div>

        <div class="float-field {{ $errors->has('email') ? 'error' : '' }}">
            <input id="f-email" name="email" required type="email" value="{{ old('email', $user->email) }}" placeholder=" ">
            <label for="f-email">E-mail *</label>
            @error('email')<p class="text-[12px] text-red-500 font-semibold mt-1.5 flex items-center gap-1">
                <span class="material-symbols-outlined text-[13px]">error</span>{{ $message }}
            </p>@enderror
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div class="float-field {{ $errors->has('phone') ? 'error' : '' }}">
                <input id="f-tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder=" " inputmode="tel">
                <label for="f-tel">Telefone</label>
                @error('phone')<p class="text-[12px] text-red-500 font-semibold mt-1.5">{{ $message }}</p>@enderror
            </div>
            <div class="float-field {{ $errors->has('birthdate') ? 'error' : '' }}">
                <input id="f-nasc" name="birthdate" type="date" value="{{ old('birthdate', $user->birthdate?->format('Y-m-d')) }}" placeholder=" ">
                <label for="f-nasc">Nascimento</label>
                @error('birthdate')<p class="text-[12px] text-red-500 font-semibold mt-1.5">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-2 border-t border-gray-500">
            <a href="{{ route('perfil') }}" class="btn-ghost">
                <span class="material-symbols-outlined text-[17px]">close</span> Cancelar
            </a>
            <button class="btn-primary">
                <span class="material-symbols-outlined text-[17px]">save</span>
                Salvar alterações
            </button>
        </div>
    </form>
</div>
@endsection
