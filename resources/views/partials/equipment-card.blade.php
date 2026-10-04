<div class="card eq-card">
    <div class="eq-thumb">
        @if($eq->image_url)
            <img src="{{ $eq->image_url }}" alt="{{ $eq->title }}">
        @else
            <i class="bi {{ $eq->category->icon ?? 'bi-sun' }}"></i>
        @endif
    </div>
    <div class="card-body d-flex flex-column">
        <span class="small text-muted"><i class="bi bi-geo-alt"></i> {{ $eq->city }} · {{ $eq->category->name }}</span>
        <h5 class="card-title mt-1">{{ $eq->title }}</h5>
        <div class="small text-muted">
            {{ $eq->type_label }}
            @if($eq->power_watts) · {{ $eq->power_watts }} W @endif
            @if($eq->capacity_wh) · {{ $eq->capacity_wh }} Wh @endif
        </div>
        <div class="mt-auto pt-3 d-flex justify-content-between align-items-center">
            <span class="price-tag">{{ number_format($eq->price_per_day, 2) }} DT<small class="text-muted fw-normal">/jour</small></span>
            <a href="{{ route('front.equipment.show', $eq) }}" class="btn btn-sm btn-outline-sun">Détails</a>
        </div>
    </div>
</div>
