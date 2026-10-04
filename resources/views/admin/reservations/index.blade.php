@extends('layouts.admin')
@section('title', 'Réservations')
@section('heading', 'Réservations')
@section('content')
<div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
    <form class="row g-2" method="GET">
        <div class="col-auto"><input name="q" class="form-control" placeholder="Équipement ou locataire" value="{{ request('q') }}"></div>
        <div class="col-auto"><select name="status" class="form-select"><option value="">Tous statuts</option>
            @foreach(\App\Models\Reservation::STATUSES as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-funnel"></i></button></div>
    </form>
    <a href="{{ route('admin.reservations.create') }}" class="btn btn-warning"><i class="bi bi-plus-lg"></i> Nouvelle réservation</a>
</div>
<div class="card card-ad"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>#</th><th>Équipement</th><th>Locataire</th><th>Période</th><th>Total</th><th>Payé</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    @forelse($reservations as $r)
        <tr><td>{{ $r->id }}</td><td class="fw-semibold">{{ $r->equipment_title }}</td><td>{{ $r->user_name }}</td>
            <td>{{ $r->start_date->format('d/m/Y') }} → {{ $r->end_date->format('d/m/Y') }}</td>
            <td>{{ number_format($r->total_price, 2) }} DT</td><td>{{ number_format((float) $r->paid_total, 2) }} DT</td>
            <td><x-badge :status="$r->status" :labels="\App\Models\Reservation::STATUSES" /></td>
            <td class="text-end text-nowrap">
                <a href="{{ route('admin.payments.create', ['reservation' => $r->id]) }}" class="btn btn-sm btn-outline-success" title="Ajouter un paiement"><i class="bi bi-cash"></i></a>
                <a href="{{ route('admin.reservations.edit', $r) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                @include('partials.delete', ['route' => route('admin.reservations.destroy', $r)])</td></tr>
    @empty
        <tr><td colspan="8" class="text-center text-muted py-4">Aucune réservation.</td></tr>
    @endforelse
    </tbody></table></div></div>
<div class="mt-3">{{ $reservations->links() }}</div>
@endsection
