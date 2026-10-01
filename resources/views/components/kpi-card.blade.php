@props(['label','value','icon'=>'wallet','accent'=>'emerald','top'=>null,'bottom'=>null,'raw'=>null,'prefix'=>'','suffix'=>''])
@php
$accents = [
  'emerald' => ['icon_bg'=>'bg-emerald-50','icon_text'=>'text-emerald-600','value'=>'text-gray-900','bar'=>'bg-emerald-500'],
  'green'   => ['icon_bg'=>'bg-emerald-50','icon_text'=>'text-emerald-600','value'=>'text-emerald-700','bar'=>'bg-emerald-500'],
  'slate'   => ['icon_bg'=>'bg-slate-100','icon_text'=>'text-gray-500','value'=>'text-gray-900','bar'=>'bg-slate-400'],
  'blue'    => ['icon_bg'=>'bg-blue-50','icon_text'=>'text-blue-600','value'=>'text-blue-700','bar'=>'bg-blue-500'],
  'amber'   => ['icon_bg'=>'bg-amber-50','icon_text'=>'text-amber-600','value'=>'text-gray-900','bar'=>'bg-amber-500'],
  'red'     => ['icon_bg'=>'bg-red-50','icon_text'=>'text-red-500','value'=>'text-gray-900','bar'=>'bg-red-500'],
  'cyan'    => ['icon_bg'=>'bg-cyan-50','icon_text'=>'text-cyan-600','value'=>'text-gray-900','bar'=>'bg-cyan-500'],
  'orange'  => ['icon_bg'=>'bg-orange-50','icon_text'=>'text-orange-500','value'=>'text-gray-900','bar'=>'bg-orange-500'],
  'violet'  => ['icon_bg'=>'bg-violet-50','icon_text'=>'text-violet-600','value'=>'text-gray-900','bar'=>'bg-violet-500'],
  'rose'    => ['icon_bg'=>'bg-rose-50','icon_text'=>'text-rose-500','value'=>'text-gray-900','bar'=>'bg-rose-500'],
];
$a = $accents[$accent] ?? $accents['emerald'];
@endphp
<div class="kpi-card accent-{{ $accent }}" data-reveal>
    {{-- Decoração de fundo sutil --}}
    <div class="absolute -right-4 -top-4 w-20 h-20 rounded-lg opacity-[0.06] {{ $a['bar'] }}"></div>

    <div class="flex items-start justify-between gap-3 relative">
        <div class="min-w-0 flex-1">
            <p class="text-[11px] font-extrabold uppercase tracking-widest text-gray-400">{{ $label }}</p>
            <p class="num text-[26px] leading-8 font-extrabold tracking-tight mt-1.5 {{ $a['value'] }}"@if($raw !== null) data-count="{{ $raw }}" data-prefix="{{ $prefix }}" data-suffix="{{ $suffix }}"@endif>{{ $value }}</p>
            @if($top)<div class="mt-1">{!! $top !!}</div>@endif
        </div>
        <div class="w-11 h-11 rounded-lg grid place-items-center shrink-0 {{ $a['icon_bg'] }} {{ $a['icon_text'] }} shadow-sm">
            <span class="material-symbols-outlined text-[22px]">{{ $icon }}</span>
        </div>
    </div>
    @if($bottom)
    <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between gap-2 relative">
        {!! $bottom !!}
    </div>
    @endif
    {{ $slot }}
</div>
