@props(['color' => 'primary', 'size' => 'md', 'icon' => null, 'iconOnly' => false])

{{-- Botão de ação JavaScript / sem submit (recebe onclick, data-*, etc. via atributos). --}}
<button type="button" title="{{ $attributes->get('title') }}"
    {{ $attributes->except('title')->merge(['class' => \App\Support\Btn::classes($color, $size, $iconOnly)]) }}>
    @if($icon)
        <span class="material-symbols-outlined {{ \App\Support\Btn::iconSize($size, $iconOnly) }}">{{ $icon }}</span>
    @endif
    @if(!$iconOnly){{ $slot }}@endif
</button>
