@extends('layouts.app')
@section('title','Cartões')
@section('breadcrumb','Crédito / Cartões')

@section('content')
@php use App\Support\Fin; $isGestor = auth()->user()->isAdmin(); @endphp

{{-- Page header --}}
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[24px] font-extrabold tracking-tight text-gray-900">Cartões</h1>
        <p class="text-[13px] text-gray-400 mt-0.5 font-medium">Limite, fechamento, vencimento e titular de cada cartão.</p>
    </div>
    <div class="flex gap-2">
        <x-btn-link :href="route('cartoes.itens.create')" icon="add_shopping_cart">Nova Compra</x-btn-link>
        @if($isGestor)
        <x-btn-link :href="route('cartoes.create')" icon="add_card">Novo Cartão</x-btn-link>
        @endif
    </div>
</div>

{{-- Cards --}}
<div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
    @forelse($cartoes as $c)
    <div class="rounded-lg border border-gray-500 overflow-hidden bg-white shadow-[var(--shadow-card)] hover:shadow-[var(--shadow-pop)] transition-all duration-200 group">
        {{-- Face do cartão --}}
        <div class="cc-sheen text-white p-5 min-h-[160px] flex flex-col justify-between relative overflow-hidden" style="background:{{ $c->display_color }}">
            {{-- Orb decorativo --}}
            <div class="absolute -right-8 -bottom-8 w-32 h-32 rounded-lg opacity-20" style="background: rgba(255,255,255,0.4); filter: blur(20px);"></div>
            <div class="flex items-center justify-between gap-2 relative z-10">
                <p class="text-[11px] font-extrabold tracking-[.15em] opacity-90">{{ mb_strtoupper($c->name) }}</p>
                <div class="flex items-center gap-1.5">
                    @if($c->brand_label)
                    <span class="text-[10px] font-bold rounded-lg px-2.5 py-1 uppercase tracking-wider" style="background: rgba(0,0,0,0.2);">{{ $c->brand_label }}</span>
                    @endif
                    @if(!$c->active)
                    <span class="text-[10px] font-bold rounded-lg px-2.5 py-1" style="background: rgba(0,0,0,0.2);">INATIVO</span>
                    @endif
                </div>
            </div>
            <div class="relative z-10">
                <p class="text-[11px] opacity-70 flex items-center gap-1.5 mb-1">
                    <span class="material-symbols-outlined text-[14px]" style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20">person</span>
                    {{ $c->holder->name ?? 'Sem titular' }}
                </p>
                <p class="font-extrabold num text-[24px] tracking-tight">{{ Fin::money($c->credit_limit) }}</p>
                <p class="text-[11px] opacity-70 mt-0.5">Fecha dia {{ $c->closing_day }} · Vence dia {{ $c->due_day }}</p>
            </div>
        </div>

        {{-- Corpo do card --}}
        <div class="p-4">
            <div class="flex items-center justify-between text-[12px] mb-2.5">
                <span class="text-gray-400 flex items-center gap-1.5">
                    @if($c->account)
                    <span class="w-2.5 h-2.5 rounded-full inline-block" style="background:{{ $c->account->color ?? '#9ca3af' }}"></span>
                    {{ $c->account->name }}
                    @else
                    <span class="text-gray-300">Sem conta vinculada</span>
                    @endif
                </span>
                <span class="font-bold num text-gray-800">{{ Fin::money($c->open_invoice) }} em aberto</span>
            </div>
            <x-progress :value="$c->credit_limit > 0 ? min(100, $c->open_invoice / (float) $c->credit_limit * 100) : 0" />
            <div class="mt-4 flex items-center gap-1.5">
                <x-btn-link :href="route('cartoes.itens.create')" color="ghost" size="sm" icon="add">Comprar</x-btn-link>
                @if($isGestor)
                <x-btn-link :href="route('cartoes.edit', $c)" iconOnly icon="edit" title="Editar cartão" />
                <form method="POST" action="{{ route('cartoes.destroy', $c) }}" class="inline"
                    onsubmit="return confirm('Excluir este cartão? Só é possível sem lançamentos.')">
                    @csrf @method('DELETE')
                    <x-btn-submit color="danger" iconOnly icon="delete" title="Excluir cartão" />
                </form>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="col-span-full text-center py-16 text-gray-400 bg-white rounded-lg border border-dashed border-gray-500">
        <span class="material-symbols-outlined text-[52px] text-gray-300">credit_card</span>
        <p class="text-[15px] font-extrabold mt-4 text-gray-500">Nenhum cartão cadastrado</p>
        <p class="text-[13px] mt-1">Adicione o primeiro cartão.</p>
        @if($isGestor)
        <x-btn-link :href="route('cartoes.create')" icon="add_card" class="mt-5">Novo Cartão</x-btn-link>
        @endif
    </div>
    @endforelse
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    {{-- Itens da fatura --}}
    <x-section-card class="lg:col-span-7" title="Itens da Fatura" :subtitle="$fatura->total().' item(ns)'">
        <x-slot:action>
            <x-btn-link :href="route('cartoes.itens.create')" size="sm" icon="add">Lançar compra</x-btn-link>
        </x-slot:action>
        <div class="overflow-x-auto -mx-5 lg:-mx-6 px-5 lg:px-6">
            <table class="w-full text-left min-w-[760px] table-modern">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Cartão</th>
                        <th>Responsável</th>
                        <th class="text-right">Valor</th>
                        <th class="text-right">Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="text-[13px]">
                    @forelse($fatura as $f)
                    <tr data-ledger-row>
                        <td class="num text-gray-400 text-[12px]">{{ $f->occurred_on->format('d/m') }}</td>
                        <td class="font-bold text-gray-800">{{ $f->description }}</td>
                        <td class="text-gray-500">{{ $f->card->name ?? '—' }}</td>
                        <td class="text-gray-500">{{ $f->member->name ?? '—' }}</td>
                        <td class="text-right font-extrabold num text-gray-800">{{ Fin::money($f->amount) }}</td>
                        <td class="text-right">
                            <x-badge :type="$f->status === 'pago' ? 'success' : 'warning'">
                                {{ $f->status === 'pago' ? 'Pago' : 'Pendente' }}
                            </x-badge>
                        </td>
                        <td class="text-right">
                            @if($f->status !== 'pago')
                            <form method="POST" action="{{ route('cartoes.itens.settle', $f) }}" class="inline">
                                @csrf
                                <x-btn-submit color="success" iconOnly icon="check_circle" title="Liquidar" />
                            </form>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-12 text-gray-400">
                            Nenhum item de fatura.
                            <a href="{{ route('cartoes.itens.create') }}" class="font-bold text-emerald-600 hover:text-emerald-700">Lançar a primeira compra →</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $fatura->links() }}</div>
    </x-section-card>

    {{-- Gastos por membro --}}
    <x-section-card class="lg:col-span-5" title="Gastos por Pessoa" subtitle="Somente valores pendentes">
        @forelse($porMembro as $r)
        <div class="flex items-center gap-3 mb-3.5">
            <span class="w-9 h-9 rounded-full grid place-items-center text-white text-[11px] font-extrabold ring-2 ring-offset-1 ring-gray-500"
                style="background:{{ $r->member->avatarColor() }}">{{ $r->member->initials() }}</span>
            <span class="flex-1 text-[13px] font-semibold text-gray-700">{{ $r->member->name }}</span>
            <strong class="num text-[13px] text-gray-800">{{ Fin::money($r->total) }}</strong>
        </div>
        @empty
        <div class="text-center py-10 text-gray-400">
            <span class="material-symbols-outlined text-[40px] text-gray-300">group</span>
            <p class="text-[13px] font-bold mt-2 text-gray-500">Sem valores pendentes por pessoa.</p>
        </div>
        @endforelse
    </x-section-card>
</div>
@endsection
