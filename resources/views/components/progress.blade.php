@props(['value'=>50,'height'=>'h-1.5'])
@php
$v = max(0,min(100,(float)$value));
if($v <= 75)      $cls = 'progress-fill safe';
elseif($v <= 90)  $cls = 'progress-fill warn';
else              $cls = 'progress-fill danger';
@endphp
<div {{ $attributes->merge(['class'=>"progress-track {$height}"]) }}>
    <div class="{{ $cls }}" style="width:{{ $v }}%"></div>
</div>
