@props(['color' => 'primary', 'size' => 'md', 'icon' => null, 'iconOnly' => false])

{{-- Botão de submit de formulário. Cor por módulo: danger=despesa, success=receita, blue=investimentos, orange=cartões. --}}
<button type="submit" title="{{ $attributes->get('title') }}"
    {{ $attributes->except('title')->merge(['class' => \App\Support\Btn::classes($color, $size, $iconOnly)]) }}>
    @if($icon)
        <span class="material-symbols-outlined {{ \App\Support\Btn::iconSize($size, $iconOnly) }}">{{ $icon }}</span>
    @endif
    @if(!$iconOnly){{ $slot }}@endif
</button>
