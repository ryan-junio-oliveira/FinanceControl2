@props(['type' => 'despesa'])

@php
$isReceita = $type === 'receita';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-extrabold whitespace-nowrap ' . ($isReceita ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-600')]) }}>
    <span class="material-symbols-outlined text-[15px]" style="font-variation-settings:'FILL' 1,'wght' 600,'GRAD' 0,'opsz' 20">{{ $isReceita ? 'north' : 'south' }}</span>
    {{ $isReceita ? 'Receita' : 'Despesa' }}
</span>
