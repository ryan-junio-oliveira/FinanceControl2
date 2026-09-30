@extends('layouts.app')
@section('title', 'Meu Perfil')
@section('breadcrumb', 'Conta / Meu Perfil')

@section('content')
@php use App\Support\Fin; @endphp

<div class="max-w-3xl mx-auto w-full">
    {{-- Hero do perfil --}}
    <div class="relative overflow-hidden rounded-lg p-6 mb-6 text-white"
        style="background: linear-gradient(135deg, #042f1e 0%, #064E3B 40%, #059669 100%);">
        {{-- Orbs --}}
        <div class="absolute -right-8 -top-8 w-40 h-40 rounded-full opacity-20 blur-3xl"
            style="background: radial-gradient(circle, #34D399, transparent)"></div>
        <div class="absolute left-1/2 -bottom-8 w-32 h-32 rounded-full opacity-15 blur-2xl"
            style="background: radial-gradient(circle, #A7F3D0, transparent)"></div>

        <div class="relative flex flex-wrap items-center gap-5">
            {{-- Avatar grande --}}
            <div class="w-20 h-20 rounded-lg grid place-items-center text-[24px] font-extrabold shrink-0 ring-4 ring-white/20"
                style="background: {{ $user->avatarColor() }}; box-shadow: 0 8px 24px rgba(0,0,0,0.2);">
                {{ $user->initials() }}
            </div>

            <div class="flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <x-badge type="info">{{ $user->roleLabel() }}</x-badge>
                    <span class="text-[11px] text-emerald-200/70 font-medium">na conta desde {{ $user->created_at->format('M/Y') }}</span>
                </div>
                <h1 class="text-[26px] font-extrabold tracking-tight mt-1">{{ $user->name }}</h1>
                <p class="text-[13px] text-emerald-100/70 mt-0.5">{{ $user->email }} · {{ $user->family->name }}</p>
            </div>

            <div class="flex gap-2 flex-wrap">
                <a href="{{ route('perfil.senha') }}" class="btn-ghost h-9 px-4 text-[12px] rounded-lg bg-white/10 border-white/20 text-white hover:bg-white/20">
                    <span class="material-symbols-outlined text-[16px]">password</span>
                    Trocar senha
                </a>
                <a href="{{ route('perfil.edit') }}" class="h-9 px-4 rounded-lg text-[12px] font-bold flex items-center gap-2 text-white border border-white/20 hover:bg-white/10 transition-all"
                    style="background: rgba(255,255,255,0.12);">
                    <span class="material-symbols-outlined text-[16px]">edit</span>
                    Editar perfil
                </a>
            </div>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        {{-- Dados pessoais --}}
        <x-section-card title="Dados pessoais" subtitle="Informações da sua conta">
            <div class="space-y-3">
                @foreach([
                    ['icon'=>'person','label'=>'Nome','value'=>$user->name],
                    ['icon'=>'email','label'=>'E-mail','value'=>$user->email,'break'=>true],
                    ['icon'=>'phone','label'=>'Telefone','value'=>$user->phone ?? '—'],
                    ['icon'=>'cake','label'=>'Nascimento','value'=>$user->birthdate?->format('d/m/Y') ?? '—'],
                ] as $row)
                <div class="flex items-center gap-3 py-1">
                    <span class="w-8 h-8 rounded-lg bg-slate-100 text-gray-500 grid place-items-center shrink-0">
                        <span class="material-symbols-outlined text-[16px]"
                            style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20">{{ $row['icon'] }}</span>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">{{ $row['label'] }}</p>
                        <p class="text-[13px] font-bold text-gray-800 {{ !empty($row['break']) ? 'break-all' : 'truncate' }} mt-0.5">{{ $row['value'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </x-section-card>

        {{-- Atividade --}}
        <x-section-card title="Sua atividade" :subtitle="'Em '.$mes">
            @if($mesada && $mesada->amount > 0)
            @php $pctMesada = min(100, $gastoMes / (float) $mesada->amount * 100); @endphp
            <div>
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-gray-400">Uso da mesada</p>
                    <span class="text-[12px] font-bold {{ $pctMesada > 90 ? 'text-red-500' : ($pctMesada > 75 ? 'text-amber-500' : 'text-emerald-600') }} num">
                        {{ round($pctMesada) }}%
                    </span>
                </div>
                <x-progress :value="$pctMesada" />
                <p class="text-[11px] text-gray-400 mt-1.5 font-medium">
                    {{ Fin::money($gastoMes) }} de {{ Fin::money($mesada->amount) }} consumidos
                </p>
            </div>
            @else
            <p class="text-[13px] text-gray-400">Nenhuma mesada configurada para você.</p>
            @endif
        </x-section-card>
    </div>
</div>
@endsection
