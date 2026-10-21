@extends('layouts.front')
@section('title', $equipment->title)
@section('content')
<div class="row g-4">
    <div class="col-lg-6">
        @if($equipment->getImageUrlAttribute())
            <img src="{{ $equipment->getImageUrlAttribute() }}" alt="{{ $equipment->title }}" class="img-fluid rounded-3 shadow-sm">
        @else
            <div class="rounded-3 bg-secondary d-flex align-items-center justify-content-center text-white shadow-sm" style="height:420px;">
                <i class="bi bi-battery-charging display-4"></i>
            </div>
        @endif
    </div>
    <div class="col-lg-6">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h1 class="h2 mb-0">{{ $equipment->title }}</h1>
            @if($equipment->is_top_rated)
                <span class="badge text-bg-warning text-dark fs-6"
                      data-bs-toggle="tooltip" data-bs-placement="top"
                      title="Top Rated : score bayésien {{ number_format($equipment->bayesian_score, 3) }} (moyenne pondérée selon le nombre d'avis visibles).">
                    Top Rated
                </span>
            @endif
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="small text-muted">Type</div>
                <div>{{ $equipment->getTypeLabelAttribute() }}</div>
            </div>
            <div class="col-md-6">
                <div class="small text-muted">État</div>
                <div>{{ \App\Models\Equipment::CONDITIONS[$equipment->condition] ?? $equipment->condition }}</div>
            </div>
            <div class="col-md-6">
                <div class="small text-muted">Ville</div>
                <div>{{ $equipment->city }}</div>
            </div>
            <div class="col-md-6">
                <div class="small text-muted">Catégorie</div>
                <div>{{ $equipment->category->name ?? '-' }}</div>
            </div>
        </div>

        <div class="d-flex align-items-center mb-4">
            <x-stars :rating="$equipment->reviews_avg ?? 0" />
            <span class="text-muted small ms-2">
                {{ number_format($equipment->reviews_avg ?? 0, 2) }}/5
                <span class="badge text-bg-secondary ms-2">{{ $equipment->reviews_count }} avis</span>
            </span>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="small text-muted mb-2">Description</div>
                <p class="mb-0">{{ $equipment->description ?? 'Aucune description.' }}</p>
            </div>
        </div>

        <div class="d-flex gap-2">
            <span class="badge text-bg-{{ $equipment->status === 'available' ? 'success' : 'secondary' }} fs-6">
                {{ \App\Models\Equipment::STATUSES[$equipment->status] ?? $equipment->status }}
            </span>
        </div>

        <div class="mt-4">
            <span class="h4">{{ number_format($equipment->price_per_day, 2) }} €/jour</span>
            @if($equipment->deposit > 0)
                <span class="text-muted small ms-2">· dépôt {{ number_format($equipment->deposit, 2) }} €</span>
            @endif
        </div>

        @if($equipment->reviews->count())
        <div class="mt-5">
            <h3 class="h5 mb-3">Avis récents</h3>
            <div class="row g-3">
                @foreach($equipment->reviews as $review)
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="fw-semibold">{{ $review->user->name }}</div>
                                    <div class="text-end small"><x-stars :rating="$review->rating" /></div>
                                </div>
                                <p class="mb-1 small">{{ \Illuminate\Support\Str::limit($review->comment, 220) }}</p>
                                <div class="small text-muted">{{ $review->created_at->format('d/m/Y') }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
