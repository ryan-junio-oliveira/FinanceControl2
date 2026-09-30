@extends('layouts.app')
@section('title','Pessoas')
@section('breadcrumb','Conta / Pessoas')

@section('content')
@php use App\Support\Fin; $isGestor = auth()->user()->isAdmin(); @endphp

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[24px] font-extrabold tracking-tight text-gray-900">Pessoas na Conta</h1>
        <p class="text-[13px] text-gray-400 mt-0.5 font-medium">{{ auth()->user()->family->name }} · {{ $membros->count() }} pessoa(s).</p>
    </div>
    @if($isGestor)
    <div class="flex gap-2">
        <x-btn-link :href="route('familia.mesadas.create')" color="ghost" icon="tune">Regras de Mesada</x-btn-link>
        <x-btn-link :href="route('familia.convites.create')" color="rose" icon="person_add">Convidar Pessoa</x-btn-link>
    </div>
    @endif
</div>

{{-- Cards de membros --}}
<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
    @foreach($membros as $u)
    <div class="section-card relative hover:-translate-y-1 hover:shadow-[var(--shadow-lift)] transition-all duration-200
        {{ $u->role === 'admin' ? 'ring-2 ring-slate-200 ring-offset-2' : '' }}">">
        {{-- Badge papel --}}
        <span class="absolute top-4 right-4">
            <x-badge :type="$u->role === 'admin' ? 'neutral' : ($u->role === 'co_admin' ? 'info' : 'warning')">
                {{ $u->roleLabel() }}
            </x-badge>
        </span>

        <span class="w-14 h-14 rounded-lg grid place-items-center text-white font-extrabold text-[16px] ring-4 ring-offset-2"
            style="background:{{ $u->avatarColor() }}; ring-color: {{ $u->avatarColor() }}20;">{{ $u->initials() }}</span>

        <p class="font-extrabold mt-3 text-gray-900 text-[15px]">{{ $u->name }}</p>
        <p class="text-[11px] text-gray-400 mt-0.5">{{ $u->email }}</p>

        <div class="mt-3 rounded-lg bg-slate-50 border border-slate-200 p-3 text-center">
            <p class="text-[11px] text-gray-400 font-medium">Despesas em {{ $mes }}</p>
            <p class="num font-extrabold text-[16px] text-gray-900 mt-0.5">{{ Fin::money($u->gasto_mes) }}</p>
        </div>

        @if($u->mesada)
        <div class="mt-3">
            <p class="num font-extrabold text-[13px] text-gray-800">
                {{ Fin::money($u->mesada->amount) }}
                <span class="text-[11px] font-semibold text-gray-400">/ {{ $u->mesada->frequency === 'mensal' ? 'mês' : 'semana' }}</span>
            </p>
            <x-progress :value="$u->mesada->amount > 0 ? min(100, $u->gasto_mes / (float) $u->mesada->amount * 100) : 0" class="mt-2"/>
        </div>
        @endif

        @if($isGestor && $u->id !== auth()->id() && $u->role !== 'admin')
        <form method="POST" action="{{ route('familia.membros.papel', $u) }}" class="mt-4 pt-3 border-t border-slate-200 flex gap-2">
            @csrf @method('PATCH')
            <select name="role" class="h-9 flex-1 rounded-lg border border-slate-200 text-[12px] font-bold px-2 bg-white text-gray-700 focus:border-emerald-500 outline-none transition">
                @foreach(['co_admin'=>'Co-admin','dependente'=>'Dependente','junior'=>'Júnior'] as $v=>$l)
                    <option value="{{ $v }}" {{ $u->role===$v?'selected':'' }}>{{ $l }}</option>
                @endforeach
            </select>
            <x-btn-submit color="dark" size="sm">OK</x-btn-submit>
        </form>
        <form method="POST" action="{{ route('familia.membros.destroy', $u) }}"
            onsubmit="return confirm('Remover {{ $u->name }}? Só é possível sem lançamentos.')" class="mt-2">
            @csrf @method('DELETE')
            <x-btn-submit color="soft-red" size="sm">Remover pessoa</x-btn-submit>
        </form>
        @endif
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    {{-- Convites pendentes --}}
    <x-section-card class="lg:col-span-7" title="Convites Pendentes" subtitle="Link de primeiro acesso por convite">
        @forelse($convites as $cv)
        <div class="flex flex-wrap items-center gap-3 p-4 rounded-xl border border-slate-200 hover:border-slate-300 mb-2.5 transition bg-slate-50/30">
            <div class="flex-1 min-w-[180px]">
                <p class="text-[13px] font-extrabold text-gray-800">{{ $cv->name }}
                    <span class="font-medium text-gray-500">· {{ $cv->email }}</span>
                </p>
                <p class="text-[11px] text-gray-400 font-mono break-all mt-0.5">{{ url('/primeiro-acesso/'.$cv->token) }}</p>
            </div>
            <x-btn-action color="ghost" size="sm"
                onclick="navigator.clipboard.writeText('{{ url('/primeiro-acesso/'.$cv->token) }}');this.textContent='✓ Copiado!';setTimeout(()=>this.textContent='Copiar link',2000)">Copiar link</x-btn-action>
            @if($isGestor)
            <form method="POST" action="{{ route('familia.convites.destroy', $cv) }}" class="inline">
                @csrf @method('DELETE')
                <x-btn-submit color="soft-red" size="sm">Revogar</x-btn-submit>
            </form>
            @endif
        </div>
        @empty
        <div class="text-center py-10 text-gray-400">
            <span class="material-symbols-outlined text-[44px] text-gray-300">mark_email_read</span>
            <p class="text-[13px] font-bold mt-2 text-gray-500">Nenhum convite pendente.</p>
        </div>
        @endforelse
    </x-section-card>

    {{-- Mesadas ativas --}}
    <x-section-card class="lg:col-span-5" title="Mesadas Ativas" subtitle="Próximo repasse calculado">
        @forelse($mesadas as $md)
        <div class="flex items-center gap-3 p-3.5 rounded-lg border border-amber-100 bg-amber-50/30 hover:border-amber-200 mb-2.5 transition">
            <span class="w-10 h-10 rounded-lg grid place-items-center shrink-0"
                style="background: linear-gradient(135deg, #FFFBEB, #FEF3C7);">
                <span class="material-symbols-outlined text-amber-500"
                    style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">calendar_clock</span>
            </span>
            <div class="flex-1 min-w-0">
                <p class="text-[13px] font-extrabold text-gray-800">{{ $md->member->name }}</p>
                <p class="text-[11px] text-gray-400">{{ ucfirst($md->frequency) }} · próximo {{ $md->nextPayday()->format('d/m/Y') }}</p>
            </div>
            <strong class="num text-[13px] text-gray-800">{{ Fin::money($md->amount) }}</strong>
            @if($isGestor)
            <form method="POST" action="{{ route('familia.mesadas.destroy', $md) }}" onsubmit="return confirm('Remover mesada de {{ $md->member->name }}?')" class="shrink-0">
                @csrf @method('DELETE')
                <x-btn-submit color="danger" iconOnly icon="close" title="Remover mesada" />
            </form>
            @endif
        </div>
        @empty
        <div class="text-center py-10 text-gray-400">
            <span class="material-symbols-outlined text-[44px] text-gray-300">calendar_clock</span>
            <p class="text-[13px] font-bold mt-2 text-gray-500">Nenhuma mesada configurada.</p>
        </div>
        @endforelse
    </x-section-card>
</div>
@endsection
