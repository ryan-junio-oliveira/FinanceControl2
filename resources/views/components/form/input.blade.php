@props(['id' => null, 'error' => false])

<input
    @if($id) id="{{ $id }}" @endif
    {{ $attributes->merge(['class' => 'fld-control' . ($error ? ' is-error' : '')]) }}
/>
