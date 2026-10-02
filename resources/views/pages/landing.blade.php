<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prumo — Gestão financeira para você e sua família</title>
    <meta name="description" content="Prumo: contas, cartões, investimentos e orçamento da família em um só lugar. Simples, seguro e no seu bolso.">
    <meta name="theme-color" content="#064E3B">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Montserrat', system-ui, sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased">

{{-- Navbar --}}
<header class="sticky top-0 z-40 backdrop-blur-md bg-slate-50/80 border-b border-slate-200">
    <div class="max-w-6xl mx-auto px-5 h-16 flex items-center justify-between">
        <a href="/" class="flex items-center gap-2.5">
            <span class="w-9 h-9 rounded-xl bg-emerald-700 text-white grid place-items-center">
                <span class="material-symbols-outlined text-[20px]">savings</span>
            </span>
            <span class="text-[18px] font-extrabold tracking-tight">Prumo</span>
        </a>
        <nav class="hidden md:flex items-center gap-7 text-[14px] font-bold text-slate-600">
            <a href="#recursos" class="hover:text-emerald-700 transition">Recursos</a>
            <a href="#planos" class="hover:text-emerald-700 transition">Planos</a>
            <a href="{{ route('termos') }}" class="hover:text-emerald-700 transition">Termos</a>
        </nav>
        <div class="flex items-center gap-2">
            <a href="{{ route('login') }}" class="h-10 px-4 inline-flex items-center text-[13px] font-extrabold text-slate-700 hover:text-slate-900 transition">Entrar</a>
            <a href="{{ route('cadastro') }}" class="h-10 px-5 inline-flex items-center text-[13px] font-extrabold text-white bg-emerald-700 hover:bg-emerald-800 rounded-xl transition shadow-sm">Criar conta grátis</a>
        </div>
    </div>
</header>

{{-- Hero --}}
<section class="relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-emerald-50 via-slate-50 to-slate-50 pointer-events-none"></div>
    <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-emerald-200/40 blur-3xl pointer-events-none"></div>
    <div class="relative max-w-6xl mx-auto px-5 pt-20 pb-24 text-center">
        <span class="inline-flex items-center gap-1.5 text-[12px] font-extrabold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-3.5 py-1.5">
            <span class="material-symbols-outlined text-[14px]">verified_user</span>
            Seguro · LGPD · Sem conta em banco
        </span>
        <h1 class="mt-6 text-[40px] md:text-[56px] leading-[1.05] font-extrabold tracking-tight text-slate-900 max-w-3xl mx-auto">
            Suas finanças em um só lugar, <span class="text-emerald-700">do seu jeito.</span>
        </h1>
        <p class="mt-5 text-[16px] md:text-[18px] text-slate-500 max-w-xl mx-auto leading-relaxed">
            Gestão financeira para você e sua família: contas, cartões, investimentos e orçamento simples, no celular e no Telegram.
        </p>
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('cadastro') }}" class="h-13 px-7 py-3.5 inline-flex items-center gap-2 text-[15px] font-extrabold text-white bg-emerald-700 hover:bg-emerald-800 rounded-2xl shadow-lg shadow-emerald-700/20 transition">
                Começar grátis
                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            </a>
            <a href="#recursos" class="h-13 px-7 py-3.5 inline-flex items-center gap-2 text-[15px] font-extrabold text-slate-700 bg-white border border-slate-200 hover:border-slate-300 rounded-2xl transition">
                Ver recursos
            </a>
        </div>
        <p class="mt-4 text-[12px] text-slate-400 font-medium">14 dias grátis · sem cartão de crédito</p>
    </div>
</section>

{{-- Recursos --}}
<section id="recursos" class="max-w-6xl mx-auto px-5 py-16">
    <div class="text-center mb-10">
        <h2 class="text-[28px] md:text-[34px] font-extrabold tracking-tight">Tudo que a sua família precisa</h2>
        <p class="text-[14px] text-slate-500 mt-2">De receitas a investimentos, organizado por pessoa.</p>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @php
            $features = [
                ['icon'=>'account_balance_wallet','title'=>'Contas e cartões','text'=>'Saldos em tempo real, faturas e extratos por conta, tudo com as cores do seu banco.'],
                ['icon'=>'pie_chart','title'=>'Gráficos inteligentes','text'=>'Receitas × despesas, gastos por categoria e investimentos visualizados de forma clara.'],
                ['icon'=>'robot','title'=>'Bot no Telegram','text'=>'Lance compras e envie comprovantes direto do celular, com OCR automático.'],
                ['icon'=>'savings','title'=>'Investimentos','text'=>'Aportes, rendimentos, classes e mercado acompanhados em um só lugar.'],
                ['icon'=>'groups','title'=>'Para toda a família','text'=>'Cada membro lança suas próprias despesas e receitas, com papéis e permissões.'],
                ['icon'=>'verified_user','title'=>'Segurança e LGPD','text'=>'Senhas protegidas, palavra-chave de identidade e seus dados sob a LGPD.'],
            ];
        @endphp
        @foreach($features as $f)
        <div class="bg-white rounded-2xl border border-slate-200 p-6 hover:shadow-[var(--shadow-lift)] hover:-translate-y-0.5 transition-all duration-200">
            <span class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 grid place-items-center">
                <span class="material-symbols-outlined text-[22px]">{{ $f['icon'] }}</span>
            </span>
            <h3 class="font-extrabold text-[15px] mt-4">{{ $f['title'] }}</h3>
            <p class="text-[13px] text-slate-500 mt-1.5 leading-relaxed">{{ $f['text'] }}</p>
        </div>
        @endforeach
    </div>
