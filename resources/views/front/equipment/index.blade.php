@extends('layouts.front')
@section('title', 'Catalogue')
@section('content')
<h1 class="section-title h3 mb-3">Catalogue d'équipements</h1>

<form method="GET" class="card form-card p-3 mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small">Recherche</label>
            <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Titre, catégorie..."></div>
        <div class="col-md-2"><label class="form-label small">Catégorie</label>
            <select name="category" class="form-select"><option value="">Toutes</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" @selected(($filters['category'] ?? '') == $c->id)>{{ $c->name }}</option>@endforeach
            </select></div>
        <div class="col-md-2"><label class="form-label small">Type</label>
            <select name="type" class="form-select"><option value="">Tous</option>
                @foreach(\App\Models\Equipment::TYPES as $k => $l)<option value="{{ $k }}" @selected(($filters['type'] ?? '') === $k)>{{ $l }}</option>@endforeach
            </select></div>
        <div class="col-md-2"><label class="form-label small">Ville</label>
            <input type="text" name="city" class="form-control" value="{{ $filters['city'] ?? '' }}"></div>
        <div class="col-md-1"><label class="form-label small">Prix max</label>
            <input type="number" name="max_price" class="form-control" value="{{ $filters['max_price'] ?? '' }}" min="1"></div>
        <div class="col-md-2"><label class="form-label small">Tri</label>
            <select name="sort" class="form-select">
                @foreach(['recent' => 'Plus récents', 'price_asc' => 'Prix croissant', 'price_desc' => 'Prix décroissant', 'power' => 'Puissance'] as $k => $l)
                    <option value="{{ $k }}" @selected(($filters['sort'] ?? 'recent') === $k)>{{ $l }}</option>
                @endforeach
            </select></div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-sun"><i class="bi bi-search"></i> Filtrer</button>
        <a href="{{ route('front.equipment.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
    </div>
</form>

<p class="text-muted">{{ $equipment->total() }} résultat(s)</p>
<div class="row g-4">
    @forelse($equipment as $eq)
        <div class="col-md-6 col-lg-4">@include('partials.equipment-card', ['eq' => $eq])</div>
    @empty
        <div class="col-12"><div class="alert alert-light border">Aucun équipement ne correspond à votre recherche.</div></div>
    @endforelse
</div>
<div class="mt-4">{{ $equipment->links() }}</div>
@endsection
