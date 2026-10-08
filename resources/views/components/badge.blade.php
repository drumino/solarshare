@props(['status', 'labels' => []])
@php
    $colors = [
        'admin' => 'dark', 'user' => 'secondary',
        'available' => 'success', 'pending' => 'warning', 'rented' => 'info', 'maintenance' => 'info', 'rejected' => 'danger',
    ];
@endphp
<span class="badge text-bg-{{ $colors[$status] ?? 'secondary' }}">{{ $labels[$status] ?? $status }}</span>
