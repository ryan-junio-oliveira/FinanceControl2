@props(['href' => '#', 'color' => 'primary', 'size' => 'md', 'icon' => null, 'iconOnly' => false])

{{-- Botão-link: navegação (ex.: "Novo …", "Voltar", "Editar"). --}}
<a href="{{ $href }}" title="{{ $attributes->get('title') }}"
    {{ $attributes->except('title')->merge(['class' => \App\Support\Btn::classes($color, $size, $iconOnly)]) }}>
    @if($icon)
        <span class="material-symbols-outlined {{ \App\Support\Btn::iconSize($size, $iconOnly) }}">{{ $icon }}</span>
    @endif
    @if(!$iconOnly){{ $slot }}@endif
</a>
