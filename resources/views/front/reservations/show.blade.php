@extends('layouts.front')
@section('title', 'Réservation #'.$reservation->id)
@section('content')
<a href="{{ route('front.reservations.index') }}" class="small"><i class="bi bi-arrow-left"></i> Mes réservations</a>
<div class="row g-4 mt-1">
    <div class="col-lg-7">
        <div class="card form-card p-4 mb-4">
            <div class="d-flex justify-content-between">
                <h1 class="h4">Réservation #{{ $reservation->id }}</h1>
                <x-badge :status="$reservation->status" :labels="\App\Models\Reservation::STATUSES" />
            </div>
            <p class="mb-1"><strong>{{ $reservation->equipment->title }}</strong> — propriétaire : {{ $reservation->equipment->owner->name }}</p>
            <p class="mb-1"><i class="bi bi-calendar-range"></i> {{ $reservation->start_date->format('d/m/Y') }} → {{ $reservation->end_date->format('d/m/Y') }} ({{ $reservation->days }} jour(s))</p>
            <p class="mb-1">Total : <span class="price-tag">{{ number_format($reservation->total_price, 2) }} DT</span> · Caution : {{ number_format($reservation->equipment->deposit, 2) }} DT</p>
            @if($reservation->notes)<p class="text-muted mb-0">Remarques : {{ $reservation->notes }}</p>@endif

            <div class="d-flex flex-wrap gap-2 mt-3">
                @if(in_array($reservation->status, ['pending', 'confirmed']))
                    <form method="POST" action="{{ route('front.reservations.cancel', $reservation) }}" onsubmit="return confirm('Annuler cette réservation ?')">@csrf
                        <button class="btn btn-outline-danger btn-sm">Annuler</button></form>
                @endif
                <a href="{{ route('front.incidents.create', ['equipment' => $reservation->equipment_id]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-exclamation-octagon"></i> Signaler un incident</a>
                @if($reservation->status === 'completed' && ! $reservation->review)
                    <a href="{{ route('front.reviews.create', $reservation) }}" class="btn btn-sun btn-sm"><i class="bi bi-star"></i> Donner mon avis</a>
                @endif
            </div>
        </div>

        @if($reservation->review)
            <div class="card form-card p-4">
                <h2 class="h6">Mon avis</h2>
                <x-stars :rating="$reservation->review->rating" />
                <x-badge :status="$reservation->review->sentiment" :labels="\App\Models\Review::SENTIMENTS" />
                @unless($reservation->review->is_visible)<span class="badge text-bg-warning">En modération</span>@endunless
                <p class="mb-0 mt-2">{{ $reservation->review->comment }}</p>
            </div>
        @endif
    </div>

    <div class="col-lg-5">
        <div class="card form-card p-4">
            <h2 class="h6">Paiement</h2>
            @forelse($reservation->payments as $p)
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span>{{ \App\Models\Payment::METHODS[$p->method] }}<br><small class="text-muted">{{ $p->reference }}</small></span>
                    <span class="text-end">{{ number_format($p->amount, 2) }} DT<br><x-badge :status="$p->status" :labels="\App\Models\Payment::STATUSES" /></span>
                </div>
            @empty
                <p class="text-muted small">Aucun paiement enregistré.</p>
            @endforelse

            @if($reservation->status === 'pending')
                <form method="POST" action="{{ route('front.reservations.pay', $reservation) }}" class="mt-3">
                    @csrf
                    <x-form.select name="method" label="Mode de paiement" :options="\App\Models\Payment::METHODS" required />
                    <button class="btn btn-sun w-100">Payer {{ number_format($reservation->total_price, 2) }} DT</button>
                    <small class="text-muted d-block mt-2">Paiement simulé à des fins pédagogiques.</small>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
