@extends('layouts.app')
@section('title','Membros')
@section('breadcrumb','Conta / Membros')

@section('content')
@php use App\Support\Fin; $isGestor = auth()->user()->isAdmin(); @endphp

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[24px] font-extrabold tracking-tight text-gray-900">Membros na Conta</h1>
        <p class="text-[13px] text-gray-400 mt-0.5 font-medium">{{ auth()->user()->group->name }} · {{ $membros->count() }} membro(s).</p>
    </div>
    @if($isGestor)
    <x-btn-link :href="route('grupo.convites.create')" color="rose" icon="person_add">Convidar Membro</x-btn-link>
    @endif
</div>

{{-- Palavra-chave da família (identidade) --}}
<x-section-card title="Palavra-chave da Família" subtitle="Combinação secreta para confirmar identidade entre os membros">
    @if($isGestor)
    <form method="POST" action="{{ route('grupo.secret') }}" class="flex flex-wrap items-center gap-3">
        @csrf
        <input name="secret_phrase" value="{{ auth()->user()->group->setting()->secret_phrase ?? '' }}" maxlength="80"
            placeholder="Ex.: abacaxi e laranja" autocomplete="off"
            class="fld-control max-w-xs">
        <x-btn-submit color="rose">Salvar</x-btn-submit>
    </form>
    @else
        @php $frase = auth()->user()->group->setting()->secret_phrase ?? null; @endphp
        @if($frase)
        <p class="text-[16px] font-extrabold text-gray-800">🔑 {{ $frase }}</p>
        @else
        <p class="text-[13px] text-gray-400">A família ainda não definiu uma palavra-chave.</p>
        @endif
    @endif
    <p class="text-[12px] text-gray-400 mt-3">Use-a para confirmar que alguém é realmente um membro da família ao pedir ou transferir dinheiro.</p>
</x-section-card>

{{-- Todos os membros --}}
<x-section-card title="Todos os Membros" :subtitle="$membros->count().' membro(s) na conta'">
    <div class="overflow-x-auto -mx-5 lg:-mx-6 px-5 lg:px-6">
        <table class="w-full text-left min-w-[720px] table-modern">
            <thead>
                <tr>
                    <th>Membro</th>
                    <th>Papel</th>
                    <th class="text-right">Despesas em {{ $mes }}</th>
                    @if($isGestor)<th class="text-right">Ações</th>@endif
                </tr>
            </thead>
            <tbody class="text-[13px]">
                @forelse($membros as $u)
                <tr data-ledger-row>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <span class="w-9 h-9 rounded-lg grid place-items-center text-white text-[12px] font-extrabold shrink-0"
                                style="background:{{ $u->avatarColor() }}">{{ $u->initials() }}</span>
                            <span class="min-w-0">
                                <span class="block font-bold text-gray-800 truncate">{{ $u->name }}</span>
                                <span class="block text-[11px] text-gray-400 truncate">{{ $u->email }}</span>
                            </span>
                        </div>
                    </td>
                    <td><x-role-badge :role="$u->role" /></td>
                    <td class="text-right num font-extrabold text-gray-800 whitespace-nowrap">{{ Fin::money($u->gasto_mes) }}</td>
                    @if($isGestor)
                    <td class="text-right whitespace-nowrap">
                        @if($u->id !== auth()->id() && $u->role !== 'admin')
                        <form method="POST" action="{{ route('grupo.membros.papel', $u) }}" class="inline">
                            @csrf @method('PATCH')
                            <select name="role" onchange="this.form.submit()" title="Alterar papel de {{ $u->name }}"
                                class="h-8 rounded-lg border border-slate-200 text-[12px] font-bold px-2 bg-white text-gray-700 focus:border-emerald-500 outline-none transition">
                                @foreach(['co_admin'=>'Co-admin','dependente'=>'Dependente','junior'=>'Júnior'] as $v=>$l)
                                    <option value="{{ $v }}" {{ $u->role===$v?'selected':'' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </form>
                        <form method="POST" action="{{ route('grupo.membros.destroy', $u) }}" class="inline"
                            onsubmit="return confirm('Remover {{ $u->name }}? Só é possível sem lançamentos.')">
                            @csrf @method('DELETE')
                            <button title="Remover membro" class="w-8 h-8 rounded-lg hover:bg-red-50 text-red-400 hover:text-red-600 transition inline-grid place-items-center">
                                <span class="material-symbols-outlined text-[19px]">delete</span>
                            </button>
                        </form>
                        @else
                        <span class="text-[11px] text-gray-300 font-medium">—</span>
                        @endif
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $isGestor ? 4 : 3 }}" class="text-center text-gray-400">
                        <x-empty-state icon="group" title="Nenhum membro encontrado"
                            :actionUrl="$isGestor ? route('grupo.convites.create') : null" actionLabel="Convidar o primeiro" />
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-section-card>

{{-- Convites pendentes --}}
<x-section-card title="Convites Pendentes" subtitle="Link de primeiro acesso por convite">
    @forelse($convites as $cv)
    <div class="flex flex-wrap items-center gap-3 p-4 rounded-xl border border-slate-200 hover:border-slate-300 mb-2.5 transition bg-slate-50/30">
        <div class="flex-1 min-w-[180px]">
            <p class="text-[13px] font-extrabold text-gray-800">{{ $cv->name }}
                <span class="font-medium text-gray-500">· {{ $cv->email }}</span>
            </p>
            <p class="text-[11px] text-gray-400 font-mono break-all mt-0.5">{{ url('/first-access/'.$cv->token) }}</p>
        </div>
        <x-btn-action color="ghost" size="sm"
            onclick="navigator.clipboard.writeText('{{ url('/first-access/'.$cv->token) }}');this.textContent='✓ Copiado!';setTimeout(()=>this.textContent='Copiar link',2000)">Copiar link</x-btn-action>
        @if($isGestor)
        <form method="POST" action="{{ route('grupo.convites.destroy', $cv) }}" class="inline">
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
@endsection
