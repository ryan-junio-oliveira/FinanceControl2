@props(['label' => null, 'for' => null, 'required' => false, 'error' => null, 'hint' => null])

<div {{ $attributes->merge(['class' => 'fld' . ($error ? ' fld-error' : '')]) }}>
    @if($label)
        <label @if($for) for="{{ $for }}" @endif class="fld-label">{{ $label }}@if($required) <span class="req">*</span>@endif</label>
    @endif
    {{ $slot }}
    @if($hint && !$error)
        <p class="fld-hint">{{ $hint }}</p>
    @endif
    @if($error)
        <p class="fld-msg-error"><span class="material-symbols-outlined text-[14px]">error</span>{{ $error }}</p>
    @endif
</div>
