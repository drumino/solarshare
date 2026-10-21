@extends('layouts.front')
@section('title', 'Donner mon avis')
@section('content')
<div class="row justify-content-center"><div class="col-lg-6">
    <div class="card form-card p-4">
        <h1 class="h4">Votre avis sur « {{ $reservation->equipment->title }} »</h1>
        <p class="text-muted small">Location du {{ $reservation->start_date->format('d/m/Y') }} au {{ $reservation->end_date->format('d/m/Y') }}. Votre commentaire est analysé automatiquement (sentiment et modération).</p>
        @include('front.reviews._form', [
            'action' => route('front.reviews.store', $reservation),
            'buttonLabel' => 'Publier mon avis',
            'cancelUrl' => route('front.home'),
        ])
    </div>
</div></div>
@endsection
