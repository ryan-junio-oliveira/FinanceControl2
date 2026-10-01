@extends('layouts.legal')
@section('title', $title)

@section('content')
<div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
    <div class="relative px-6 sm:px-8 py-7 text-white"
        style="background: linear-gradient(135deg, #042f1e 0%, #064E3B 50%, #059669 100%);">
        <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full opacity-20 blur-3xl"
            style="background: radial-gradient(circle, #34D399, transparent)"></div>
        <span class="relative w-11 h-11 rounded-xl bg-white/10 grid place-items-center">
            <span class="material-symbols-outlined text-[22px]">gavel</span>
        </span>
        <h1 class="relative text-[22px] font-extrabold tracking-tight mt-3">{{ $title }}</h1>
        <p class="relative text-[12px] text-emerald-100/80 mt-0.5">Última atualização: {{ $updatedAt }}</p>
    </div>

    <div class="px-6 sm:px-8 py-6 space-y-6">
        <p class="text-[14px] leading-relaxed text-gray-600">{{ $intro }}</p>

        @foreach($sections as $sec)
        <section>
            <h2 class="text-[15px] font-extrabold text-gray-900">{{ $sec['heading'] }}</h2>
            <p class="text-[13.5px] leading-relaxed text-gray-600 mt-1.5">{{ $sec['body'] }}</p>
        </section>
        @endforeach

        <div class="pt-4 border-t border-slate-100 flex flex-wrap gap-3">
            <a href="{{ route('login') }}"
                class="h-10 px-5 rounded-lg bg-gradient-to-br from-emerald-600 to-emerald-700 text-white text-[13px] font-bold inline-flex items-center gap-2 shadow-sm hover:from-emerald-700 hover:to-emerald-800 transition">
                <span class="material-symbols-outlined text-[17px]">login</span>
                Fazer login
            </a>
            <a href="{{ route('cadastro') }}"
                class="h-10 px-5 rounded-lg border border-slate-200 bg-white text-slate-600 text-[13px] font-bold inline-flex items-center gap-2 hover:bg-slate-50 hover:text-slate-900 transition">
                <span class="material-symbols-outlined text-[17px]">person_add</span>
                Criar conta
            </a>
        </div>
    </div>
</div>
@endsection