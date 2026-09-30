@extends('layouts.app')
@section('title','Mercado')
@section('breadcrumb','Patrimônio / Mercado')
@section('nav-active','investimentos')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-[24px] font-extrabold tracking-tight text-gray-900">Mercado</h1>
        <p class="text-[13px] text-gray-400 mt-0.5 font-medium">Ações do Ibovespa, FIIs e renda fixa · fonte: Brapi/Yahoo.</p>
    </div>
    <div class="flex gap-2 flex-wrap">
        <span id="mercado-updated" class="text-[11px] font-semibold text-gray-400 num self-center"></span>
        <x-btn-link :href="route('investimentos')" color="ghost" icon="savings">Meus investimentos</x-btn-link>
    </div>
</div>

{{-- Indicadores (Selic, CDI, Ibovespa, dólar e Bitcoin) --}}
<x-section-card title="Indicadores" subtitle="Fontes: BCB, Yahoo, AwesomeAPI, Binance">
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3" id="market-grid" data-url="{{ route('investimentos.mercado') }}">
        @for($i = 0; $i < 6; $i++)
        <div class="rounded-xl border border-slate-200 bg-white p-3.5" aria-hidden="true">
            <div class="flex items-center gap-1.5">
                <div class="w-7 h-7 rounded-lg bg-slate-100 animate-pulse shrink-0"></div>
                <div class="h-3 w-16 rounded bg-slate-100 animate-pulse"></div>
            </div>
            <div class="h-6 w-3/4 rounded bg-slate-100 animate-pulse mt-3"></div>
            <div class="h-3 w-1/2 rounded bg-slate-100 animate-pulse mt-2"></div>
        </div>
        @endfor
    </div>
</x-section-card>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <x-section-card title="Em Alta" subtitle="Maiores altas do dia (ações + FIIs)">
        <div class="space-y-2.5" id="mercado-altas">
            @for($i = 0; $i < 3; $i++)
            <div class="flex items-center gap-3 p-3 rounded-xl border border-slate-200" aria-hidden="true">
                <div class="w-9 h-9 rounded-lg bg-slate-100 animate-pulse shrink-0"></div>
                <div class="flex-1 min-w-0">
                    <div class="h-3.5 w-2/3 rounded bg-slate-100 animate-pulse"></div>
                    <div class="h-3 w-1/3 rounded bg-slate-100 animate-pulse mt-1.5"></div>
                </div>
                <div class="h-4 w-16 rounded bg-slate-100 animate-pulse"></div>
            </div>
            @endfor
        </div>
    </x-section-card>

    <x-section-card title="Em Queda" subtitle="Maiores quedas do dia (ações + FIIs)">
        <div class="space-y-2.5" id="mercado-quedas">
            @for($i = 0; $i < 3; $i++)
            <div class="flex items-center gap-3 p-3 rounded-xl border border-slate-200" aria-hidden="true">
                <div class="w-9 h-9 rounded-lg bg-slate-100 animate-pulse shrink-0"></div>
                <div class="flex-1 min-w-0">
                    <div class="h-3.5 w-2/3 rounded bg-slate-100 animate-pulse"></div>
                    <div class="h-3 w-1/3 rounded bg-slate-100 animate-pulse mt-1.5"></div>
                </div>
                <div class="h-4 w-16 rounded bg-slate-100 animate-pulse"></div>
            </div>
            @endfor
        </div>
    </x-section-card>
</div>

