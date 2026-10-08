@extends('layouts.admin')
@section('title', 'Maintenance')
@section('heading', 'Disponibilité et maintenance')
@section('content')
<div class="d-flex justify-content-between flex-wrap gap-2 mb-3"><form method="GET" class="row g-2"><div class="col-auto"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Équipement"></div><div class="col-auto"><select name="status" class="form-select"><option value="">Tous statuts</option>@foreach(\App\Models\MaintenanceRecord::STATUSES as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select></div><div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-funnel"></i></button></div></form></div>
<div class="card card-ad"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Équipement</th><th>Intervention</th><th>Propriétaire</th><th>Période</th><th>Statut</th><th>Coût</th></tr></thead><tbody>
@forelse($records as $record)
    <tr><td class="fw-semibold">{{ $record->equipment->title }}</td><td>{{ $record->title }}<br><small class="text-muted">{{ $record->reason_label }}</small></td><td>{{ $record->equipment->owner->name }}</td><td class="small">{{ $record->starts_on->format('d/m/Y') }} → {{ $record->ends_on->format('d/m/Y') }}</td><td><x-badge :status="$record->status" :labels="\App\Models\MaintenanceRecord::STATUSES" /></td><td>{{ $record->cost ? number_format($record->cost, 2).' DT' : '—' }}</td></tr>
@empty
    <tr><td colspan="6" class="text-center text-muted py-4">Aucune intervention enregistrée.</td></tr>
@endforelse
</tbody></table></div></div><div class="mt-3">{{ $records->links() }}</div>
@endsection
