@extends('layouts.admin')
@section('title', 'Maintenance')
@section('heading', 'Tâches de maintenance')
@section('content')
<div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
    <form class="row g-2" method="GET">
        <div class="col-auto"><input name="q" class="form-control" placeholder="Tâche ou équipement" value="{{ request('q') }}"></div>
        <div class="col-auto"><select name="status" class="form-select"><option value="">Tous statuts</option>
            @foreach(\App\Models\MaintenanceTask::STATUSES as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-funnel"></i></button></div>
    </form>
    <a href="{{ route('admin.maintenance-tasks.create') }}" class="btn btn-warning"><i class="bi bi-plus-lg"></i> Nouvelle tâche</a>
</div>
<div class="card card-ad"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Tâche</th><th>Incident</th><th>Équipement</th><th>Technicien</th><th>Prévue</th><th>Coût</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    @forelse($tasks as $t)
        <tr><td class="fw-semibold">{{ $t->title }}</td><td>{{ $t->incident_title }}</td><td>{{ $t->equipment_title }}</td>
            <td>{{ $t->technician_name ?? '—' }}</td>
            <td>{{ $t->planned_at->format('d/m/Y') }}@if($t->completed_at)<br><small class="text-muted">fin {{ $t->completed_at->format('d/m/Y') }}</small>@endif</td>
            <td>{{ number_format($t->cost, 2) }} DT</td>
            <td><x-badge :status="$t->status" :labels="\App\Models\MaintenanceTask::STATUSES" /></td>
            <td class="text-end text-nowrap"><a href="{{ route('admin.maintenance-tasks.edit', $t) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                @include('partials.delete', ['route' => route('admin.maintenance-tasks.destroy', $t)])</td></tr>
    @empty
        <tr><td colspan="8" class="text-center text-muted py-4">Aucune tâche.</td></tr>
    @endforelse
    </tbody></table></div></div>
<div class="mt-3">{{ $tasks->links() }}</div>
@endsection