<x-section-card title="Ações do Ibovespa" subtitle="Composição aproximada · clique no cabeçalho para ordenar">
    <x-slot:action>
        <div class="table-search" style="max-width:240px">
            <span class="material-symbols-outlined">search</span>
            <input id="filtro-acoes" placeholder="Buscar ação…" autocomplete="off">
        </div>
    </x-slot:action>
    <div class="overflow-x-auto -mx-5 lg:-mx-6 px-5 lg:px-6">
        <table class="w-full text-left min-w-[560px] table-modern" id="tabela-acoes">
            <thead>
                <tr>
                    <th><button data-sort="code" class="font-extrabold uppercase tracking-widest text-[11px] text-gray-400 hover:text-gray-600">Ativo</button></th>
                    <th class="text-right"><button data-sort="price" class="font-extrabold uppercase tracking-widest text-[11px] text-gray-400 hover:text-gray-600">Preço</button></th>
                    <th class="text-right"><button data-sort="change" class="font-extrabold uppercase tracking-widest text-[11px] text-gray-400 hover:text-gray-600">Variação</button></th>
                </tr>
            </thead>
            <tbody class="text-[13px]" id="mercado-acoes">
                @for($i = 0; $i < 8; $i++)
                <tr aria-hidden="true">
                    <td><div class="h-4 w-40 rounded bg-slate-100 animate-pulse"></div></td>
                    <td class="text-right"><div class="h-4 w-20 rounded bg-slate-100 animate-pulse ml-auto"></div></td>
                    <td class="text-right"><div class="h-4 w-16 rounded bg-slate-100 animate-pulse ml-auto"></div></td>
                </tr>
                @endfor
            </tbody>
        </table>
    </div>
    <div class="mt-4 flex flex-wrap items-center justify-between gap-2" id="pager-acoes">
        <p class="text-[12px] text-gray-400 num" data-pg-info>—</p>
        <div class="flex gap-2">
            <button data-pg-prev class="h-9 px-4 rounded-xl border border-slate-200 text-[12px] font-bold text-slate-600 hover:border-slate-300 disabled:opacity-40 disabled:pointer-events-none">‹ Anterior</button>
            <button data-pg-next class="h-9 px-4 rounded-xl border border-slate-200 text-[12px] font-bold text-slate-600 hover:border-slate-300 disabled:opacity-40 disabled:pointer-events-none">Próxima ›</button>
        </div>
    </div>
</x-section-card>

<x-section-card title="Fundos Imobiliários" subtitle="Top 100 por ordem alfabética · clique no cabeçalho para ordenar">
    <x-slot:action>
        <div class="table-search" style="max-width:240px">
            <span class="material-symbols-outlined">search</span>
            <input id="filtro-fiis" placeholder="Buscar FII…" autocomplete="off">
        </div>
    </x-slot:action>
    <div class="overflow-x-auto -mx-5 lg:-mx-6 px-5 lg:px-6">
        <table class="w-full text-left min-w-[640px] table-modern" id="tabela-fiis">
            <thead>
                <tr>
                    <th><button data-sort="code" class="font-extrabold uppercase tracking-widest text-[11px] text-gray-400 hover:text-gray-600">Fundo</button></th>
                    <th>Segmento</th>
                    <th class="text-right"><button data-sort="price" class="font-extrabold uppercase tracking-widest text-[11px] text-gray-400 hover:text-gray-600">Preço</button></th>
                    <th class="text-right"><button data-sort="change" class="font-extrabold uppercase tracking-widest text-[11px] text-gray-400 hover:text-gray-600">Variação</button></th>
                </tr>
            </thead>
            <tbody class="text-[13px]" id="mercado-fiis">
                @for($i = 0; $i < 8; $i++)
                <tr aria-hidden="true">
                    <td><div class="h-4 w-40 rounded bg-slate-100 animate-pulse"></div></td>
                    <td><div class="h-4 w-24 rounded bg-slate-100 animate-pulse"></div></td>
                    <td class="text-right"><div class="h-4 w-20 rounded bg-slate-100 animate-pulse ml-auto"></div></td>
                    <td class="text-right"><div class="h-4 w-16 rounded bg-slate-100 animate-pulse ml-auto"></div></td>
                </tr>
                @endfor
            </tbody>
        </table>
    </div>
    <div class="mt-4 flex flex-wrap items-center justify-between gap-2" id="pager-fiis">
        <p class="text-[12px] text-gray-400 num" data-pg-info>—</p>
        <div class="flex gap-2">
            <button data-pg-prev class="h-9 px-4 rounded-xl border border-slate-200 text-[12px] font-bold text-slate-600 hover:border-slate-300 disabled:opacity-40 disabled:pointer-events-none">‹ Anterior</button>
            <button data-pg-next class="h-9 px-4 rounded-xl border border-slate-200 text-[12px] font-bold text-slate-600 hover:border-slate-300 disabled:opacity-40 disabled:pointer-events-none">Próxima ›</button>
        </div>
    </div>
