@props(['equipment', 'showTopRatedBadge' => true])

<div class="card equipment-card h-100">
    @if($equipment->getImageUrlAttribute())
        <img src="{{ $equipment->getImageUrlAttribute() }}" alt="{{ $equipment->title }}" class="card-img-top equipment-img" loading="lazy">
    @else
        <div class="card-img-top equipment-img bg-secondary d-flex align-items-center justify-content-center text-white">
            <i class="bi bi-battery-charging"></i>
        </div>
    @endif
    <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 class="card-title mb-0">{{ $equipment->title }}</h5>
            @if($showTopRatedBadge && $equipment->is_top_rated)
                <span class="badge text-bg-warning text-dark fs-6"
                      data-bs-toggle="tooltip" data-bs-placement="top"
                      title="Top Rated : score bayésien {{ number_format($equipment->bayesian_score, 3) }} (moyenne pondérée selon le nombre d'avis visibles).">
                    Top Rated
                </span>
            @endif
        </div>
        <div class="small text-muted mb-1">
            <i class="bi bi-geo-alt-fill me-1"></i> {{ $equipment->city }}
            @if($equipment->category)
                <span class="ms-2"><i class="bi bi-tag me-1"></i> {{ $equipment->category->name }}</span>
            @endif
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <x-stars :rating="$equipment->reviews_avg" />
                <span class="text-muted small ms-1">{{ number_format($equipment->reviews_avg, 2) }}/5
                    <span class="text-xs text-muted">({{ $equipment->reviews_count }} avis)</span>
                </span>
            </div>
            <span class="badge text-bg-{{ $equipment->status === 'available' ? 'success' : 'secondary' }} fw-normal">
                {{ \App\Models\Equipment::STATUSES[$equipment->status] ?? $equipment->status }}
            </span>
        </div>
        <div class="mt-auto">
            <span class="h5 mb-0">{{ number_format($equipment->price_per_day, 2) }} €/jour</span>
            @if($equipment->deposit > 0)
                <span class="text-muted small ms-2">· dépôt {{ number_format($equipment->deposit, 2) }} €</span>
            @endif
        </div>
    </div>
    <div class="card-footer d-grid">
        <a href="{{ route('front.equipements.show', $equipment) }}" class="btn btn-sun">Voir le détail</a>
    </div>
</div>
