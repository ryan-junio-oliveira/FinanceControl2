@props(['title' => '', 'subtitle' => '', 'backUrl' => null, 'backLabel' => 'Voltar', 'icon' => null, 'iconBg' => 'linear-gradient(135deg,#ECFDF5,#D1FAE5)', 'iconColor' => '#059669'])

<div class="mb-6">
    @if($backUrl)
        <a href="{{ $backUrl }}" class="back-link">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            {{ $backLabel }}
        </a>
    @endif
    <div class="flex items-center gap-3 mt-3">
        @if($icon)
            <div class="w-11 h-11 rounded-xl grid place-items-center shrink-0" style="background: {{ $iconBg }};">
                <span class="material-symbols-outlined text-[22px]" style="font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24; color: {{ $iconColor }}">{{ $icon }}</span>
            </div>
        @endif
        <div class="min-w-0">
            <h1 class="text-[22px] lg:text-[24px] font-extrabold tracking-tight text-gray-900 leading-tight">{{ $title }}</h1>
            @if($subtitle)
                <p class="text-[13px] text-gray-500 mt-0.5">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
</div>
