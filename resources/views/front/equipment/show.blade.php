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
                <p>{{ $equipment->description }}</p>
                <div class="row g-2 text-center">
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><small class="text-muted d-block">Type</small>{{ $equipment->type_label }}</div></div>
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><small class="text-muted d-block">Puissance</small>{{ $equipment->power_watts ? $equipment->power_watts.' W' : '—' }}</div></div>
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><small class="text-muted d-block">Capacité</small>{{ $equipment->capacity_wh ? $equipment->capacity_wh.' Wh' : '—' }}</div></div>
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><small class="text-muted d-block">État</small>{{ \App\Models\Equipment::CONDITIONS[$equipment->condition] ?? $equipment->condition }}</div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card form-card p-4 position-sticky" style="top:90px">
            <div class="price-tag fs-3">{{ number_format($equipment->price_per_day, 2) }} DT<small class="fs-6 text-muted fw-normal"> / jour</small></div>
            <div class="small text-muted mb-3">Caution : {{ number_format($equipment->deposit, 2) }} DT</div>

            <h2 class="h6 mt-3"><i class="bi bi-calendar-range"></i> Disponibilité</h2>
            @if($equipment->maintenanceRecords->isEmpty())
                <p class="small text-success"><i class="bi bi-check-circle"></i> Aucune indisponibilité programmée.</p>
            @else
                <ul class="small ps-3 mb-3">
                    @foreach($equipment->maintenanceRecords as $maintenance)
                        <li><span class="fw-semibold">Indisponible</span> du {{ $maintenance->starts_on->format('d/m/Y') }} au {{ $maintenance->ends_on->format('d/m/Y') }}<br><span class="text-muted">{{ $maintenance->reason_label }}</span></li>
                    @endforeach
                </ul>
            @endif

            <h2 class="h6 mt-2"><i class="bi bi-person-circle"></i> Propriétaire</h2>
            <p class="mb-1 fw-semibold">{{ $equipment->owner->name }}</p>
            @if($equipment->owner->city)<p class="mb-1 small text-muted"><i class="bi bi-geo-alt"></i> {{ $equipment->owner->city }}</p>@endif
            @auth
                @if($equipment->owner->phone)<p class="mb-1 small"><i class="bi bi-telephone"></i> {{ $equipment->owner->phone }}</p>@endif
                <p class="mb-0 small"><i class="bi bi-envelope"></i> <a href="mailto:{{ $equipment->owner->email }}">{{ $equipment->owner->email }}</a></p>
            @else
                <a href="{{ route('login') }}" class="btn btn-sun w-100 mt-2">Connectez-vous pour contacter le propriétaire</a>
            @endauth
        </div>
    </div>
</div>
@endsection
