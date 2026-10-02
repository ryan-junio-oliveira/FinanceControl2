@extends('layouts.app')
@section('title','Plano')
@section('breadcrumb','Conta / Plano')

@section('content')
@php use App\Support\Fin; @endphp

<div class="max-w-4xl mx-auto w-full">
    <x-form.header title="Assinatura FinFamília" subtitle="Escolha um plano para manter sua família no controle financeiro."
        :backUrl="route('dashboard')" backLabel="Voltar ao dashboard" icon="workspace_premium"
        iconBg="linear-gradient(135deg,#FEF3C7,#FDE68A)" iconColor="#B45309" />

    {{-- Status atual --}}
    @php
        $s = $status;
        $trialDias = $s['trial_ends_at'] ? max(0, (int) \Carbon\Carbon::parse($s['trial_ends_at'])->diffInDays(now(), false)) : 0;
    @endphp
    <div class="mb-6">
        @if($s['pro'])
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 flex items-center gap-4">
            <span class="w-11 h-11 rounded-xl bg-emerald-500 grid place-items-center text-white shrink-0"><span class="material-symbols-outlined">verified</span></span>
            <div>
                <p class="font-extrabold text-gray-900">Plano Pro ativo 🎉</p>
                <p class="text-[13px] text-gray-500 mt-0.5">Sua assinatura está ativa. Aproveite todos os recursos.</p>
            </div>
        </div>
        @elseif($s['trial_ativo'])
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 flex items-center gap-4">
            <span class="w-11 h-11 rounded-xl bg-amber-500 grid place-items-center text-white shrink-0"><span class="material-symbols-outlined">hourglass_top</span></span>
            <div>
                <p class="font-extrabold text-gray-900">Trial grátis em andamento</p>
                <p class="text-[13px] text-gray-500 mt-0.5">Termina em <b class="text-gray-800">{{ $s['trial_ends_at'] }}</b>. Depois disso, escolha um plano para continuar.</p>
            </div>
        </div>
        @else
        <div class="rounded-2xl border border-red-200 bg-red-50 p-5 flex items-center gap-4">
            <span class="w-11 h-11 rounded-xl bg-red-500 grid place-items-center text-white shrink-0"><span class="material-symbols-outlined">lock</span></span>
            <div>
                <p class="font-extrabold text-gray-900">Seu plano expirou</p>
                <p class="text-[13px] text-gray-500 mt-0.5">Assine um plano abaixo para reativar o acesso.</p>
            </div>
        </div>
        @endif
    </div>

    @if(!$status['enabled'])
    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 mb-6 text-[13px] text-gray-500">
        <b class="text-gray-700">Integração de pagamento em preparação.</b> Configure <code class="text-[12px] bg-white border border-slate-200 rounded px-1.5 py-0.5">BILLING_ENABLED</code> e as credenciais do Mercado Pago no <code class="text-[12px] bg-white border border-slate-200 rounded px-1.5 py-0.5">.env</code> para liberar as assinaturas.
    </div>
    @endif

    {{-- Cards de planos --}}
    <div class="grid sm:grid-cols-2 gap-4">
        @foreach($status['plans'] as $chave => $plano)
        <div class="rounded-2xl border bg-white p-6 relative {{ $plano['destaque'] ? 'border-amber-300 ring-4 ring-amber-100' : 'border-slate-200' }}">
            @if($plano['destaque'])
            <span class="absolute -top-3 left-6 text-[11px] font-extrabold uppercase tracking-wider bg-amber-500 text-white px-3 py-1 rounded-full">Mais econômico</span>
            @endif
            <p class="text-[13px] font-extrabold uppercase tracking-widest text-gray-400">{{ $plano['label'] }}</p>
            <p class="num text-[34px] font-extrabold text-gray-900 mt-2">{{ Fin::money($plano['price']) }}</p>
            <p class="text-[12px] text-gray-400 mt-1">{{ $chave === 'anual' ? 'Cobrado uma vez por ano' : 'Cobrado todo mês' }}</p>
            <ul class="mt-5 space-y-2 text-[13px] text-gray-600">
                <li class="flex items-center gap-2"><span class="text-emerald-500 material-symbols-outlined text-[16px]">check_circle</span> Família ilimitada</li>
                <li class="flex items-center gap-2"><span class="text-emerald-500 material-symbols-outlined text-[16px]">check_circle</span> Lançamentos ilimitados</li>
                <li class="flex items-center gap-2"><span class="text-emerald-500 material-symbols-outlined text-[16px]">check_circle</span> Bot no Telegram + comprovantes</li>
                <li class="flex items-center gap-2"><span class="text-emerald-500 material-symbols-outlined text-[16px]">check_circle</span> Cartões, investimentos e mercado</li>
            </ul>
            <form method="POST" action="{{ route('plans.checkout') }}" class="mt-6">
                @csrf
                <input type="hidden" name="plano" value="{{ $chave }}">
                <button type="submit" {{ $status['enabled'] ? '' : 'disabled' }}
                    class="w-full h-12 rounded-xl font-extrabold text-[14px] transition {{ $plano['destaque'] ? 'bg-amber-500 text-white hover:bg-amber-600' : 'bg-slate-900 text-white hover:bg-slate-800' }} {{ $status['enabled'] ? '' : 'opacity-40 cursor-not-allowed' }}">
                    Assinar {{ $plano['label'] }}
                </button>
            </form>
        </div>
        @endforeach
    </div>

    @if($s['preapproval'])
    <div class="mt-6 text-center">
        <form method="POST" action="{{ route('plans.cancel') }}" onsubmit="return confirm('Cancelar a assinatura? Você perderá o acesso Pro ao fim do período pago.')">
            @csrf
            <button type="submit" class="text-[13px] font-bold text-red-500 hover:text-red-600 hover:underline transition">Cancelar assinatura</button>
        </form>
    </div>
    @endif

    <p class="text-[12px] text-gray-400 text-center mt-8">Pagamento processado pelo Mercado Pago (PIX ou cartão). Cancele quando quiser.</p>
</div>
@endsection