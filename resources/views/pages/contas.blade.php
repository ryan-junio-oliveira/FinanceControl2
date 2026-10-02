@extends('layouts.app')
@section('title','Contas')
@section('breadcrumb','Banco / Contas')

@section('content')
@php use App\Support\Fin; $isGestor = auth()->user()->isAdmin(); @endphp

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[24px] font-extrabold tracking-tight text-gray-900">Contas Bancárias &amp; Saldos</h1>
        <p class="text-[13px] text-gray-400 mt-0.5 font-medium">Toque em um card para ver o extrato completo da conta.</p>
    </div>
    @if($isGestor)
    <div class="flex gap-2 flex-wrap">
        <x-btn-link :href="route('contas.transfer.create')" color="ghost" icon="swap_horiz">Transferência Interna</x-btn-link>
        <x-btn-link :href="route('contas.create')" color="blue" icon="add_circle">Nova Conta</x-btn-link>
    </div>
    @endif
</div>

{{-- Cards de contas (estilo cartão de banco) --}}
<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
    @forelse($contas as $c)
    <div class="relative">
        <a href="{{ route('contas.extrato', $c) }}"
            class="group relative overflow-hidden rounded-2xl p-5 text-white block transition-all duration-200 hover:-translate-y-1 hover:shadow-[var(--shadow-lift)]"
            style="background: {{ $c->display_color }}">
            <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-white/10"></div>
            <div class="absolute -right-2 -bottom-10 w-24 h-24 rounded-full bg-white/10"></div>

            <div class="flex items-start justify-between gap-2 relative">
                <div class="min-w-0">
                    <p class="text-[10px] font-extrabold uppercase tracking-widest text-white/70 truncate">{{ $c->bank->name ?? 'Sem banco' }}</p>
                    <p class="font-extrabold text-[15px] truncate mt-0.5">{{ $c->name }}</p>
                </div>
                <span class="material-symbols-outlined text-white/80 text-[22px] shrink-0">credit_card</span>
            </div>

            <div class="mt-6 relative">
                <p class="num text-[26px] leading-8 font-extrabold drop-shadow-sm">{{ Fin::money($c->balance) }}</p>
                <p class="text-[11px] text-white/70 num mt-0.5">Inicial: {{ Fin::money($c->initial_balance) }}</p>
            </div>

            <div class="mt-5 pt-3 border-t border-white/20 flex items-center justify-between relative">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-white/75">{{ ucfirst($c->kind) }}</span>
                <span class="inline-flex items-center gap-0.5 text-[12px] font-extrabold text-white/90 group-hover:gap-1.5 transition-all">
                    Ver extrato <span class="material-symbols-outlined text-[15px]">chevron_right</span>
                </span>
            </div>
        </a>

        @if($isGestor)
        <div class="absolute top-2.5 right-2.5 flex gap-1">
            <a href="{{ route('contas.edit', $c) }}" title="Editar conta"
                class="w-8 h-8 rounded-lg grid place-items-center text-white bg-black/15 hover:bg-black/30 transition backdrop-blur-sm">
                <span class="material-symbols-outlined text-[17px]">edit</span>
            </a>
            <form method="POST" action="{{ route('contas.destroy', $c) }}"
                onsubmit="return confirm('Excluir esta conta? Só é possível sem movimentações.')" class="inline">
                @csrf @method('DELETE')
                <button type="submit" title="Excluir conta"
                    class="w-8 h-8 rounded-lg grid place-items-center text-white bg-black/15 hover:bg-red-500/70 transition backdrop-blur-sm">
                    <span class="material-symbols-outlined text-[17px]">delete</span>
                </button>
            </form>
        </div>
        @endif
    </div>
    @empty
    <div class="col-span-full bg-white rounded-2xl border border-dashed border-slate-200">
        <x-empty-state icon="account_balance" title="Nenhuma conta cadastrada" hint="Conecte a primeira conta para começar o controle."
            :actionUrl="$isGestor ? route('contas.create') : null" actionLabel="Nova Conta" />
    </div>
    @endforelse
</div>
@endsection