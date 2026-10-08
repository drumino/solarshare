@props(['status', 'labels' => []])
@php
    $colors = [
        'admin' => 'dark', 'user' => 'secondary',
        'available' => 'success', 'pending' => 'warning', 'rented' => 'info', 'maintenance' => 'info', 'rejected' => 'danger',
        'scheduled' => 'warning', 'in_progress' => 'info', 'completed' => 'success', 'cancelled' => 'secondary',
    ];
@endphp
<span class="badge text-bg-{{ $colors[$status] ?? 'secondary' }}">{{ $labels[$status] ?? $status }}</span>
