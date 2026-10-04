@extends('layouts.front')
@section('title', 'Mes réservations')
@section('content')
<h1 class="section-title h3 mb-3">Mes réservations</h1>
<div class="card form-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Équipement</th><th>Période</th><th>Total</th><th>Statut</th><th></th></tr></thead>
            <tbody>
            @forelse($reservations as $r)
                <tr>
                    <td><a href="{{ route('front.equipment.show', $r->equipment) }}">{{ $r->equipment->title }}</a><br><small class="text-muted">{{ $r->equipment->category->name }}</small></td>
                    <td>{{ $r->start_date->format('d/m/Y') }} → {{ $r->end_date->format('d/m/Y') }}<br><small class="text-muted">{{ $r->days }} jour(s)</small></td>
                    <td class="fw-semibold">{{ number_format($r->total_price, 2) }} DT</td>
                    <td><x-badge :status="$r->status" :labels="\App\Models\Reservation::STATUSES" /></td>
                    <td class="text-end"><a href="{{ route('front.reservations.show', $r) }}" class="btn btn-sm btn-outline-sun">Voir</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Aucune réservation. <a href="{{ route('front.equipment.index') }}">Parcourir le catalogue</a></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $reservations->links() }}</div>
@endsection
