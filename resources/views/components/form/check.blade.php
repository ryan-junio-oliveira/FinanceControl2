@props(['label' => '', 'hint' => null])

<label {{ $attributes->merge(['class' => 'flex items-start gap-2.5 text-[13px] text-gray-600 cursor-pointer select-none']) }}>
    <input type="checkbox" {{ $attributes->except('class') }} class="w-5 h-5 mt-0.5 rounded-md accent-emerald-600 shrink-0" />
    <span>
        <span class="font-semibold">{{ $label }}</span>
        @if($hint)<span class="block text-[12px] text-gray-400 font-normal mt-0.5">{{ $hint }}</span>@endif
    </span>
</label>
