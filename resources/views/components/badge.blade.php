@props(['type'=>'success','icon'=>null])
@php
$map = [
  'success'  => ['cls'=>'bg-emerald-50 text-emerald-700 border-emerald-200/80','icon'=>$icon??'check_circle'],
  'warning'  => ['cls'=>'bg-amber-50 text-amber-700 border-amber-200/80','icon'=>$icon??'warning'],
  'critical' => ['cls'=>'bg-red-50 text-red-700 border-red-200/80','icon'=>$icon??'error'],
  'info'     => ['cls'=>'bg-blue-50 text-blue-700 border-blue-200/80','icon'=>$icon??'info'],
  'neutral'  => ['cls'=>'bg-slate-100 text-gray-600 border-slate-200','icon'=>$icon??'tag'],
];
$c=$map[$type]??$map['success'];
@endphp
<span {{ $attributes->merge(['class'=>"badge border {$c['cls']}"]) }}>
    <span class="material-symbols-outlined" style="font-size:12px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20">{{ $c['icon'] }}</span>
    {{ $slot }}
</span>
