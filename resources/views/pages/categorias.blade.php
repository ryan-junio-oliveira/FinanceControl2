@extends('layouts.app')
@section('title', 'Categorias')
@section('breadcrumb', 'Sistema / Categorias')

@section('content')
@php use App\Support\Fin; $isGestor = auth()->user()->isAdmin(); @endphp

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[24px] font-extrabold tracking-tight text-gray-900">Categorias &amp; Orçamentos</h1>
        <p class="text-[13px] text-gray-400 mt-0.5 font-medium">Tetos mensais monitorados em {{ $mes }}.</p>
    </div>
    @if($isGestor)
    <a href="{{ route('categorias.create') }}" class="btn-secondary">
        <span class="material-symbols-outlined text-[17px]">add_circle</span>
        Nova Categoria
    </a>
    @endif
</div>

{{-- KPIs --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <x-kpi-card label="Total de Categorias"     :value="$categorias->count().' ativas'"  icon="category"             accent="slate" />
    <x-kpi-card label="Teto Mensal (despesas)"  :value="Fin::money($totalTetos)"          icon="account_balance_wallet" accent="emerald" />
    <x-kpi-card label="Tetos em Alerta (≥90%)" :value="$alertas.' categoria(s)'"         icon="warning"              accent="red" />
</div>

<x-section-card title="Todas as Categorias" subtitle="Filtro por tipo via busca do topo">
    <x-slot:action>
        <form method="GET" action="{{ route('categorias') }}" class="flex gap-2 items-center">
            <select name="tipo" onchange="this.form.submit()"
                class="h-9 rounded-lg border border-gray-500 text-[12px] font-bold px-3 bg-white text-gray-700 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/10 outline-none transition">
                <option value="">Despesas + Receitas</option>
                <option value="despesa" {{ request('tipo')==='despesa'?'selected':'' }}>Despesas</option>
                <option value="receita" {{ request('tipo')==='receita'?'selected':'' }}>Receitas</option>
            </select>
        </form>
    </x-slot:action>

    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($categorias as $c)
        @php
            $estourado = $c->type === 'despesa' && $c->monthly_cap > 0 && $c->pct > 100;
            $atencao = $c->type === 'despesa' && $c->monthly_cap > 0 && $c->pct >= 85 && $c->pct <= 100;
        @endphp
        <article data-ledger-row
            class="rounded-lg p-4 flex flex-col border transition-all duration-200 hover:shadow-[var(--shadow-pop)]
            {{ $estourado ? 'border-red-200 bg-red-50/30' : ($atencao ? 'border-amber-200 bg-amber-50/20' : 'border-gray-500 bg-white') }}">
            <div class="flex items-start gap-3">
                <span class="w-11 h-11 rounded-lg grid place-items-center shrink-0
                    {{ $estourado ? 'bg-red-100 text-red-500' : ($atencao ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-gray-500') }}">
                    <span class="material-symbols-outlined text-[22px]"
                        style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24">{{ $c->icon }}</span>
                </span>
                <div class="flex-1 min-w-0">
                    <p class="font-extrabold text-[14px] leading-tight text-gray-800">{{ $c->name }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $c->type === 'despesa' ? 'Despesa' : 'Receita' }} · {{ $c->subcategories->count() }} subcategoria(s)</p>
                </div>
                @if($estourado)
                    <x-badge type="critical">Estourado</x-badge>
                @elseif($atencao)
                    <x-badge type="warning">Atenção</x-badge>
                @else
                    <x-badge type="neutral">{{ ucfirst($c->type) }}</x-badge>
                @endif
            </div>

            @if($c->type === 'despesa')
            <div class="mt-3.5 flex items-baseline justify-between gap-2">
                <p class="text-[12px] text-gray-500">Gasto: <strong class="num text-gray-900 text-[14px]">{{ Fin::money($c->gasto_mes) }}</strong></p>
                <p class="text-[12px] text-gray-500">Teto: <strong class="num text-gray-700">{{ $c->monthly_cap ? Fin::money($c->monthly_cap) : '—' }}</strong></p>
            </div>
            @if($c->monthly_cap)
            <x-progress :value="min(100, $c->pct)" class="mt-2" />
            <p class="text-[12px] mt-1.5 font-bold {{ $estourado ? 'text-red-600' : ($atencao ? 'text-amber-600' : 'text-gray-400') }}">
                {{ round($c->pct) }}% do teto
            </p>
            @endif
            @endif

            @if($c->subcategories->isNotEmpty())
            <div class="mt-3 pt-3 border-t border-gray-500 space-y-1">
                @foreach($c->subcategories as $s)
                <div class="flex items-center gap-2 text-[12px] text-gray-500">
                    <span class="w-1 h-1 rounded-full bg-slate-300 shrink-0"></span>
                    {{ $s->name }}
                </div>
                @endforeach
            </div>
            @endif

            @if($isGestor)
            <div class="mt-3 pt-3 border-t border-gray-500 flex items-center gap-2">
                <a href="{{ route('categorias.edit', $c) }}" class="btn-ghost h-8 px-3 text-[12px] rounded-lg">
                    Editar / teto
                </a>
                <form method="POST" action="{{ route('categorias.destroy', $c) }}"
                    onsubmit="return confirm('Excluir? Só é possível sem lançamentos.')" class="inline">
                    @csrf @method('DELETE')
                    <button class="h-8 px-3 rounded-lg border border-red-100 text-red-400 hover:text-red-600 hover:bg-red-50 hover:border-red-200 text-[12px] font-bold transition-all">Excluir</button>
                </form>
            </div>
            @endif
        </article>
        @empty
        <div class="col-span-full text-center py-16 text-gray-400 bg-white rounded-lg border border-dashed border-gray-500">
            <span class="material-symbols-outlined text-[52px] text-gray-300">category</span>
            <p class="text-[15px] font-extrabold mt-4 text-gray-500">Nenhuma categoria ainda</p>
            <p class="text-[13px] mt-1">Crie a primeira para organizar o orçamento.</p>
            @if($isGestor)
            <a href="{{ route('categorias.create') }}" class="btn-secondary inline-flex mt-5">
                <span class="material-symbols-outlined text-[17px]">add_circle</span>
                Nova Categoria
            </a>
            @endif
        </div>
        @endforelse
    </div>
</x-section-card>
@endsection
