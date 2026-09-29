@extends('layouts.app')
@section('title','Dashboard')
@section('breadcrumb','Visão Geral / Dashboard')

@section('content')
@php use App\Support\Fin; $mesAtual = Fin::month(); @endphp

{{-- FILTRO DE MÊS / ANO (movido da navbar) --}}
<div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center bg-white border border-gray-500 rounded-lg h-10 text-[13px] font-bold text-gray-700 overflow-hidden shadow-sm">
        <a href="{{ request()->fullUrlWithQuery(['mes' => \Carbon\Carbon::createFromFormat('Y-m', $mesAtual)->subMonth()->format('Y-m')]) }}"
            class="px-2 h-full grid place-items-center hover:bg-slate-50 text-gray-400 hover:text-gray-700 transition" title="Mês anterior">
            <span class="material-symbols-outlined text-[18px]">chevron_left</span>
        </a>
        <label class="flex items-center gap-1.5 px-2 cursor-pointer">
            <span class="material-symbols-outlined text-[17px] text-emerald-500">calendar_month</span>
            <input type="month" id="filtro-mes" value="{{ $mesAtual }}"
                class="bg-transparent outline-none text-[13px] font-bold text-gray-700 w-[110px]" title="Escolha o mês">
        </label>
        <a href="{{ request()->fullUrlWithQuery(['mes' => \Carbon\Carbon::createFromFormat('Y-m', $mesAtual)->addMonth()->format('Y-m')]) }}"
            class="px-2 h-full grid place-items-center hover:bg-slate-50 text-gray-400 hover:text-gray-700 transition" title="Próximo mês">
            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
        </a>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('familia') }}" class="btn-ghost">
            <span class="material-symbols-outlined text-[17px] text-emerald-600">flag</span>
            Planejar Metas
        </a>
        <a href="{{ route('receitas.create') }}" class="btn-ghost">
            <span class="material-symbols-outlined text-[17px] text-blue-500">arrow_circle_up</span>
            Nova Receita
        </a>
        <a href="{{ route('despesas.create') }}" class="btn-primary">
            <span class="material-symbols-outlined text-[17px]">remove_circle_outline</span>
            Nova Despesa
        </a>
    </div>
</div>

@push('scripts')
<script>
// Filtro de mês: navega ao escolher um mês no seletor.
document.getElementById('filtro-mes')?.addEventListener('change', function () {
    if (this.value) {
        const url = new URL(window.location.href);
        url.searchParams.set('mes', this.value);
        window.location.href = url.toString();
    }
});
</script>
@endpush

