<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prumo — Gestão financeira para você e sua família</title>
    <meta name="description" content="Prumo: contas, cartões, investimentos e orçamento da família em um só lugar. Bot no Telegram, gráficos inteligentes e 14 dias grátis.">
    <meta name="theme-color" content="#064E3B">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Montserrat', system-ui, sans-serif; }
        .grad-text { background: linear-gradient(100deg, #059669 10%, #0d9488 55%, #2563eb 100%); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .hero-grid-bg {
            background-image: radial-gradient(circle at 1px 1px, rgba(5,150,105,.14) 1px, transparent 0);
            background-size: 26px 26px;
            mask-image: radial-gradient(ellipse 90% 70% at 50% 30%, black 55%, transparent 100%);
            -webkit-mask-image: radial-gradient(ellipse 90% 70% at 50% 30%, black 55%, transparent 100%);
        }
        @keyframes floaty { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
        @keyframes floaty2 { 0%,100% { transform: translateY(0) rotate(-1.5deg); } 50% { transform: translateY(-8px) rotate(1deg); } }
        .float-a { animation: floaty 6s ease-in-out infinite; }
        .float-b { animation: floaty2 7s ease-in-out infinite; }
        .reveal { opacity: 0; transform: translateY(26px); transition: opacity .7s ease, transform .7s ease; }
        .reveal.on { opacity: 1; transform: none; }
        @media (prefers-reduced-motion: reduce) {
            .float-a, .float-b { animation: none; }
            .reveal { opacity: 1; transform: none; transition: none; }
        }
        .bar { border-radius: 6px 6px 3px 3px; }
        details.faq summary::-webkit-details-marker { display: none; }
        details.faq[open] .faq-plus { transform: rotate(45deg); }
        .faq-plus { transition: transform .2s ease; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased">

{{-- ═══ NAVBAR ═══ --}}
<header class="sticky top-0 z-40 border-b border-slate-200/70 bg-white/80 backdrop-blur-xl">
    <div class="max-w-6xl mx-auto px-5 h-[68px] flex items-center justify-between gap-3">
        <a href="/" class="flex items-center gap-2.5 shrink-0">
            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-600 to-emerald-800 text-white grid place-items-center shadow-lg shadow-emerald-600/25">
                <span class="material-symbols-outlined text-[20px]">savings</span>
            </span>
            <span class="text-[18px] font-extrabold tracking-tight">Prumo</span>
        </a>
        <nav class="hidden md:flex items-center gap-7 text-[13.5px] font-bold text-slate-600">
            <a href="#recursos" class="hover:text-emerald-700 transition">Recursos</a>
            <a href="#como-funciona" class="hover:text-emerald-700 transition">Como funciona</a>
            <a href="#planos" class="hover:text-emerald-700 transition">Planos</a>
            <a href="#faq" class="hover:text-emerald-700 transition">Dúvidas</a>
        </nav>
        <div class="flex items-center gap-2">
            <a href="{{ route('login') }}" class="h-10 px-4 hidden sm:inline-flex items-center text-[13px] font-extrabold text-slate-700 hover:text-slate-900 transition">Entrar</a>
            <a href="{{ route('cadastro') }}" class="h-10 px-5 inline-flex items-center gap-1.5 text-[13px] font-extrabold text-white bg-slate-900 hover:bg-emerald-700 rounded-xl transition shadow-sm">
                Testar grátis <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </a>
        </div>
    </div>
</header>

{{-- ═══ HERO ═══ --}}
<section class="relative overflow-hidden">
    <div class="absolute inset-0 hero-grid-bg"></div>
    <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[720px] h-[380px] rounded-full bg-emerald-300/30 blur-3xl pointer-events-none"></div>

    <div class="relative max-w-6xl mx-auto px-5 pt-14 md:pt-20 pb-10 grid lg:grid-cols-2 gap-12 items-center">
        <div class="reveal on">
            <span class="inline-flex items-center gap-1.5 text-[12px] font-extrabold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-full px-3.5 py-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Novo · Bot no Telegram com OCR de comprovantes
            </span>
            <h1 class="mt-5 text-[38px] md:text-[54px] leading-[1.04] font-black tracking-tight">
                O dinheiro da família, <span class="grad-text">no prumo.</span>
            </h1>
            <p class="mt-5 text-[15.5px] md:text-[17px] text-slate-500 leading-relaxed max-w-lg">
                Gestão financeira para você e sua família: contas, cartões, investimentos e orçamento em um só lugar — com gráficos claros e lançamentos até pelo Telegram.
            </p>
            <div class="mt-7 flex flex-wrap items-center gap-3">
                <a href="{{ route('cadastro') }}" class="h-12 px-7 inline-flex items-center gap-2 text-[15px] font-extrabold text-white bg-emerald-600 hover:bg-emerald-700 rounded-2xl shadow-xl shadow-emerald-600/25 transition">
                    Criar conta grátis <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </a>
                <a href="#como-funciona" class="h-12 px-6 inline-flex items-center gap-2 text-[14px] font-extrabold text-slate-700 bg-white border border-slate-200 hover:border-slate-300 rounded-2xl shadow-sm transition">
                    <span class="material-symbols-outlined text-[18px] text-emerald-600">play_circle</span> Ver como funciona
                </a>
            </div>
            <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-[12.5px] font-bold text-slate-500">
                <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-emerald-600">check_circle</span> 14 dias grátis</span>
                <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-emerald-600">check_circle</span> Sem cartão de crédito</span>
                <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-emerald-600">check_circle</span> Cancele quando quiser</span>
            </div>
            <div class="mt-8 flex items-center gap-6">
                <div><p class="text-[22px] font-black num" data-count>14</p><p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">dias de teste</p></div>
                <div class="w-px h-9 bg-slate-200"></div>
                <div><p class="text-[22px] font-black num">100%</p><p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">seus dados, seus</p></div>
                <div class="w-px h-9 bg-slate-200"></div>
                <div><p class="text-[22px] font-black num">LGPD</p><p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">privacidade total</p></div>
            </div>
        </div>

        {{-- Mock do produto --}}
        <div class="relative reveal on">
            <div class="relative rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10 overflow-hidden">
                <div class="flex items-center gap-1.5 px-4 h-11 border-b border-slate-100 bg-slate-50/60">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-400/80"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400/80"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400/80"></span>
                    <span class="ml-2 text-[11px] font-bold text-slate-400">app.prumo.com.br · Visão geral</span>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-3">
                            <p class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-600">Saldo</p>
                            <p class="text-[16px] font-black num mt-1">R$ 8.420</p>
                        </div>
                        <div class="rounded-2xl bg-red-50 border border-red-100 p-3">
                            <p class="text-[10px] font-extrabold uppercase tracking-wider text-red-500">Despesas</p>
                            <p class="text-[16px] font-black num mt-1">R$ 3.190</p>
                        </div>
                        <div class="rounded-2xl bg-blue-50 border border-blue-100 p-3">
                            <p class="text-[10px] font-extrabold uppercase tracking-wider text-blue-600">Investido</p>
                            <p class="text-[16px] font-black num mt-1">R$ 12,4 mil</p>
                        </div>
                    </div>
                    <div class="mt-4 rounded-2xl border border-slate-100 bg-slate-50/50 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-[12px] font-extrabold">Receitas × Despesas</p>
                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-2 py-0.5">+12% no mês</span>
                        </div>
                        <div class="mt-3 flex items-end gap-1.5 h-24">
                            <div class="bar flex-1 bg-emerald-200" style="height:45%"></div>
                            <div class="bar flex-1 bg-emerald-400" style="height:62%"></div>
                            <div class="bar flex-1 bg-emerald-500" style="height:52%"></div>
                            <div class="bar flex-1 bg-red-300" style="height:70%"></div>
                            <div class="bar flex-1 bg-emerald-500" style="height:84%"></div>
                            <div class="bar flex-1 bg-slate-900" style="height:96%"></div>
                            <div class="bar flex-1 bg-emerald-400" style="height:74%"></div>
                            <div class="bar flex-1 bg-emerald-500" style="height:88%"></div>
                        </div>
                    </div>
                    <div class="mt-3 space-y-2">
                        <div class="flex items-center gap-3 rounded-xl border border-slate-100 p-2.5">
                            <span class="w-8 h-8 rounded-lg bg-red-50 text-red-500 grid place-items-center shrink-0"><span class="material-symbols-outlined text-[17px]">shopping_cart</span></span>
                            <div class="flex-1 min-w-0"><p class="text-[12px] font-bold truncate">Mercado da semana</p><p class="text-[10px] text-slate-400">Alimentação · hoje</p></div>
                            <p class="text-[12px] font-black num text-red-500">−R$ 486,20</p>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-slate-100 p-2.5">
                            <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 grid place-items-center shrink-0"><span class="material-symbols-outlined text-[17px]">payments</span></span>
                            <div class="flex-1 min-w-0"><p class="text-[12px] font-bold truncate">Salário · Maria</p><p class="text-[10px] text-slate-400">Receita · dia 5</p></div>
                            <p class="text-[12px] font-black num text-emerald-600">+R$ 4.800,00</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card flutuante: Telegram --}}
            <div class="float-a absolute -left-3 md:-left-8 top-16 w-52 rounded-2xl bg-slate-900 text-white p-3.5 shadow-2xl">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-full bg-emerald-500 grid place-items-center shrink-0"><span class="material-symbols-outlined text-[15px]">send</span></span>
                    <p class="text-[11px] font-extrabold">Bot no Telegram</p>
                </div>
                <p class="mt-2 text-[11px] leading-relaxed text-slate-300">“Mercado 486,20 hoje”</p>
                <p class="mt-1.5 inline-flex items-center gap-1 text-[10px] font-bold text-emerald-300"><span class="material-symbols-outlined text-[13px]">check_circle</span> Lançado na hora</p>
            </div>

            {{-- Card flutuante: fatura --}}
            <div class="float-b absolute -right-2 md:-right-6 -bottom-5 w-56 rounded-2xl bg-white border border-slate-200 p-3.5 shadow-2xl">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-orange-50 text-orange-500 grid place-items-center shrink-0"><span class="material-symbols-outlined text-[15px]">credit_card</span></span>
                    <p class="text-[11px] font-extrabold">Fatura a vencer</p>
                </div>
                <p class="mt-2 text-[15px] font-black num">R$ 1.240,00</p>
                <p class="text-[10px] font-bold text-orange-500">vence em 6 dias</p>
            </div>
        </div>
    </div>
</section>

{{-- ═══ FAIXA DE CONFIANÇA ═══ --}}
<section class="border-y border-slate-200 bg-white">
    <div class="max-w-6xl mx-auto px-5 py-5 flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-[12.5px] font-extrabold text-slate-500">
        <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-[17px] text-emerald-600">lock</span> Senhas com criptografia</span>
        <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-[17px] text-emerald-600">verified_user</span> Conformidade LGPD</span>
        <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-[17px] text-emerald-600">groups</span> Feito para famílias</span>
        <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-[17px] text-emerald-600">smartphone</span> Funciona no celular</span>
    </div>
</section>

{{-- ═══ RECURSOS (bento) ═══ --}}
<section id="recursos" class="max-w-6xl mx-auto px-5 py-20">
    <div class="reveal max-w-2xl">
        <p class="text-[12px] font-extrabold uppercase tracking-[0.18em] text-emerald-600">Recursos</p>
        <h2 class="mt-2 text-[30px] md:text-[38px] font-black tracking-tight leading-tight">Tudo que a sua família precisa para sair do aperto</h2>
        <p class="mt-3 text-[15px] text-slate-500 leading-relaxed">Sem planilha, sem papel, sem briga no fim do mês. Cada pessoa lança o seu — e todo mundo enxerga o todo.</p>
    </div>

    <div class="mt-10 grid md:grid-cols-3 gap-5">
        <div class="reveal md:col-span-2 rounded-3xl bg-slate-900 text-white p-8 relative overflow-hidden">
            <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-emerald-500/25 blur-3xl"></div>
            <span class="relative w-11 h-11 rounded-xl bg-white/10 grid place-items-center"><span class="material-symbols-outlined text-[22px] text-emerald-300">pie_chart</span></span>
            <h3 class="relative font-extrabold text-[19px] mt-4">Gráficos que qualquer pessoa entende</h3>
            <p class="relative text-[13.5px] text-slate-300 mt-2 leading-relaxed max-w-md">Receitas × despesas no ano, gastos por categoria, evolução de investimentos e detalhes por membro — sem jargão financeiro.</p>
            <div class="relative mt-5 flex items-end gap-2 h-28 max-w-md">
                <div class="bar flex-1 bg-white/15" style="height:40%"></div>
                <div class="bar flex-1 bg-white/15" style="height:58%"></div>
                <div class="bar flex-1 bg-emerald-400" style="height:76%"></div>
                <div class="bar flex-1 bg-white/15" style="height:52%"></div>
                <div class="bar flex-1 bg-emerald-300" style="height:92%"></div>
            </div>
        </div>
        <div class="reveal rounded-3xl bg-white border border-slate-200 p-8 hover:shadow-xl hover:-translate-y-1 transition-all">
            <span class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 grid place-items-center"><span class="material-symbols-outlined text-[22px]">send</span></span>
            <h3 class="font-extrabold text-[17px] mt-4">Lance pelo Telegram</h3>
            <p class="text-[13.5px] text-slate-500 mt-2 leading-relaxed">Envie “Mercado 486,20” ou a foto do comprovante: o bot lê, classifica e já deixa tudo pronto para confirmar.</p>
        </div>
        <div class="reveal rounded-3xl bg-white border border-slate-200 p-8 hover:shadow-xl hover:-translate-y-1 transition-all">
            <span class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 grid place-items-center"><span class="material-symbols-outlined text-[22px]">account_balance_wallet</span></span>
            <h3 class="font-extrabold text-[17px] mt-4">Contas, cartões e extratos</h3>
            <p class="text-[13.5px] text-slate-500 mt-2 leading-relaxed">Saldos automáticos, faturas a vencer, parcelas e transferências — com a cara do seu banco.</p>
        </div>
        <div class="reveal rounded-3xl bg-white border border-slate-200 p-8 hover:shadow-xl hover:-translate-y-1 transition-all">
            <span class="w-11 h-11 rounded-xl bg-violet-50 text-violet-600 grid place-items-center"><span class="material-symbols-outlined text-[22px]">trending_up</span></span>
            <h3 class="font-extrabold text-[17px] mt-4">Investimentos sem mistério</h3>
            <p class="text-[13.5px] text-slate-500 mt-2 leading-relaxed">Aportes, rendimentos e rentabilidade por classe — e o radar do mercado embutido.</p>
        </div>
        <div class="reveal rounded-3xl bg-gradient-to-br from-emerald-600 to-emerald-800 text-white p-8">
            <span class="w-11 h-11 rounded-xl bg-white/15 grid place-items-center"><span class="material-symbols-outlined text-[22px]">key</span></span>
            <h3 class="font-extrabold text-[17px] mt-4">Proteção contra golpes</h3>
            <p class="text-[13.5px] text-emerald-50/90 mt-2 leading-relaxed">Palavra-chave da família: ninguém se passa por ninguém para pedir dinheiro.</p>
        </div>
    </div>
</section>

{{-- ═══ COMO FUNCIONA ═══ --}}
<section id="como-funciona" class="bg-white border-y border-slate-200">
    <div class="max-w-6xl mx-auto px-5 py-20">
        <div class="reveal text-center max-w-xl mx-auto">
            <p class="text-[12px] font-extrabold uppercase tracking-[0.18em] text-emerald-600">Como funciona</p>
            <h2 class="mt-2 text-[28px] md:text-[34px] font-black tracking-tight">Em 3 passos, hoje mesmo</h2>
        </div>
        <div class="mt-10 grid md:grid-cols-3 gap-5">
            @php
                $steps = [
                    ['n' => '01', 'icon' => 'person_add', 'title' => 'Crie a conta da família', 'text' => 'Leva 1 minuto. Convide quem mora com você — cada um com seu acesso.'],
                    ['n' => '02', 'icon' => 'add_circle', 'title' => 'Cadastre contas e cartões', 'text' => 'Informe saldos, limites e vencimentos. O Prumo calcula o resto sozinho.'],
                    ['n' => '03', 'icon' => 'insights', 'title' => 'Lance e acompanhe', 'text' => 'Pelo site ou pelo Telegram. Gráficos mostram para onde o dinheiro vai.'],
                ];
            @endphp
            @foreach($steps as $s)
            <div class="reveal relative rounded-3xl border border-slate-200 bg-slate-50/60 p-7">
                <span class="text-[44px] font-black text-slate-200 select-none">{{ $s['n'] }}</span>
                <span class="absolute top-7 right-7 w-10 h-10 rounded-xl bg-emerald-600 text-white grid place-items-center shadow-lg shadow-emerald-600/25"><span class="material-symbols-outlined text-[19px]">{{ $s['icon'] }}</span></span>
                <h3 class="font-extrabold text-[16px] mt-2">{{ $s['title'] }}</h3>
                <p class="text-[13.5px] text-slate-500 mt-1.5 leading-relaxed">{{ $s['text'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ PLANOS ═══ --}}
<section id="planos" class="max-w-6xl mx-auto px-5 py-20">
    <div class="reveal text-center max-w-xl mx-auto">
        <p class="text-[12px] font-extrabold uppercase tracking-[0.18em] text-emerald-600">Planos</p>
        <h2 class="mt-2 text-[28px] md:text-[34px] font-black tracking-tight">Menos que um lanche por mês</h2>
        <p class="text-[14px] text-slate-500 mt-2">14 dias grátis, sem cartão. Cancele quando quiser.</p>
    </div>
    <div class="mt-10 grid sm:grid-cols-2 max-w-3xl mx-auto gap-5 items-stretch">
        @foreach($plans as $chave => $plano)
        <div class="reveal rounded-3xl border p-8 relative flex flex-col {{ ($plano['destaque'] ?? false) ? 'border-emerald-500 ring-8 ring-emerald-500/10 bg-slate-900 text-white shadow-2xl' : 'border-slate-200 bg-white' }}">
            @if($plano['destaque'] ?? false)
            <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 text-[11px] font-extrabold uppercase tracking-wider bg-emerald-500 text-white px-4 py-1.5 rounded-full whitespace-nowrap">2 meses de graça</span>
            @endif
            <p class="text-[13px] font-extrabold uppercase tracking-widest {{ ($plano['destaque'] ?? false) ? 'text-emerald-300' : 'text-slate-400' }}">{{ $plano['label'] }}</p>
            <p class="mt-2 flex items-end gap-1.5">
                <span class="text-[42px] leading-none font-black num">R$ {{ number_format($plano['price'], 2, ',', '.') }}</span>
                <span class="{{ ($plano['destaque'] ?? false) ? 'text-slate-300' : 'text-slate-400' }} text-[13px] font-bold mb-1.5">/{{ $chave === 'anual' ? 'ano' : 'mês' }}</span>
            </p>
            <p class="mt-1 text-[12px] font-medium {{ ($plano['destaque'] ?? false) ? 'text-slate-300' : 'text-slate-400' }}">
                @if($chave === 'anual')
                    Equivale a R$ {{ number_format($plano['price'] / 12, 2, ',', '.') }}/mês · cobrado 1x ao ano
                @else
                    Cobrança mensal · cancele quando quiser
                @endif
            </p>
            <ul class="mt-6 space-y-2.5 text-[13.5px] {{ ($plano['destaque'] ?? false) ? 'text-slate-200' : 'text-slate-600' }}">
                <li class="flex items-center gap-2"><span class="text-emerald-500 material-symbols-outlined text-[18px]">check_circle</span> Membros ilimitados</li>
                <li class="flex items-center gap-2"><span class="text-emerald-500 material-symbols-outlined text-[18px]">check_circle</span> Lançamentos ilimitados</li>
                <li class="flex items-center gap-2"><span class="text-emerald-500 material-symbols-outlined text-[18px]">check_circle</span> Bot no Telegram + OCR</li>
                <li class="flex items-center gap-2"><span class="text-emerald-500 material-symbols-outlined text-[18px]">check_circle</span> Cartões, investimentos e mercado</li>
            </ul>
            <a href="{{ route('cadastro') }}" class="mt-7 w-full h-12 inline-flex items-center justify-center gap-2 rounded-2xl font-extrabold text-[14px] transition {{ ($plano['destaque'] ?? false) ? 'bg-emerald-500 text-white hover:bg-emerald-400' : 'bg-slate-900 text-white hover:bg-slate-700' }}">
                Começar grátis <span class="material-symbols-outlined text-[17px]">arrow_forward</span>
            </a>
        </div>
        @endforeach
    </div>
    <p class="reveal text-center text-[12px] text-slate-400 font-medium mt-6">Pagamento por PIX ou cartão via Mercado Pago · Nota e suporte inclusos</p>
</section>

{{-- ═══ FAQ ═══ --}}
<section id="faq" class="bg-white border-y border-slate-200">
    <div class="max-w-3xl mx-auto px-5 py-20">
        <div class="reveal text-center">
            <p class="text-[12px] font-extrabold uppercase tracking-[0.18em] text-emerald-600">Dúvidas frequentes</p>
            <h2 class="mt-2 text-[28px] md:text-[32px] font-black tracking-tight">Perguntas e respostas</h2>
        </div>
        <div class="mt-8 space-y-3">
            @php
                $faqs = [
                    ['q' => 'Preciso conectar minha conta do banco?', 'a' => 'Não. Você informa saldos, limites e vencimentos uma vez — e registra os lançamentos. Nada se conecta ao seu banco, seu dinheiro continua só com você.'],
                    ['q' => 'Como funciona o bot no Telegram?', 'a' => 'Você vincula com um código gerado no seu perfil e passa a lançar pelo chat (“Mercado 120,50 hoje”) ou enviando a foto do comprovante, que é lida automaticamente.'],
                    ['q' => 'Meus dados estão seguros?', 'a' => 'Sim. Senhas com criptografia, acesso por perfil (admin, membro), hospedagem com HTTPS e tratamento de dados conforme a LGPD — com exportação e exclusão garantidas.'],
                    ['q' => 'Posso cancelar quando quiser?', 'a' => 'Pode. Não há fidelidade nem multa: você cancela a assinatura e mantém o acesso até o fim do período já pago.'],
                    ['q' => 'Serve para quem mora sozinho?', 'a' => 'Serve. Apesar do foco em família, quem mora sozinho usa do mesmo jeito — e quando formar família, é só convidar.'],
                ];
            @endphp
            @foreach($faqs as $f)
            <details class="faq reveal group rounded-2xl border border-slate-200 bg-slate-50/60 open:bg-white open:shadow-sm transition">
                <summary class="flex items-center justify-between gap-4 cursor-pointer list-none p-5 text-[14px] font-extrabold">
                    {{ $f['q'] }}
                    <span class="faq-plus w-8 h-8 shrink-0 rounded-full bg-emerald-50 text-emerald-700 grid place-items-center"><span class="material-symbols-outlined text-[18px]">add</span></span>
                </summary>
                <p class="px-5 pb-5 text-[13.5px] text-slate-500 leading-relaxed">{{ $f['a'] }}</p>
            </details>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ CTA FINAL ═══ --}}
