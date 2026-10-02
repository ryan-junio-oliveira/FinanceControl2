@props(['id' => null, 'prefix' => 'R$', 'error' => false])

@php
    // O IMask espera o valor já no formato BR (vírgula). Sem isso, um valor
    // vindo do banco como "30.00" (ponto decimal) é lido como "3.000".
    $value = $attributes->get('value');
    $fmt = $value;
    if ($value !== null && $value !== '') {
        $parsed = \App\Http\Requests\FormRequest::parseBrazilianDecimal((string) $value);
        $fmt = $parsed !== null ? number_format((float) $parsed, 2, ',', '.') : (string) $value;
    }
@endphp

<div class="fld-money">
    <span class="prefix">{{ $prefix }}</span>
    <input
        @if($id) id="{{ $id }}" @endif
        inputmode="decimal"
        value="{{ $fmt }}"
        {{ $attributes->except(['value'])->merge(['class' => 'num']) }}
    />
</div>