{{-- HERO --}}
<div class="hero-card flex flex-col lg:flex-row lg:items-center gap-5">
    {{-- Orbs decorativos --}}
    <div class="absolute -right-12 -top-12 w-56 h-56 rounded-full blur-3xl pointer-events-none opacity-40"
        style="background: radial-gradient(circle, #A7F3D0, transparent)"></div>
    <div class="absolute left-1/3 -bottom-10 w-44 h-44 rounded-full blur-2xl pointer-events-none opacity-30"
        style="background: radial-gradient(circle, #BFDBFE, transparent)"></div>

    <div class="relative z-10 flex-1">
        <div class="flex items-center gap-2 flex-wrap">
            <x-badge type="success">Saúde Financeira</x-badge>
            <span class="text-[12px] text-gray-400 font-medium">· {{ $mes ?? '' }}</span>
        </div>
        <h1 class="text-[22px] lg:text-[27px] font-extrabold tracking-tight mt-2.5 text-gray-900">
            Olá, {{ explode(' ', auth()->user()->name)[0] }}!
            <span class="text-gray-400 font-semibold"> Este é o resumo da sua conta. </span>
        </h1>
        <p class="text-[14px] text-gray-500 mt-1.5">
            Resultado do mês:
            <strong class="{{ ($receitasMes - $despesasMes) >= 0 ? 'text-emerald-600' : 'text-red-500' }} num font-extrabold">
                {{ Fin::signedMoney(abs($receitasMes - $despesasMes), ($receitasMes - $despesasMes) >= 0 ? 'receita' : 'despesa') }}
            </strong>
        </p>
    </div>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
    <x-kpi-card label="Saldo Consolidado" :value="Fin::money($saldo)" :raw="$saldo" prefix="R$ " icon="account_balance_wallet" accent="emerald">
        <x-slot:bottom>
            <span class="text-[12px] text-gray-400">{{ $accounts->count() }} conta(s) ativa(s)</span>
        </x-slot:bottom>
    </x-kpi-card>
    <x-kpi-card label="Receitas do Mês" :value="Fin::money($receitasMes)" :raw="$receitasMes" prefix="R$ " icon="payments" accent="green" />
    <x-kpi-card label="Despesas do Mês" :value="Fin::money($despesasMes)" :raw="$despesasMes" prefix="R$ " icon="shopping_bag" accent="slate" />
    <x-kpi-card label="Aportes no Mês" :value="Fin::money($aportesMes)" :raw="$aportesMes" prefix="R$ " icon="query_stats" accent="blue">
        <x-slot:bottom>
            <span class="text-[12px] text-gray-400">Patrimônio</span>
            <span class="text-[14px] font-extrabold num text-gray-800">{{ Fin::money($patrimonio) }}</span>
        </x-slot:bottom>
    </x-kpi-card>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    {{-- FLUXO --}}
    <x-section-card class="lg:col-span-8" title="Fluxo Financeiro Semanal" :subtitle="'Entradas e saídas pagas · '.$mes">
        <x-slot:action>
            <div class="flex items-center gap-4 text-[12px] font-semibold text-gray-500">
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full" style="background: #059669;"></span> Receitas
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-200"></span> Despesas
                </span>
            </div>
        </x-slot:action>
        @if(array_sum(array_column($fluxo, 'receitas')) + array_sum(array_column($fluxo, 'despesas')) > 0)
        <div class="grid grid-cols-4 gap-3 mt-2">
            @foreach($fluxo as $w)
            <div class="flex flex-col items-center gap-2">
                <div class="flex items-end justify-center gap-1.5 h-32 w-full">
                    <div class="bar-anim w-5 rounded-t-lg" title="Receitas: {{ Fin::money($w['receitas']) }}"
                        style="height:{{ max(4, $w['receitas'] / $maxFluxo * 100) }}%; background: linear-gradient(to top, #047857, #34D399);"></div>
                    <div class="bar-anim w-5 rounded-t-lg bg-slate-200" title="Despesas: {{ Fin::money($w['despesas']) }}"
                        style="height:{{ max(4, $w['despesas'] / $maxFluxo * 100) }}%; animation-delay:.1s;"></div>
                </div>
                <span class="text-[11px] font-bold text-gray-500 text-center">{{ $w['rotulo'] }}</span>
                <span class="text-[10px] text-gray-300 num text-center leading-tight">
                    {{ Fin::money($w['receitas']) }}<br>{{ Fin::money($w['despesas']) }}
                </span>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-12 text-gray-400">
            <span class="material-symbols-outlined text-[44px] text-gray-300">bar_chart</span>
            <p class="text-[13px] font-bold mt-3 text-gray-500">Sem movimentações pagas neste mês</p>
            <p class="text-[12px] mt-1">Registre receitas e despesas para ver o fluxo.</p>
        </div>
        @endif
    </x-section-card>

    {{-- GASTOS POR MEMBRO --}}
    <x-section-card class="lg:col-span-4" title="Gastos por Pessoa" subtitle="Despesas do mês por responsável">
        @forelse($porMembro as $row)
        <div class="mb-4">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-full grid place-items-center text-white text-[11px] font-extrabold shrink-0 ring-2 ring-offset-1 ring-gray-500"
                    style="background:{{ $row->member->avatarColor() }}">{{ $row->member->initials() }}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-[13px] font-bold truncate">{{ $row->member->name }}</p>
                    <p class="text-[11px] text-gray-400">{{ $row->member->roleLabel() }}</p>
                </div>
                <p class="text-[13px] font-extrabold num text-gray-800">{{ Fin::money($row->total) }}</p>
            </div>
            <div class="mt-2 flex items-center gap-2">
                <x-progress :value="$totalMembros > 0 ? $row->total / $totalMembros * 100 : 0" class="flex-1" />
                <span class="text-[11px] font-bold text-gray-400 w-10 text-right num">{{ $totalMembros > 0 ? round($row->total / $totalMembros * 100) : 0 }}%</span>
            </div>
        </div>
        @empty
        <div class="text-center py-10 text-gray-400">
            <span class="material-symbols-outlined text-[40px] text-gray-300">group</span>
            <p class="text-[13px] font-bold mt-2 text-gray-500">Nenhuma despesa no mês</p>
        </div>
        @endforelse
        <a href="{{ route('familia') }}" class="btn-ghost w-full justify-center mt-2">
            <span class="material-symbols-outlined text-[17px] text-emerald-600">manage_accounts</span>
            Gerenciar Mesadas &amp; Permissões
        </a>
    </x-section-card>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    {{-- ORÇAMENTO POR CATEGORIA --}}
    <x-section-card class="lg:col-span-5" title="Orçamento por Categoria" subtitle="Gasto vs teto mensal">
        <x-slot:action>
            <a href="{{ route('categorias') }}" class="text-[12px] font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
                Configurar Tetos <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
            </a>
        </x-slot:action>
        @forelse($orcamento as $c)
        <div class="mb-4">
            <div class="flex items-center gap-2.5">
                <p class="flex-1 text-[13px] font-bold text-gray-800">{{ $c['nome'] }}</p>
                <p class="text-[12px] font-bold num text-gray-600">
                    {{ Fin::money($c['gasto']) }}{{ $c['teto'] ? ' / '.Fin::money($c['teto']) : '' }}
                </p>
            </div>
            <div class="mt-1.5 flex items-center gap-2">
                <x-progress :value="$c['pct']" class="flex-1"/>
                <span class="text-[11px] text-gray-400 w-12 text-right font-bold num">{{ round($c['pct']) }}%</span>
            </div>
        </div>
        @empty
        <div class="text-center py-10 text-gray-400">
            <span class="material-symbols-outlined text-[40px] text-gray-300">pie_chart</span>
            <p class="text-[13px] font-bold mt-2 text-gray-500">Sem categorias de despesa</p>
            <a href="{{ route('categorias') }}" class="text-[12px] font-bold text-emerald-600 hover:text-emerald-700 mt-1 inline-block">Criar a primeira categoria →</a>
        </div>
        @endforelse
    </x-section-card>

    {{-- CARTÕES --}}
    <x-section-card class="lg:col-span-4" title="Cartões" subtitle="Faturas em aberto">
        @forelse($cartoes as $cd)
        <div class="flex items-center gap-3 p-3.5 rounded-lg border border-gray-500 hover:border-emerald-100 hover:bg-emerald-50/30 mb-2.5 transition-all duration-200">
            <span class="w-11 h-7 rounded-lg shrink-0 cc-sheen shadow-sm" style="background:{{ $cd->color ?: 'linear-gradient(135deg,#0f172a,#334155)' }}"></span>
            <div class="flex-1 min-w-0">
                <p class="text-[13px] font-bold truncate">{{ $cd->name }}</p>
                <p class="text-[11px] text-gray-400">Vence dia {{ $cd->due_day }} · {{ $cd->holder->name ?? '—' }}</p>
            </div>
            <p class="text-[14px] font-extrabold num text-gray-800">{{ Fin::money($cd->open_invoice) }}</p>
        </div>
        @empty
        <div class="text-center py-10 text-gray-400">
            <span class="material-symbols-outlined text-[40px] text-gray-300">credit_card</span>
            <p class="text-[13px] font-bold mt-2 text-gray-500">Nenhum cartão cadastrado</p>
        </div>
        @endforelse
        <a href="{{ route('cartoes') }}" class="btn-secondary w-full justify-center mt-3">
            Ver cartões <span class="material-symbols-outlined text-[17px]">arrow_forward</span>
        </a>
    </x-section-card>

    {{-- PRÓXIMOS VENCIMENTOS --}}
    <x-section-card class="lg:col-span-3" title="Próximos Vencimentos" subtitle="Contas pendentes">
        @forelse($vencimentos as $v)
        <div class="flex items-center gap-2.5 mb-3">
            <span class="w-9 h-9 rounded-lg grid place-items-center shrink-0"
                style="background: linear-gradient(135deg, #FFFBEB, #FEF3C7);">
                <span class="material-symbols-outlined text-amber-600 text-[18px]"
                    style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20">event_busy</span>
            </span>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-[13px] truncate text-gray-800">{{ $v->description }}</p>
                <p class="text-[11px] text-gray-400 num">{{ $v->due_on->format('d/m') }} · {{ Fin::money($v->amount) }}</p>
            </div>
        </div>
        @empty
        <div class="text-center py-10 text-gray-400">
            <span class="material-symbols-outlined text-[40px] text-gray-300">event_available</span>
            <p class="text-[13px] font-bold mt-2 text-gray-500">Nada a vencer</p>
        </div>
        @endforelse
    </x-section-card>
