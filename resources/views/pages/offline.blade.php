@extends('layouts.guest')
@section('title', 'Offline')

@section('content')
<div class="text-center py-6">
    <span class="w-14 h-14 rounded-2xl bg-amber-50 grid place-items-center mx-auto">
        <span class="material-symbols-outlined text-[28px] text-amber-500">wifi_off</span>
    </span>
    <h1 class="text-xl font-extrabold tracking-tight text-gray-900 mt-4">Você está offline</h1>
    <p class="text-[13px] text-gray-500 mt-2 leading-relaxed">
        Sem conexão com a internet. Verifique sua rede e tente de novo —
        nada foi perdido, é só recarregar.
    </p>
    <button onclick="window.location.reload()"
        class="mt-5 h-11 px-6 rounded-xl bg-gradient-to-br from-emerald-600 to-emerald-700 text-white text-[13px] font-bold shadow-sm hover:from-emerald-700 hover:to-emerald-800 transition">
        Tentar de novo
    </button>
</div>
@endsection