</section>

{{-- Planos --}}
<section id="planos" class="max-w-6xl mx-auto px-5 py-16">
    <div class="text-center mb-10">
        <h2 class="text-[28px] md:text-[34px] font-extrabold tracking-tight">Escolha o seu plano</h2>
        <p class="text-[14px] text-slate-500 mt-2">Comece grátis por 14 dias. Depois, R$ 1 no primeiro mês.</p>
    </div>
    <div class="grid sm:grid-cols-2 max-w-3xl mx-auto gap-5">
        @foreach($plans as $chave => $plano)
        <div class="bg-white rounded-2xl border p-7 relative {{ $plano['destaque'] ? 'border-emerald-300 ring-4 ring-emerald-100' : 'border-slate-200' }}">
            @if($plano['destaque'])
            <span class="absolute -top-3 left-6 text-[11px] font-extrabold uppercase tracking-wider bg-emerald-600 text-white px-3 py-1 rounded-full">Mais econômico</span>
            @endif
            <p class="text-[13px] font-extrabold uppercase tracking-widest text-slate-400">{{ $plano['label'] }}</p>
            <p class="text-[38px] font-extrabold text-slate-900 mt-2">R$ {{ number_format($plano['price'], 2, ',', '.') }}</p>
            <p class="text-[12px] text-slate-400 mt-0.5">{{ $chave === 'anual' ? 'Cobrado uma vez por ano' : 'Cobrado todo mês' }}</p>
            <ul class="mt-5 space-y-2 text-[13px] text-slate-600">
                <li class="flex items-center gap-2"><span class="text-emerald-600 material-symbols-outlined text-[16px]">check_circle</span> Família ilimitada</li>
                <li class="flex items-center gap-2"><span class="text-emerald-600 material-symbols-outlined text-[16px]">check_circle</span> Lançamentos ilimitados</li>
                <li class="flex items-center gap-2"><span class="text-emerald-600 material-symbols-outlined text-[16px]">check_circle</span> Bot no Telegram</li>
            </ul>
            <a href="{{ route('cadastro') }}" class="mt-6 w-full h-12 inline-flex items-center justify-center rounded-xl font-extrabold text-[14px] transition {{ $plano['destaque'] ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-slate-900 text-white hover:bg-slate-800' }}">
                Começar grátis
            </a>
        </div>
        @endforeach
    </div>
</section>

{{-- CTA final --}}
<section class="max-w-6xl mx-auto px-5 py-14">
    <div class="bg-slate-900 rounded-3xl p-10 md:p-14 text-center text-white relative overflow-hidden">
        <div class="absolute -top-20 -right-20 w-72 h-72 rounded-full bg-emerald-500/20 blur-3xl"></div>
        <h2 class="text-[26px] md:text-[32px] font-extrabold tracking-tight relative">Comece a organizar suas finanças hoje</h2>
        <p class="text-[14px] text-slate-300 mt-2 relative max-w-lg mx-auto">14 dias grátis. Sem cartão. Cancele quando quiser.</p>
        <a href="{{ route('cadastro') }}" class="mt-7 inline-flex items-center gap-2 h-13 px-8 py-3.5 text-[15px] font-extrabold text-slate-900 bg-white hover:bg-emerald-50 rounded-2xl transition relative">
            Criar minha conta grátis
            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </a>
    </div>
</section>

{{-- Footer --}}
<footer class="border-t border-slate-200 bg-white">
    <div class="max-w-6xl mx-auto px-5 py-10 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-emerald-700 text-white grid place-items-center">
                <span class="material-symbols-outlined text-[18px]">savings</span>
            </span>
            <span class="font-extrabold">Prumo</span>
            <span class="text-[12px] text-slate-400 font-medium">© {{ date('Y') }} · Gestão financeira para você e sua família</span>
        </div>
        <div class="flex items-center gap-5 text-[13px] font-bold text-slate-500">
            <a href="{{ route('termos') }}" class="hover:text-emerald-700 transition">Termos de Uso</a>
            <a href="{{ route('privacidade') }}" class="hover:text-emerald-700 transition">Política de Privacidade (LGPD)</a>
        </div>
    </div>
</footer>

</body>
</html>