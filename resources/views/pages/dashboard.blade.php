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
                class="bg-transparent outline-none text-[13px] font-bold text-gray-700 w-[170px] min-w-0" title="Escolha o mês">
        </label>
        <a href="{{ request()->fullUrlWithQuery(['mes' => \Carbon\Carbon::createFromFormat('Y-m', $mesAtual)->addMonth()->format('Y-m')]) }}"
            class="px-2 h-full grid place-items-center hover:bg-slate-50 text-gray-400 hover:text-gray-700 transition" title="Próximo mês">
            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
        </a>
    </div>
    <div class="flex flex-wrap gap-2">
        <x-btn-link :href="route('familia')" color="ghost" icon="flag">Planejar Metas</x-btn-link>
        <x-btn-link :href="route('receitas.create')" icon="arrow_circle_up">Nova Receita</x-btn-link>
        <x-btn-link :href="route('despesas.create')" icon="remove_circle_outline">Nova Despesa</x-btn-link>
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
            Resultado do mês (caixa):
            <strong class="{{ ($receitasMes - ($despesasPagas ?? $despesasMes)) >= 0 ? 'text-emerald-600' : 'text-red-500' }} num font-extrabold">
                {{ Fin::signedMoney(abs($receitasMes - ($despesasPagas ?? $despesasMes)), ($receitasMes - ($despesasPagas ?? $despesasMes)) >= 0 ? 'receita' : 'despesa') }}
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
    <x-kpi-card label="Despesas do Mês" :value="Fin::money($despesasMes)" :raw="$despesasMes" prefix="R$ " icon="shopping_bag" accent="red" />
    <x-kpi-card label="Aportes no Mês" :value="Fin::money($aportesMes)" :raw="$aportesMes" prefix="R$ " icon="query_stats" accent="blue">
        <x-slot:bottom>
            <span class="text-[12px] text-gray-400">Patrimônio</span>
            <span class="text-[14px] font-extrabold num text-gray-800">{{ Fin::money($patrimonio) }}</span>
        </x-slot:bottom>
    </x-kpi-card>
</div>

{{-- KPIs de saúde financeira --}}
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
    <x-kpi-card label="Resultado do Mês" :value="Fin::signedMoney(abs($resultado), $resultado >= 0 ? 'receita' : 'despesa')" icon="savings" :accent="$resultado >= 0 ? 'green' : 'red'">
        <x-slot:bottom>
            <span class="text-[12px] text-gray-400">Receitas menos despesas pagas</span>
        </x-slot:bottom>
    </x-kpi-card>
    <x-kpi-card label="Taxa de Poupança" :value="str_replace('.', ',', (string) $taxaPoupanca).'%'" icon="percent" accent="emerald">
        <x-slot:bottom>
            <span class="text-[12px] text-gray-400 num">
                @if($varReceitas !== null)Receitas {{ $varReceitas >= 0 ? '+' : '' }}{{ str_replace('.', ',', (string) $varReceitas) }}%@endif
                @if($varReceitas !== null && $varDespesas !== null) · @endif
                @if($varDespesas !== null)Despesas {{ $varDespesas >= 0 ? '+' : '' }}{{ str_replace('.', ',', (string) $varDespesas) }}%@endif
                @if($varReceitas === null && $varDespesas === null)Sem base anterior @endif
            </span>
        </x-slot:bottom>
    </x-kpi-card>
    <x-kpi-card label="Ticket Médio" :value="Fin::money($ticketMedio)" icon="receipt_long" accent="slate">
        <x-slot:bottom>
            <span class="text-[12px] text-gray-400 num">{{ $qtdDespesas }} despesa(s) pagas</span>
        </x-slot:bottom>
    </x-kpi-card>
    <x-kpi-card label="Contas Vencidas" :value="Fin::money($vencidasTotal)" icon="event_busy" :accent="$vencidasQtd > 0 ? 'red' : 'slate'">
        <x-slot:bottom>
            <span class="text-[12px] text-gray-400 num">{{ $vencidasQtd }} conta(s) em atraso</span>
        </x-slot:bottom>
    </x-kpi-card>
</div>

    {{-- FLUXO --}}
    <x-section-card title="Fluxo Financeiro Semanal" :subtitle="'Entradas e saídas pagas · '.$mes">
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

