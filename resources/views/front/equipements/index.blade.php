@extends('layouts.front')
@section('title', 'Nos équipements')
@section('content')
<div class="row mb-3">
    <div class="col-12">
        <h2 class="h4">Nos équipements disponibles</h2>
        <p class="text-muted">Trouvez le matériel solaire disponible à la location près de chez vous.</p>
    </div>
</div>

<form method="GET" action="{{ route('front.equipements.index') }}" class="row g-3 mb-4 align-items-end">
    <div class="col-md-4">
        <label class="form-label small text-muted">Trier par</label>
        <select name="tri" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="recents" @selected($tri === 'recents')>Récents</option>
            <option value="mieux_notes" @selected($tri === 'mieux_notes')>Mieux notés (score bayésien)</option>
            <option value="prix_asc" @selected($tri === 'prix_asc')>Prix croissant</option>
            <option value="prix_desc" @selected($tri === 'prix_desc')>Prix décroissant</option>
        </select>
    </div>
    <div class="col-md-3 d-grid">
        <button type="submit" name="top" value="1" class="btn btn-outline-{{ $top ? 'warning' : 'secondary' }} btn-sm {{ $top ? 'text-dark fw-semibold' : '' }}">
            <i class="bi bi-star-fill me-1"></i> Top Rated
        </button>
    </div>
    <input type="hidden" name="tri" value="{{ $tri }}">
</form>

<div class="row g-4">
    @forelse($equipments as $equipment)
        <div class="col-md-6 col-lg-4 col-xl-3">
            <x-equipment-card :equipment="$equipment" />
        </div>
    @empty
        <div class="col-12">
            <div class="text-center py-5 text-muted">
                <i class="bi bi-search-off display-4 mb-3 opacity-50"></i>
                <p class="h6">Aucun équipement @if($top) « Top Rated » @endif trouvé.</p>
            </div>
        </div>
    @endforelse
</div>

<div class="mt-4">{{ $equipments->links() }}</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endpush
