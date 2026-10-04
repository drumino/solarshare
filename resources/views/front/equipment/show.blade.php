@extends('layouts.front')
@section('title', $equipment->title)
@section('content')
<nav aria-label="breadcrumb"><ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('front.equipment.index') }}">Catalogue</a></li>
    <li class="breadcrumb-item active">{{ $equipment->title }}</li>
</ol></nav>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card form-card overflow-hidden mb-4">
            <div class="eq-thumb" style="height:280px">
                @if($equipment->image_url)<img src="{{ $equipment->image_url }}" alt="{{ $equipment->title }}">@else<i class="bi {{ $equipment->category->icon }}"></i>@endif
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between flex-wrap">
                    <h1 class="h3">{{ $equipment->title }}</h1>
                    <x-badge :status="$equipment->status" :labels="\App\Models\Equipment::STATUSES" />
                </div>
                <p class="text-muted mb-2">
                    <i class="bi bi-geo-alt"></i> {{ $equipment->city }} · {{ $equipment->category->name }} · propriétaire : {{ $equipment->owner->name }}
                </p>
                @if($equipment->average_rating)
                    <p><x-stars :rating="$equipment->average_rating" /> <strong>{{ $equipment->average_rating }}</strong>/5 ({{ $reviews->count() }} avis)</p>
                @endif
                <p>{{ $equipment->description }}</p>
                <div class="row g-2 text-center">
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><small class="text-muted d-block">Type</small>{{ $equipment->type_label }}</div></div>
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><small class="text-muted d-block">Puissance</small>{{ $equipment->power_watts ? $equipment->power_watts.' W' : '—' }}</div></div>
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><small class="text-muted d-block">Capacité</small>{{ $equipment->capacity_wh ? $equipment->capacity_wh.' Wh' : '—' }}</div></div>
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><small class="text-muted d-block">État</small>{{ \App\Models\Equipment::CONDITIONS[$equipment->condition] ?? $equipment->condition }}</div></div>
                </div>
                @auth
                    <a href="{{ route('front.incidents.create', ['equipment' => $equipment->id]) }}" class="btn btn-sm btn-outline-danger mt-3"><i class="bi bi-exclamation-octagon"></i> Signaler un incident</a>
                @endauth
            </div>
        </div>

        <h2 class="h5 section-title">Avis ({{ $reviews->count() }})</h2>
        @forelse($reviews as $review)
            <div class="card form-card p-3 mb-3">
                <div class="d-flex justify-content-between">
                    <div><strong>{{ $review->user->name }}</strong> <x-stars :rating="$review->rating" />
                        <x-badge :status="$review->sentiment" :labels="\App\Models\Review::SENTIMENTS" />
                        <span class="ai-chip">analyse IA</span></div>
                    <small class="text-muted">{{ $review->created_at->format('d/m/Y') }}</small>
                </div>
                <p class="mb-2 mt-2">{{ $review->comment }}</p>
                @auth
                    @if($review->user_id !== auth()->id())
                        <a class="small text-danger" data-bs-toggle="collapse" href="#report-{{ $review->id }}"><i class="bi bi-flag"></i> Signaler</a>
                        <div class="collapse mt-2" id="report-{{ $review->id }}">
                            <form method="POST" action="{{ route('front.reviews.report', $review) }}" class="row g-2">
                                @csrf
                                <div class="col-md-4">
                                    <select name="reason" class="form-select form-select-sm" required>
                                        @foreach(\App\Models\ReviewReport::REASONS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-md-6"><input type="text" name="details" class="form-control form-control-sm" placeholder="Détails (obligatoire pour « Autre »)"></div>
                                <div class="col-md-2"><button class="btn btn-sm btn-danger w-100">Envoyer</button></div>
                            </form>
                        </div>
                    @endif
                @endauth
            </div>
        @empty
            <p class="text-muted">Aucun avis pour le moment.</p>
        @endforelse
    </div>

    <div class="col-lg-4">
        <div class="card form-card p-4 position-sticky" style="top:90px">
            <div class="price-tag fs-3">{{ number_format($equipment->price_per_day, 2) }} DT<small class="fs-6 text-muted fw-normal"> / jour</small></div>
            <div class="small text-muted mb-3">Caution : {{ number_format($equipment->deposit, 2) }} DT</div>

            @if($equipment->status !== 'available')
                <div class="alert alert-warning">Cet équipement n'est pas réservable actuellement.</div>
            @elseif(! auth()->check())
                <a href="{{ route('login') }}" class="btn btn-sun w-100">Connectez-vous pour réserver</a>
            @elseif($equipment->owner_id === auth()->id())
                <div class="alert alert-info mb-0">Vous êtes le propriétaire de cet équipement.</div>
            @else
                <form id="booking-form" method="POST" action="{{ route('front.reservations.store') }}" data-price="{{ $equipment->price_per_day }}" novalidate>
                    @csrf
                    <input type="hidden" name="equipment_id" value="{{ $equipment->id }}">
                    <x-form.input name="start_date" label="Du" type="date" required :min="today()->toDateString()" />
                    <x-form.input name="end_date" label="Au" type="date" required :min="today()->toDateString()" />
                    <x-form.textarea name="notes" label="Remarques" rows="2" />
                    <div class="bg-light rounded p-2 mb-3 small">Total : <strong id="booking-total">—</strong></div>
                    <button class="btn btn-sun w-100">Réserver</button>
                </form>
            @endif

            @if($booked->isNotEmpty())
                <hr>
                <div class="small"><strong>Périodes déjà réservées :</strong>
                    <ul class="mb-0">
                        @foreach($booked as $b)<li>{{ $b->start_date->format('d/m/Y') }} → {{ $b->end_date->format('d/m/Y') }}</li>@endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/booking.js') }}"></script>
@endpush
