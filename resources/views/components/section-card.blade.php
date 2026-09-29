@props(['title','subtitle'=>null,'action'=>null])
<section {{ $attributes->merge(['class'=>'section-card', 'data-reveal' => '']) }}>
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h2 class="font-extrabold text-[15px] tracking-tight text-gray-900">{{ $title }}</h2>
            @if($subtitle)
            <p class="text-[12px] text-gray-400 mt-0.5 font-medium">{{ $subtitle }}</p>
            @endif
        </div>
        @if($action)<div>{{ $action }}</div>@endif
    </div>
    {{ $slot }}
</section>