<section class="max-w-6xl mx-auto px-5 py-16">
    <div class="reveal relative overflow-hidden rounded-[28px] bg-slate-900 p-10 md:p-16 text-center text-white">
        <div class="absolute -top-28 left-1/2 -translate-x-1/2 w-[560px] h-[280px] rounded-full bg-emerald-500/25 blur-3xl"></div>
        <div class="absolute inset-0 hero-grid-bg opacity-40"></div>
        <h2 class="relative text-[26px] md:text-[36px] font-black tracking-tight max-w-2xl mx-auto leading-tight">Feche o mês no azul — começando hoje, de graça</h2>
        <p class="relative text-[14px] text-slate-300 mt-3 max-w-lg mx-auto">Junte a família, organize as contas e veja o dinheiro render. Leva menos de 2 minutos.</p>
        <a href="{{ route('cadastro') }}" class="relative mt-8 inline-flex items-center gap-2 px-9 py-4 text-[15px] font-extrabold text-slate-900 bg-white hover:bg-emerald-50 rounded-2xl transition shadow-xl">
            Criar minha conta grátis <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </a>
        <p class="relative mt-4 text-[12px] text-slate-400 font-medium">Sem cartão de crédito · Suporte em português</p>
    </div>
</section>

{{-- ═══ FOOTER ═══ --}}
<footer class="border-t border-slate-200 bg-white">
    <div class="max-w-6xl mx-auto px-5 py-10 flex flex-col md:flex-row items-center justify-between gap-5">
        <div class="flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-lg bg-emerald-700 text-white grid place-items-center">
                <span class="material-symbols-outlined text-[18px]">savings</span>
            </span>
            <div>
                <p class="font-extrabold leading-none">Prumo</p>
                <p class="text-[11px] text-slate-400 font-medium mt-0.5">© {{ date('Y') }} · Gestão financeira para você e sua família</p>
            </div>
        </div>
        <div class="flex items-center gap-5 text-[13px] font-bold text-slate-500">
            <a href="{{ route('termos') }}" class="hover:text-emerald-700 transition">Termos de Uso</a>
            <a href="{{ route('privacidade') }}" class="hover:text-emerald-700 transition">Política de Privacidade (LGPD)</a>
            <a href="{{ route('login') }}" class="hover:text-emerald-700 transition">Entrar</a>
        </div>
    </div>
</footer>

<script>
(function () {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('on'); });
        return;
    }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) { e.target.classList.add('on'); io.unobserve(e.target); }
        });
    }, { threshold: 0.12 });
    document.querySelectorAll('.reveal:not(.on)').forEach(function (el) { io.observe(el); });
})();
</script>

</body>
</html>
