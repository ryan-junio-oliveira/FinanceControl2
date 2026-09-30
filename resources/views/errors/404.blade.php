<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Página não encontrada · FinFamília</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F8FAFC] text-gray-900" style="font-family:'Montserrat',sans-serif">
<div class="min-h-screen grid place-items-center px-5">
    <div class="max-w-md w-full text-center">
        <span class="inline-grid place-items-center w-16 h-16 rounded-lg text-white mx-auto" style="background:linear-gradient(135deg,#059669,#064E3B)">
            <span class="material-symbols-outlined text-[32px]">savings</span>
        </span>
        <p class="num text-[64px] font-extrabold text-gray-200 mt-4">404</p>
        <h1 class="text-[20px] font-extrabold">Página não encontrada</h1>
        <p class="text-[13px] text-gray-500 mt-1">O endereço acessado não existe ou o convite expirou.</p>
        <div class="mt-6 flex justify-center gap-2">
            @auth
            <x-btn-link :href="route('dashboard')" size="sm" icon="home">Ir para o Dashboard</x-btn-link>
            @else
            <x-btn-link :href="route('login')" size="sm" icon="login">Ir para o login</x-btn-link>
            @endauth
        </div>
    </div>
</div>
</body>
</html>
