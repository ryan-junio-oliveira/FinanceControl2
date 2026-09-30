@props(['id' => null])

<textarea
    @if($id) id="{{ $id }}" @endif
    {{ $attributes->merge(['class' => 'fld-control']) }}
>{{ $slot }}</textarea>
