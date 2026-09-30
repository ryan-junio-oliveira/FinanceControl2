@props(['id' => null, 'prefix' => 'R$', 'error' => false])

<div class="fld-money">
    <span class="prefix">{{ $prefix }}</span>
    <input
        @if($id) id="{{ $id }}" @endif
        inputmode="decimal"
        {{ $attributes->merge(['class' => 'num']) }}
    />
</div>
