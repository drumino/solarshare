@extends('layouts.front')
@section('title', 'Maintenance · '.$equipment->title)
@section('content')
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('front.maintenance.index') }}">Mes équipements</a></li><li class="breadcrumb-item active">{{ $equipment->title }}</li></ol></nav>
<div class="row g-4">
    <div class="col-lg-5"><div class="card form-card p-4">
        <h1 class="h4">Ajouter une indisponibilité</h1>
        <p class="small text-muted">Les interventions planifiées ou en cours bloquent l'équipement dans le catalogue pendant les dates indiquées.</p>
        <form method="POST" action="{{ route('front.maintenance.store', $equipment) }}">@csrf
            <div class="mb-3"><label class="form-label">Titre</label><input class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title') }}" placeholder="Ex. Révision annuelle" required>@error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label class="form-label">Motif</label><select class="form-select" name="reason">@foreach(\App\Models\MaintenanceRecord::REASONS as $key => $label)<option value="{{ $key }}" @selected(old('reason', $record->reason) === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="row g-2"><div class="col-sm-6 mb-3"><label class="form-label">Du</label><input type="date" class="form-control @error('starts_on') is-invalid @enderror" name="starts_on" value="{{ old('starts_on', $record->starts_on?->format('Y-m-d')) }}" required>@error('starts_on')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="col-sm-6 mb-3"><label class="form-label">Au</label><input type="date" class="form-control @error('ends_on') is-invalid @enderror" name="ends_on" value="{{ old('ends_on', $record->ends_on?->format('Y-m-d')) }}" required>@error('ends_on')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
            <div class="mb-3"><label class="form-label">Statut</label><select class="form-select" name="status">@foreach(\App\Models\MaintenanceRecord::STATUSES as $key => $label)<option value="{{ $key }}" @selected(old('status', $record->status) === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="mb-3"><label class="form-label">Description <small class="text-muted">(facultative)</small></label><textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea></div>
            <div class="row g-2"><div class="col-sm-5 mb-3"><label class="form-label">Coût (DT)</label><input type="number" class="form-control" name="cost" min="0" step="0.01" value="{{ old('cost') }}"></div><div class="col-sm-7 mb-3"><label class="form-label">Notes de résolution</label><input class="form-control" name="resolution_notes" value="{{ old('resolution_notes') }}"></div></div>
            <button class="btn btn-sun w-100"><i class="bi bi-calendar-plus"></i> Enregistrer</button>
        </form>
    </div></div>
    <div class="col-lg-7"><div class="card form-card"><div class="card-body border-bottom"><h2 class="h4 mb-0">Historique et calendrier</h2></div>
        <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Intervention</th><th>Période</th><th>Statut</th><th></th></tr></thead><tbody>
        @forelse($records as $item)
            <tr>
                <td>
                    <span class="fw-semibold">{{ $item->title }}</span><br>
                    <small class="text-muted">
                        {{ $item->reason_label }}
                        @if($item->cost)
                            · {{ number_format($item->cost, 2) }} DT
                        @endif
                    </small>
                </td>
                <td class="small">{{ $item->starts_on->format('d/m/Y') }}<br>→ {{ $item->ends_on->format('d/m/Y') }}</td>
                <td>
                    <form method="POST" action="{{ route('front.maintenance.update', [$equipment, $item]) }}" class="d-flex gap-1">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="title" value="{{ $item->title }}">
                        <input type="hidden" name="reason" value="{{ $item->reason }}">
                        <input type="hidden" name="starts_on" value="{{ $item->starts_on->format('Y-m-d') }}">
                        <input type="hidden" name="ends_on" value="{{ $item->ends_on->format('Y-m-d') }}">
                        <input type="hidden" name="description" value="{{ $item->description }}">
                        <input type="hidden" name="cost" value="{{ $item->cost }}">
                        <input type="hidden" name="resolution_notes" value="{{ $item->resolution_notes }}">
                        <select name="status" class="form-select form-select-sm" aria-label="Statut de l'intervention">
                            @foreach(\App\Models\MaintenanceRecord::STATUSES as $key => $label)
                                <option value="{{ $key }}" @selected($item->status === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-outline-primary" title="Mettre à jour"><i class="bi bi-check-lg"></i></button>
                    </form>
                </td>
                <td class="text-end">
                    <form method="POST" action="{{ route('front.maintenance.destroy', [$equipment, $item]) }}" onsubmit="return confirm('Supprimer cette intervention ?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted py-4">Aucune intervention enregistrée.</td></tr>
        @endforelse
        </tbody></table></div>
    </div></div>
</div>
@endsection
