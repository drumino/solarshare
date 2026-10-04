@extends('layouts.admin')
@section('title', 'Équipements')
@section('heading', 'Équipements')
@section('content')
<div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
    <form class="row g-2" method="GET">
        <div class="col-auto"><input name="q" class="form-control" placeholder="Titre ou propriétaire" value="{{ request('q') }}"></div>
        <div class="col-auto"><select name="category" class="form-select"><option value="">Toutes catégories</option>
            @foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
        <div class="col-auto"><select name="status" class="form-select"><option value="">Tous statuts</option>
            @foreach(\App\Models\Equipment::STATUSES as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-funnel"></i></button></div>
    </form>
    <a href="{{ route('admin.equipment.create') }}" class="btn btn-warning"><i class="bi bi-plus-lg"></i> Nouvel équipement</a>
</div>
<div class="card card-ad"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Équipement</th><th>Catégorie</th><th>Propriétaire</th><th>Spécifications</th><th>Prix/jour</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    @forelse($equipment as $e)
        <tr><td class="fw-semibold">{{ $e->title }}<br><small class="text-muted">{{ $e->city }}</small></td>
            <td>{{ $e->category_name }}</td><td>{{ $e->owner_name }}</td>
            <td class="small">{{ \App\Models\Equipment::TYPES[$e->type] ?? $e->type }}<br>{{ $e->power_watts ? $e->power_watts.' W ' : '' }}{{ $e->capacity_wh ? $e->capacity_wh.' Wh' : '' }}</td>
            <td>{{ number_format($e->price_per_day, 2) }} DT</td>
            <td><x-badge :status="$e->status" :labels="\App\Models\Equipment::STATUSES" /></td>
            <td class="text-end text-nowrap"><a href="{{ route('admin.equipment.edit', $e) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                @include('partials.delete', ['route' => route('admin.equipment.destroy', $e)])</td></tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted py-4">Aucun équipement.</td></tr>
    @endforelse
    </tbody></table></div></div>
<div class="mt-3">{{ $equipment->links() }}</div>
@endsection
