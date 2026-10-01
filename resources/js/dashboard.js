const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const fmtMoney = (v) =>
    v == null
        ? 'R$ 0,00'
        : 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function baseOptions(cfg) {
    const isDonut = cfg.type === 'donut' || cfg.type === 'pie';

    const opts = {
        chart: {
            type: isDonut ? 'donut' : cfg.type,
            height: cfg.height || 320,
            fontFamily: "'Montserrat', sans-serif",
            toolbar: { show: false },
            animations: { enabled: !reducedMotion },
            background: 'transparent',
            zoom: { enabled: false },
        },
        colors: cfg.colors,
        dataLabels: { enabled: false },
        legend:
            cfg.legend === false
                ? { show: false }
                : {
                      position: 'bottom',
                      fontSize: '12px',
                      fontWeight: 600,
                      labels: { colors: '#64748b' },
                      markers: { size: 5 },
                  },
        tooltip: {
            theme: 'light',
            y: cfg.yformat === 'money' ? { formatter: fmtMoney } : undefined,
        },
    };

    if (!isDonut) {
        opts.stroke = { curve: 'smooth', width: 2 };
        opts.grid = {
            borderColor: '#e2e8f0',
            strokeDashArray: 4,
            padding: { left: 8, right: 8 },
        };
        opts.xaxis = {
            categories: cfg.labels,
            labels: { style: { colors: '#94a3b8', fontSize: '11px', fontWeight: 600 } },
        };
        opts.yaxis = {
            labels: {
                formatter: (v) => (cfg.yformat === 'money' ? fmtMoney(v) : v),
                style: { colors: '#94a3b8', fontSize: '11px' },
            },
        };
        if (cfg.horizontal) {
            opts.plotOptions = { bar: { horizontal: true, borderRadius: 6, barHeight: '60%' } };
        }
    }

    return opts;
}

function render(ApexCharts, el, cfg) {
    const total = cfg.series.reduce(
        (s, sr) => s + (Array.isArray(sr.data) ? sr.data.reduce((a, b) => a + (Number(b) || 0), 0) : 0),
        0,
    );

    if (total === 0) {
        el.innerHTML =
            '<div class="h-full min-h-[220px] grid place-items-center text-[13px] text-gray-400 font-medium">Sem dados no período selecionado.</div>';
        return;
    }

    const opts = baseOptions(cfg);

    if (cfg.type === 'donut' || cfg.type === 'pie') {
        opts.series = cfg.series[0]?.data || cfg.series;
        opts.labels = cfg.labels;
        opts.plotOptions = {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            formatter: () => fmtMoney(total),
                            fontSize: '13px',
                            fontWeight: 700,
                            color: '#0f172a',
                        },
                    },
                },
            },
        };
    } else {
        opts.series = cfg.series.map((s) => ({
            name: s.name,
            data: s.data,
            type: s.type || cfg.type,
        }));
    }

    const chart = new ApexCharts(el, opts);
    chart.render();

    return chart;
}

document.addEventListener('DOMContentLoaded', async () => {
    const charts = window.DashboardCharts || {};
    const entries = Object.entries(charts).filter(([id]) => document.getElementById(id));

    if (entries.length === 0) return;

    // ApexCharts carregado sob demanda (chunk só baixado na dashboard).
    let ApexCharts;
    try {
        ApexCharts = (await import('apexcharts')).default;
    } catch {
        return; // falha ao carregar: os cards permanecem estáticos
    }

    entries.forEach(([id, cfg]) => {
        const el = document.getElementById(id);
        if (el) render(ApexCharts, el, cfg);
    });
});