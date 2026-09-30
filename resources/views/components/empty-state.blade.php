@props(['icon' => 'inbox', 'title' => 'Nada por aqui', 'hint' => null, 'actionUrl' => null, 'actionLabel' => null, 'actionIcon' => 'add_circle', 'actionColor' => 'primary'])

{{-- Estado vazio padrão de tabelas e listas. --}}
<div class="text-center py-12 px-4">
    <span class="w-14 h-14 rounded-2xl bg-slate-100 grid place-items-center mx-auto">
        <span class="material-symbols-outlined text-[28px] text-slate-400">{{ $icon }}</span>
    </span>
    <p class="text-[14px] font-extrabold mt-4 text-gray-600">{{ $title }}</p>
    @if($hint)
    <p class="text-[12px] text-gray-400 mt-1">{{ $hint }}</p>
    @endif
    @if($actionUrl)
    <x-btn-link :href="$actionUrl" :icon="$actionIcon" :color="$actionColor" size="sm" class="mt-4">{{ $actionLabel }}</x-btn-link>
    @endif
</div>
