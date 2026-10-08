@extends('layouts.front')
@section('title', 'Mes équipements')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h1 class="section-title h3 mb-1">Mes équipements</h1><p class="text-muted mb-0">Planifiez les périodes d'entretien et rendez les indisponibilités visibles.</p></div>
    <a href="{{ route('front.equipment.create') }}" class="btn btn-sun"><i class="bi bi-plus-lg"></i> Proposer</a>
</div>

<div class="row g-4">
@forelse($equipment as $eq)
    <div class="col-md-6 col-lg-4"><div class="card form-card h-100">
        <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between gap-2"><h2 class="h5">{{ $eq->title }}</h2><x-badge :status="$eq->status" :labels="\App\Models\Equipment::STATUSES" /></div>
            <p class="small text-muted"><i class="bi bi-geo-alt"></i> {{ $eq->city }} · {{ $eq->type_label }}</p>
            @if($eq->active_maintenance_count)
                <div class="alert alert-warning py-2 small"><i class="bi bi-tools"></i> Indisponible aujourd'hui</div>
            @else
                <div class="text-success small mb-3"><i class="bi bi-check-circle"></i> Disponible aujourd'hui</div>
            @endif
            <div class="mt-auto d-flex justify-content-between align-items-center">
                <span class="small text-muted">{{ $eq->maintenanceRecords->count() }} intervention(s) récente(s)</span>
                <a href="{{ route('front.maintenance.manage', $eq) }}" class="btn btn-outline-sun btn-sm"><i class="bi bi-calendar-range"></i> Gérer</a>
            </div>
        </div>
    </div></div>
@empty
    <div class="col-12"><div class="alert alert-light border">Vous n'avez pas encore proposé d'équipement. <a href="{{ route('front.equipment.create') }}">Créer une annonce</a>.</div></div>
@endforelse
</div>
@endsection
