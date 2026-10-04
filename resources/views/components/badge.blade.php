@props(['status', 'labels' => []])
@php
    $colors = [
        'available' => 'success', 'confirmed' => 'success', 'completed' => 'success', 'paid' => 'success',
        'resolved' => 'success', 'done' => 'success', 'positive' => 'success', 'low' => 'success',
        'pending' => 'warning', 'planned' => 'warning', 'open' => 'warning', 'medium' => 'warning',
        'neutral' => 'secondary', 'refunded' => 'secondary', 'dismissed' => 'secondary',
        'ongoing' => 'info', 'in_progress' => 'info', 'rented' => 'info', 'maintenance' => 'info',
        'rejected' => 'danger', 'cancelled' => 'danger', 'negative' => 'danger', 'high' => 'danger',
        'critical' => 'dark',
    ];
@endphp
<span class="badge text-bg-{{ $colors[$status] ?? 'secondary' }}">{{ $labels[$status] ?? $status }}</span>