<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    {{-- EVOLUÇÃO 6 MESES --}}
    <x-section-card class="lg:col-span-8" title="Evolução 6 Meses" subtitle="Receitas e despesas pagas · linha = resultado">
        <div class="relative h-72">
            <canvas id="chart-evolucao"></canvas>
        </div>
    </x-section-card>

    {{-- DESPESAS POR CATEGORIA --}}
    <x-section-card class="lg:col-span-4" title="Despesas por Categoria" subtitle="Top do mês · pagas + pendentes">
        @if(array_sum($doughValues) > 0)
        <div class="relative h-72">
            <canvas id="chart-categorias"></canvas>
        </div>
        @else
        <div class="text-center py-10 text-gray-400">
            <span class="material-symbols-outlined text-[40px] text-gray-300">pie_chart</span>
            <p class="text-[13px] font-bold mt-2 text-gray-500">Sem despesas no mês</p>
        </div>
        @endif
    </x-section-card>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    {{-- SCORE --}}
    <x-section-card class="lg:col-span-4" title="Saúde Financeira" subtitle="Score 0–100 do mês">
        <div class="text-center py-4">
            <p class="num text-[56px] leading-none font-extrabold tracking-tight {{ $score >= 80 ? 'text-emerald-600' : ($score >= 60 ? 'text-blue-600' : ($score >= 40 ? 'text-amber-500' : 'text-red-500')) }}">{{ $score }}</p>
            <p class="mt-2"><x-badge :type="$scoreType">{{ $scoreLabel }}</x-badge></p>
            <p class="text-[12px] text-gray-400 mt-3 leading-relaxed">Superávit, poupança, vencidas,<br>uso do limite e concentração.</p>
        </div>
    </x-section-card>

    {{-- INSIGHTS --}}
    <x-section-card class="lg:col-span-8" title="Insights Automáticos" subtitle="Conclusões geradas a partir dos seus dados">
        <div class="space-y-3">
            @foreach($insights as $in)
            @php
                $tone = [
                    'success' => 'bg-emerald-50 text-emerald-600',
                    'info' => 'bg-blue-50 text-blue-600',
                    'warning' => 'bg-amber-50 text-amber-600',
                    'critical' => 'bg-red-50 text-red-500',
                ][$in['type']] ?? 'bg-slate-100 text-gray-500';
            @endphp
            <div class="flex items-start gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50/50">
                <span class="w-9 h-9 rounded-lg grid place-items-center shrink-0 {{ $tone }}">
                    <span class="material-symbols-outlined text-[19px]" style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20">{{ $in['icon'] }}</span>
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-extrabold text-gray-800">{{ $in['title'] }}</p>
                    <p class="text-[12px] text-gray-500 mt-0.5 leading-relaxed">{{ $in['text'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </x-section-card>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    if (!window.Chart) return;
    Chart.defaults.font.family = 'Montserrat, sans-serif';
    Chart.defaults.color = '#94a3b8';
    var grid = { color: '#f1f5f9' };

    var evo = document.getElementById('chart-evolucao');
    if (evo) {
        new Chart(evo, {
            type: 'bar',
            data: {
                labels: @json($evoLabels),
                datasets: [
                    { label: 'Receitas', data: @json($evoReceitas), backgroundColor: '#059669', borderRadius: 6, maxBarThickness: 26 },
                    { label: 'Despesas', data: @json($evoDespesas), backgroundColor: '#f1f5f9', hoverBackgroundColor: '#fca5a5', borderRadius: 6, maxBarThickness: 26 },
                    { label: 'Resultado', data: @json($evoResultado), type: 'line', borderColor: '#0f172a', backgroundColor: '#0f172a', tension: 0.35, pointRadius: 3 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } } },
                scales: {
                    x: { grid: { display: false } },
                    y: { grid: grid, ticks: { callback: function (v) { return 'R$ ' + Number(v).toLocaleString('pt-BR'); } } }
                }
            }
        });
    }

    var dough = document.getElementById('chart-categorias');
    if (dough) {
        new Chart(dough, {
            type: 'doughnut',
            data: {
                labels: @json($doughLabels),
                datasets: [{
                    data: @json($doughValues),
                    backgroundColor: ['#059669', '#3B82F6', '#F59E0B', '#EC4899', '#8B5CF6', '#06B6D4', '#F97316'],
                    borderWidth: 2, borderColor: '#fff'
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '62%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true, font: { size: 11 } } } }
            }
        });
    }
})();
</script>
@endpush
