@props(['cancelUrl' => null, 'cancelLabel' => 'Cancelar', 'submitLabel' => 'Salvar', 'submitIcon' => 'save', 'color' => 'primary'])

<div class="form-actions">
    @if($cancelUrl)
        <x-btn-link :href="$cancelUrl" color="ghost" icon="close">{{ $cancelLabel }}</x-btn-link>
    @endif
    <x-btn-submit :color="$color" :icon="$submitIcon" {{ $attributes }}>{{ $submitLabel }}</x-btn-submit>
</div>
