@props(['role'])
@php
$map = [
    'admin' => ['type' => 'neutral', 'icon' => 'shield'],
    'co_admin' => ['type' => 'info', 'icon' => 'manage_accounts'],
    'dependente' => ['type' => 'success', 'icon' => 'person'],
    'junior' => ['type' => 'warning', 'icon' => 'school'],
];
$c = $map[$role] ?? ['type' => 'neutral', 'icon' => 'tag'];
$label = \App\Models\User::ROLES[$role] ?? $role;
@endphp
<x-badge :type="$c['type']" :icon="$c['icon']">{{ $label }}</x-badge>
