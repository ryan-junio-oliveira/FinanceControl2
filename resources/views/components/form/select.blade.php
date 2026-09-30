@props(['id' => null, 'error' => false])

<select
    @if($id) id="{{ $id }}" @endif
    {{ $attributes->merge(['class' => 'fld-control']) }}
>{{ $slot }}</select>
