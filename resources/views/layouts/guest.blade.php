<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Entrar') · Prumo</title>
    <meta name="description" content="Prumo — gestão financeira para você e sua família.">
    <meta name="theme-color" content="#064E3B">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Prumo">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="text-gray-900">
<div class="min-h-screen flex flex-col bg-slate-100">
    <div class="flex-1 flex min-h-0">
        {{-- Banner lateral (desktop) --}}
        <aside class="hidden lg:flex lg:basis-[45%] flex-col justify-between gap-8 bg-[#064E3B] text-white p-8 relative overflow-hidden">
            <canvas id="auth-particles" class="absolute inset-0 h-full w-full opacity-70 pointer-events-none"></canvas>
            <a href="{{ route('login') }}" class="relative z-10 flex items-center gap-2.5" data-hero>
                <span class="w-8 h-8 rounded-lg bg-white/10 grid place-items-center shrink-0">
                    <span class="material-symbols-outlined text-[19px]">savings</span>
                </span>
                <span class="text-base font-extrabold tracking-tight">Prumo</span>
            </a>

            <div data-hero>
                <h2 class="text-[26px] leading-tight font-bold tracking-tight max-w-md">Gestão financeira para você e sua família.</h2>
                <p class="text-[13px] text-emerald-100/75 mt-2 leading-relaxed max-w-sm">Contas, cartões, investimentos e orçamento em um só lugar.</p>
                <ul class="mt-5 pt-5 border-t border-white/15 grid gap-3">
                    <li class="flex items-center gap-2.5 text-[13px] font-semibold"><span class="material-symbols-outlined text-[18px] text-emerald-300">check_circle</span> Lançamentos por pessoa</li>
                    <li class="flex items-center gap-2.5 text-[13px] font-semibold"><span class="material-symbols-outlined text-[18px] text-emerald-300">check_circle</span> Gastos por categoria</li>
                    <li class="flex items-center gap-2.5 text-[13px] font-semibold"><span class="material-symbols-outlined text-[18px] text-emerald-300">check_circle</span> Gastos mensais organizados</li>
                </ul>
            </div>

            <p class="relative z-10 text-[11px] text-emerald-100/55" data-hero>Senhas protegidas por hash · Seus dados, sua conta</p>
        </aside>

        {{-- Formulário --}}
        <main class="flex-1 grid place-items-center content-center px-4 py-10 min-w-0">
            <div class="lg:hidden flex items-center gap-2.5 mb-6">
                <span class="w-8 h-8 rounded-lg bg-emerald-700 text-white grid place-items-center shrink-0">
                    <span class="material-symbols-outlined text-[19px]">savings</span>
                </span>
                <span class="text-base font-extrabold tracking-tight">Prumo</span>
            </div>
            <div class="w-full max-w-lg bg-white border border-slate-200 border-t-[3px] border-t-emerald-600 rounded-lg shadow-sm p-7" data-auth-card>
                @yield('content')
            </div>
            <div class="w-full max-w-lg mt-4">
                <footer class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-[11px]">
                    <p class="text-gray-400 font-medium">© {{ date('Y') }} <b class="text-gray-500">Prumo</b></p>
                    <a href="{{ route('termos') }}" class="text-gray-400 hover:text-emerald-700 transition font-semibold">Termos de Uso</a>
                    <a href="{{ route('privacidade') }}" class="text-gray-400 hover:text-emerald-700 transition font-semibold">Política de Privacidade (LGPD)</a>
                </footer>
            </div>
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
