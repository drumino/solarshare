@props(['status', 'labels' => []])
@php
    $colors = [
        'admin' => 'dark', 'user' => 'secondary',
    ];
@endphp
<span class="badge text-bg-{{ $colors[$status] ?? 'secondary' }}">{{ $labels[$status] ?? $status }}</span>
