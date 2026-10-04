@extends('layouts.admin')
@section('title', 'Incidents')
@section('heading', 'Incidents')
@section('content')
<div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
    <form class="row g-2" method="GET">
        <div class="col-auto"><input name="q" class="form-control" placeholder="Incident ou équipement" value="{{ request('q') }}"></div>
        <div class="col-auto"><select name="severity" class="form-select"><option value="">Toutes gravités</option>
            @foreach(\App\Models\Incident::SEVERITIES as $k => $l)<option value="{{ $k }}" @selected(request('severity') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-auto"><select name="status" class="form-select"><option value="">Tous statuts</option>
            @foreach(\App\Models\Incident::STATUSES as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-funnel"></i></button></div>
    </form>
    <a href="{{ route('admin.incidents.create') }}" class="btn btn-warning"><i class="bi bi-plus-lg"></i> Nouvel incident</a>
</div>
<div class="card card-ad"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Incident</th><th>Équipement</th><th>Déclarant</th><th>Catégorie</th><th>Gravité</th><th>Statut</th><th>Tâches</th><th></th></tr></thead>
    <tbody>
    @forelse($incidents as $i)
        <tr><td class="fw-semibold">{{ $i->title }}@if($i->ai_advice)<br><small class="fw-normal text-muted"><span class="ai-chip">IA</span> {{ \Illuminate\Support\Str::limit($i->ai_advice, 70) }}</small>@endif</td>
            <td>{{ $i->equipment_title }}</td><td>{{ $i->reporter_name }}</td>
            <td>{{ \App\Models\Incident::CATEGORIES[$i->category] ?? $i->category }}</td>
            <td><x-badge :status="$i->severity" :labels="\App\Models\Incident::SEVERITIES" /></td>
            <td><x-badge :status="$i->status" :labels="\App\Models\Incident::STATUSES" /></td>
            <td><span class="badge text-bg-light border">{{ $i->tasks_count }}</span></td>
            <td class="text-end text-nowrap">
                <a href="{{ route('admin.maintenance-tasks.create', ['incident' => $i->id]) }}" class="btn btn-sm btn-outline-success" title="Planifier une intervention"><i class="bi bi-tools"></i></a>
                <a href="{{ route('admin.incidents.edit', $i) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                @include('partials.delete', ['route' => route('admin.incidents.destroy', $i)])</td></tr>
    @empty
        <tr><td colspan="8" class="text-center text-muted py-4">Aucun incident.</td></tr>
    @endforelse
    </tbody></table></div></div>
<div class="mt-3">{{ $incidents->links() }}</div>
@endsection