</div>

{{-- ÚLTIMAS TRANSAÇÕES --}}
<x-section-card title="Últimas Transações" subtitle="Movimentações mais recentes">
    <x-slot:action>
        <a href="{{ route('despesas') }}" class="text-[13px] font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
            Ver ledger completo <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
        </a>
    </x-slot:action>
    <div class="overflow-x-auto -mx-5 lg:-mx-6 px-5 lg:px-6">
        <table class="w-full text-left min-w-[720px] table-modern">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Categoria</th>
                    <th>Responsável</th>
                    <th>Data</th>
                    <th class="text-right">Valor</th>
                    <th class="text-right">Status</th>
                </tr>
            </thead>
            <tbody class="text-[13px]">
                @forelse($recent as $t)
                <tr data-ledger-row>
                    <td class="font-bold text-gray-800">{{ $t->description }}</td>
                    <td><x-badge type="neutral">{{ $t->category->name ?? '—' }}</x-badge></td>
                    <td class="text-gray-500">{{ $t->member->name ?? '—' }}</td>
                    <td class="text-gray-400 num text-[12px]">{{ $t->occurred_on->format('d/m/Y') }}</td>
                    <td class="text-right font-extrabold num {{ $t->type === 'receita' ? 'text-emerald-600' : 'text-gray-800' }}">
                        {{ Fin::signedMoney($t->amount, $t->type === 'receita' ? 'receita' : 'despesa') }}
                    </td>
                    <td class="text-right"><x-badge :type="$t->display_status_type">{{ $t->display_status }}</x-badge></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-12 text-gray-400">
                        <span class="material-symbols-outlined text-[44px] text-gray-300">receipt_long</span>
                        <p class="text-[13px] font-bold mt-3 text-gray-500">Nenhum lançamento ainda</p>
                        <a href="{{ route('despesas.create') }}" class="mt-2 inline-block text-[12px] font-bold text-emerald-600 hover:text-emerald-700">Registrar o primeiro →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-section-card>
@endsection
