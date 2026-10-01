<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'FinFamília') · FinFamília</title>
    <meta name="description" content="FinFamília — termos de uso e política de privacidade (LGPD).">
    <meta name="theme-color" content="#064E3B">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="FinFamília">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-[#F8FAFC] text-gray-900 flex flex-col min-h-screen">
    {{-- Topo --}}
    <header class="sticky top-0 z-20 glass border-b border-slate-200/70">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between gap-3">
            <a href="{{ route('login') }}" class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-lg grid place-items-center text-white shrink-0"
                    style="background: linear-gradient(135deg, #059669, #064E3B);">
                    <span class="material-symbols-outlined text-[18px]">savings</span>
                </span>
                <span class="text-[17px] font-extrabold tracking-tight">Fin<span class="text-emerald-600">Família</span></span>
            </a>
            <a href="{{ route('login') }}" class="text-[12px] font-bold text-gray-500 hover:text-emerald-600 transition">Voltar ao acesso</a>
        </div>
    </header>

    {{-- Conteúdo --}}
    <main class="flex-1 w-full max-w-3xl mx-auto px-4 py-8">
        @yield('content')
    </main>

    {{-- Rodapé --}}
    <footer class="shrink-0 border-t border-slate-200 bg-white/70 backdrop-blur">
        <div class="max-w-3xl mx-auto px-4 py-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px]">
            <p class="text-gray-400 font-medium">© 2026 <b class="text-gray-500">FinFamília</b> · Gestão financeira familiar</p>
            <nav class="flex items-center gap-4 font-semibold">
                <a href="{{ route('termos') }}" class="text-gray-400 hover:text-emerald-600 transition">Termos de Uso</a>
                <span class="text-slate-200">•</span>
                <a href="{{ route('privacidade') }}" class="text-gray-400 hover:text-emerald-600 transition">Política de Privacidade (LGPD)</a>
            </nav>
        </div>
    </footer>
</body>
</html>