</x-section-card>

<x-section-card title="Renda Fixa de Referência" subtitle="Estimativas a partir do CDI do dia · base para comparar seu CDB">
    <div class="overflow-x-auto -mx-5 lg:-mx-6 px-5 lg:px-6">
        <table class="w-full text-left min-w-[560px] table-modern">
            <thead>
                <tr>
                    <th>Aplicação</th>
                    <th class="text-right">Taxa a.a.</th>
                    <th class="text-right">R$ 10 mil em 12 meses</th>
                </tr>
            </thead>
            <tbody class="text-[13px]" id="mercado-cdb">
                <tr aria-hidden="true">
                    <td><div class="h-4 w-40 rounded bg-slate-100 animate-pulse"></div></td>
                    <td class="text-right"><div class="h-4 w-20 rounded bg-slate-100 animate-pulse ml-auto"></div></td>
                    <td class="text-right"><div class="h-4 w-24 rounded bg-slate-100 animate-pulse ml-auto"></div></td>
                </tr>
            </tbody>
        </table>
    </div>
    <p class="text-[11px] text-gray-400 mt-3">Valores brutos, sem IR regressivo e taxas. Poupança: regra nova (Selic acima de 8,5% a.a.).</p>
</x-section-card>
@endsection

@push('scripts')
<script>
(function () {
    var grid = document.getElementById('market-grid');
    var DEFS = [
        { key: 'selic', label: 'Selic', icon: 'percent', accent: 'text-emerald-600 bg-emerald-50', suffix: ' p.p.',
          fmt: function (v) { return v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '% a.a.'; } },
        { key: 'cdi', label: 'CDI', icon: 'show_chart', accent: 'text-blue-600 bg-blue-50', suffix: '', nota: 'Selic − 0,10 p.p.',
          fmt: function (v) { return v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '% a.a.'; } },
        { key: 'ibovespa', label: 'Ibovespa', icon: 'trending_up', accent: 'text-indigo-600 bg-indigo-50', suffix: '%',
          fmt: function (v) { return Math.round(v).toLocaleString('pt-BR') + ' pts'; } },
        { key: 'dolar', label: 'Dólar', icon: 'attach_money', accent: 'text-amber-600 bg-amber-50', suffix: '%',
          fmt: function (v) { return 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); } },
        { key: 'btc_usd', label: 'Bitcoin (USD)', icon: 'currency_bitcoin', accent: 'text-orange-600 bg-orange-50', suffix: '%',
          fmt: function (v) { return 'US$ ' + Math.round(v).toLocaleString('pt-BR'); } },
        { key: 'btc_brl', label: 'Bitcoin (BRL)', icon: 'currency_bitcoin', accent: 'text-orange-600 bg-orange-50', suffix: '%',
          fmt: function (v) { return 'R$ ' + Math.round(v).toLocaleString('pt-BR'); } },
    ];

    function indCard(def, d) {
        var head = '<div class="flex items-center gap-1.5">'
            + '<span class="w-7 h-7 rounded-lg grid place-items-center shrink-0 ' + def.accent + '">'
            + '<span class="material-symbols-outlined text-[16px]" style="font-variation-settings:\'FILL\' 1,\'wght\' 400,\'GRAD\' 0,\'opsz\' 20">' + def.icon + '</span></span>'
            + '<p class="text-[11px] font-bold text-gray-500 truncate">' + def.label + '</p></div>';
        if (!d) {
            return '<div class="rounded-xl border border-slate-200 bg-white p-3.5">' + head
                + '<p class="font-bold text-[13px] text-gray-300 mt-2">Indisponível</p>'
                + '<p class="text-[10px] text-gray-300 mt-1">Sem conexão com a fonte</p></div>';
        }
        var body = '<p class="num font-extrabold text-[16px] text-gray-900 mt-2 truncate">' + def.fmt(d.value) + '</p>'
            + '<div class="flex items-center gap-1.5 mt-1.5 flex-wrap">';
        if (d.change !== null && d.change !== undefined) {
            var up = d.change >= 0;
            var ch = (up ? '+' : '') + Number(d.change).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + def.suffix;
            body += '<span class="inline-flex items-center gap-0.5 text-[11px] font-extrabold num ' + (up ? 'text-emerald-600' : 'text-red-500') + '">'
                + '<span class="material-symbols-outlined text-[14px]" style="font-variation-settings:\'FILL\' 1,\'wght\' 600,\'GRAD\' 0,\'opsz\' 20">' + (up ? 'trending_up' : 'trending_down') + '</span>'
                + ch + '</span>';
        }
        if (d.date) body += '<span class="text-[10px] text-gray-400 num">' + d.date + '</span>';
        body += '</div>';
        if (def.nota) body += '<p class="text-[10px] text-gray-300 mt-1">' + def.nota + '</p>';
        return '<div class="rounded-xl border border-slate-200 bg-white p-3.5">' + head + body + '</div>';
    }

    function changeHtml(change) {
        if (change === null || change === undefined) return '<span class="text-[11px] text-gray-300 font-bold">—</span>';
        var up = change >= 0;
        return '<span class="inline-flex items-center gap-0.5 text-[12px] font-extrabold num ' + (up ? 'text-emerald-600' : 'text-red-500') + '">'
            + '<span class="material-symbols-outlined text-[15px]" style="font-variation-settings:\'FILL\' 1,\'wght\' 600,\'GRAD\' 0,\'opsz\' 20">' + (up ? 'trending_up' : 'trending_down') + '</span>'
            + (up ? '+' : '') + Number(change).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%</span>';
    }

    function priceHtml(v) {
        return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function topHtml(s) {
        var up = s.change !== null && s.change !== undefined ? s.change >= 0 : true;
        var tone = s.change !== null && s.change !== undefined && !up ? 'bg-red-50 text-red-500' : 'bg-emerald-50 text-emerald-600';
        return '<div class="flex items-center gap-3 p-3 rounded-xl border border-slate-200">'
            + '<span class="w-9 h-9 rounded-lg grid place-items-center shrink-0 font-extrabold text-[12px] ' + tone + '">' + s.code.replace(/[0-9]+$/, '') + '</span>'
            + '<div class="flex-1 min-w-0"><p class="text-[13px] font-extrabold text-gray-800 truncate">' + s.code + '</p>'
            + '<p class="text-[11px] text-gray-400 truncate">' + (s.label || '') + '</p></div>'
            + '<div class="text-right shrink-0"><p class="num text-[14px] font-extrabold text-gray-900">' + priceHtml(s.price) + '</p>' + changeHtml(s.change) + '</div></div>';
    }

    function failHtml(msg) {
        return '<div class="text-center py-8 text-gray-400"><p class="text-[13px] font-bold text-gray-500">' + msg + '</p>'
            + '<p class="text-[12px] mt-1">Verifique a conexão e recarregue.</p></div>';
    }

    function makeTable(tableId, tbodyId, pagerId, inputId, rowFn, cols) {
        var table = document.getElementById(tableId);
        var tbody = document.getElementById(tbodyId);
        var pager = document.getElementById(pagerId);
        var input = document.getElementById(inputId);
        var st = { items: [], q: '', sortKey: null, sortDir: 1, page: 1, per: 10 };
        var infoEl = pager ? pager.querySelector('[data-pg-info]') : null;
        var prevBtn = pager ? pager.querySelector('[data-pg-prev]') : null;
        var nextBtn = pager ? pager.querySelector('[data-pg-next]') : null;

        if (table) table.querySelectorAll('[data-sort]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var key = btn.dataset.sort;
                st.sortDir = st.sortKey === key ? -st.sortDir : 1;
                st.sortKey = key;
                st.page = 1;
                render();
            });
        });
        if (input) input.addEventListener('input', function () {
            st.q = input.value.toLowerCase().trim();
            st.page = 1;
            render();
        });
        if (prevBtn) prevBtn.addEventListener('click', function () { if (st.page > 1) { st.page--; render(); } });
        if (nextBtn) nextBtn.addEventListener('click', function () { st.page++; render(); });

        function visible() {
            var items = st.items.slice();
            if (st.q) {
                items = items.filter(function (s) {
                    return (s.code + ' ' + (s.label || '') + ' ' + (s.segment || '')).toLowerCase().indexOf(st.q) !== -1;
                });
            }
            if (st.sortKey) {
                items.sort(function (a, b) {
                    var av = a[st.sortKey], bv = b[st.sortKey];
                    if (av === null || av === undefined) return 1;
                    if (bv === null || bv === undefined) return -1;
                    var cmp = typeof av === 'string' ? av.localeCompare(bv, 'pt-BR') : av - bv;
                    return cmp * st.sortDir;
                });
            }
            return items;
        }

        function render() {
            var items = visible();
            var pages = Math.max(1, Math.ceil(items.length / st.per));
            if (st.page > pages) st.page = pages;
            var slice = items.slice((st.page - 1) * st.per, st.page * st.per);
            tbody.innerHTML = slice.length
                ? slice.map(rowFn).join('')
                : '<tr><td colspan="' + cols + '"><div class="text-center py-8 text-gray-400"><p class="text-[13px] font-bold text-gray-500">Nenhum ativo encontrado.</p></div></td></tr>';
            if (infoEl) infoEl.textContent = items.length
                ? 'Página ' + st.page + ' de ' + pages + ' · ' + items.length + ' registro(s)'
                : 'Nenhum registro';
            if (prevBtn) prevBtn.disabled = st.page <= 1;
            if (nextBtn) nextBtn.disabled = st.page >= pages;
        }

        return {
            setItems: function (items) { st.items = items || []; st.page = 1; render(); },
            render: render
        };
    }

    var DATA = { acoes: [], fiis: [], indicators: {} };

    function acaoRow(s) {
        return '<tr><td><span class="font-bold text-gray-800">' + s.code + '</span>'
            + '<span class="block text-[11px] font-medium text-gray-400">' + (s.label || '') + '</span></td>'
            + '<td class="text-right font-extrabold num text-gray-800">' + priceHtml(s.price) + '</td>'
            + '<td class="text-right">' + changeHtml(s.change) + '</td></tr>';
    }

    function fiiRow(s) {
        return '<tr><td><span class="font-bold text-gray-800">' + s.code + '</span>'
            + '<span class="block text-[11px] font-medium text-gray-400">' + (s.label || '') + '</span></td>'
            + '<td class="text-gray-500 text-[12px]">' + (s.segment || '—') + '</td>'
            + '<td class="text-right font-extrabold num text-gray-800">' + priceHtml(s.price) + '</td>'
            + '<td class="text-right">' + changeHtml(s.change) + '</td></tr>';
    }

    var tblAcoes = makeTable('tabela-acoes', 'mercado-acoes', 'pager-acoes', 'filtro-acoes', acaoRow, 3);
    var tblFiis = makeTable('tabela-fiis', 'mercado-fiis', 'pager-fiis', 'filtro-fiis', fiiRow, 4);

    function renderCdb(indicators) {
        var tbody = document.getElementById('mercado-cdb');
        var cdi = indicators && indicators.cdi ? Number(indicators.cdi.value) : null;
        var selic = indicators && indicators.selic ? Number(indicators.selic.value) : null;
        if (cdi === null || isNaN(cdi)) {
            tbody.innerHTML = '<tr><td colspan="3">' + failHtml('CDI indisponível no momento.') + '</td></tr>';
            return;
        }
        var fmtPct = function (v) { return v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '% a.a.'; };
        var fmtBrl = function (v) { return 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
        var rows = [90, 100, 110, 120, 130].map(function (p) {
            var taxa = cdi * p / 100;
            var futuro = 10000 * (1 + taxa / 100);
            var star = p === 100 ? ' <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 rounded px-1.5 py-0.5 ml-1">referência</span>' : '';
            return '<tr><td class="font-bold text-gray-800">CDB ' + p + '% do CDI' + star + '</td>'
                + '<td class="text-right font-extrabold num text-gray-800">' + fmtPct(taxa) + '</td>'
                + '<td class="text-right num text-gray-500">' + fmtBrl(futuro) + '</td></tr>';
        });
        var poup = (selic !== null && !isNaN(selic)) ? (selic > 8.5 ? 6.17 : Math.round(selic * 0.7 * 100) / 100) : null;
        if (poup !== null) {
            rows.push('<tr><td class="font-bold text-gray-800">Poupança <span class="text-[10px] font-bold text-gray-400 bg-slate-100 rounded px-1.5 py-0.5 ml-1">estimativa</span></td>'
                + '<td class="text-right font-extrabold num text-gray-800">' + fmtPct(poup) + '</td>'
                + '<td class="text-right num text-gray-500">' + fmtBrl(10000 * (1 + poup / 100)) + '</td></tr>');
        }
        tbody.innerHTML = rows.join('');
    }

    fetch(@json(route('mercado.dados')), { headers: { 'Accept': 'application/json' } })
        .then(function (r) { if (!r.ok) throw new Error('http ' + r.status); return r.json(); })
        .then(function (payload) {
            var snap = payload.indicators || {};
            document.getElementById('market-grid').innerHTML = DEFS_INNER(snap);
            if (payload.fetched_at || snap.fetched_at) {
                var el = document.getElementById('market-updated');
                if (el) el.textContent = 'Atualizado em ' + (payload.fetched_at || snap.fetched_at);
            }

            DATA.acoes = (payload.acoes || []).filter(function (s) { return s && s.price !== null && s.price !== undefined; });
            DATA.fiis = (payload.fiis || []).filter(function (s) { return s && s.price !== null && s.price !== undefined; });

            var all = DATA.acoes.concat(DATA.fiis).filter(function (s) { return s.change !== null && s.change !== undefined; });
            var altas = all.filter(function (s) { return s.change >= 0; }).sort(function (a, b) { return b.change - a.change; }).slice(0, 3);
            var quedas = all.filter(function (s) { return s.change < 0; }).sort(function (a, b) { return a.change - b.change; }).slice(0, 3);
            document.getElementById('mercado-altas').innerHTML = altas.length ? altas.map(topHtml).join('') : failHtml('Sem altas hoje.');
            document.getElementById('mercado-quedas').innerHTML = quedas.length ? quedas.map(topHtml).join('') : failHtml('Sem quedas hoje.');

            tblAcoes.setItems(DATA.acoes);
            tblFiis.setItems(DATA.fiis);
            renderCdb(snap);
        })
        .catch(function () {
            document.getElementById('market-grid').innerHTML = DEFS.map(function (def) { return indCard(def, null); }).join('');
            document.getElementById('mercado-altas').innerHTML = failHtml('Não foi possível carregar.');
            document.getElementById('mercado-quedas').innerHTML = failHtml('Não foi possível carregar.');
            document.getElementById('mercado-acoes').innerHTML = '<tr><td colspan="3">' + failHtml('Não foi possível carregar.') + '</td></tr>';
            document.getElementById('mercado-fiis').innerHTML = '<tr><td colspan="4">' + failHtml('Não foi possível carregar.') + '</td></tr>';
            document.getElementById('mercado-cdb').innerHTML = '<tr><td colspan="3">' + failHtml('Não foi possível carregar.') + '</td></tr>';
        });

    var DEFS = [
        { key: 'selic', label: 'Selic', icon: 'percent', accent: 'text-emerald-600 bg-emerald-50', suffix: ' p.p.',
          fmt: function (v) { return v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '% a.a.'; } },
        { key: 'cdi', label: 'CDI', icon: 'show_chart', accent: 'text-blue-600 bg-blue-50', suffix: '', nota: 'Selic − 0,10 p.p.',
          fmt: function (v) { return v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '% a.a.'; } },
        { key: 'ibovespa', label: 'Ibovespa', icon: 'trending_up', accent: 'text-indigo-600 bg-indigo-50', suffix: '%',
          fmt: function (v) { return Math.round(v).toLocaleString('pt-BR') + ' pts'; } },
        { key: 'dolar', label: 'Dólar', icon: 'attach_money', accent: 'text-amber-600 bg-amber-50', suffix: '%',
          fmt: function (v) { return 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); } },
        { key: 'btc_usd', label: 'Bitcoin (USD)', icon: 'currency_bitcoin', accent: 'text-orange-600 bg-orange-50', suffix: '%',
          fmt: function (v) { return 'US$ ' + Math.round(v).toLocaleString('pt-BR'); } },
        { key: 'btc_brl', label: 'Bitcoin (BRL)', icon: 'currency_bitcoin', accent: 'text-orange-600 bg-orange-50', suffix: '%',
          fmt: function (v) { return 'R$ ' + Math.round(v).toLocaleString('pt-BR'); } },
    ];

    function indCard(def, d) {
        var head = '<div class="flex items-center gap-1.5">'
            + '<span class="w-7 h-7 rounded-lg grid place-items-center shrink-0 ' + def.accent + '">'
            + '<span class="material-symbols-outlined text-[16px]" style="font-variation-settings:\'FILL\' 1,\'wght\' 400,\'GRAD\' 0,\'opsz\' 20">' + def.icon + '</span></span>'
            + '<p class="text-[11px] font-bold text-gray-500 truncate">' + def.label + '</p></div>';
        if (!d) {
            return '<div class="rounded-xl border border-slate-200 bg-white p-3.5">' + head
                + '<p class="font-bold text-[13px] text-gray-300 mt-2">Indisponível</p>'
                + '<p class="text-[10px] text-gray-300 mt-1">Sem conexão com a fonte</p></div>';
        }
        var body = '<p class="num font-extrabold text-[16px] text-gray-900 mt-2 truncate">' + def.fmt(d.value) + '</p>'
            + '<div class="flex items-center gap-1.5 mt-1.5 flex-wrap">';
        if (d.change !== null && d.change !== undefined) {
            var up = d.change >= 0;
            var ch = (up ? '+' : '') + Number(d.change).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + def.suffix;
            body += '<span class="inline-flex items-center gap-0.5 text-[11px] font-extrabold num ' + (up ? 'text-emerald-600' : 'text-red-500') + '">'
                + '<span class="material-symbols-outlined text-[14px]" style="font-variation-settings:\'FILL\' 1,\'wght\' 600,\'GRAD\' 0,\'opsz\' 20">' + (up ? 'trending_up' : 'trending_down') + '</span>'
                + ch + '</span>';
        }
        if (d.date) body += '<span class="text-[10px] text-gray-400 num">' + d.date + '</span>';
        body += '</div>';
        if (def.nota) body += '<p class="text-[10px] text-gray-300 mt-1">' + def.nota + '</p>';
        return '<div class="rounded-xl border border-slate-200 bg-white p-3.5">' + head + body + '</div>';
    }

    function DEFS_INNER(snap) {
        return DEFS.map(function (def) { return indCard(def, snap[def.key] || null); }).join('');
    }
})();
</script>
@endpush
