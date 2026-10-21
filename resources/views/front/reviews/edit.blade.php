@extends('layouts.front')
@section('title', 'Modifier mon avis')
@section('content')
<div class="row justify-content-center"><div class="col-lg-6">
    <div class="card form-card p-4">
        <h1 class="h4">Modifier votre avis sur « {{ $review->equipment->title }} »</h1>
        <p class="text-muted small">Vous pouvez modifier votre avis dans les 24h suivant sa publication.</p>
        @include('front.reviews._form', [
            'review' => $review,
            'action' => route('front.reviews.update', $review),
            'buttonLabel' => 'Enregistrer les modifications',
            'cancelUrl' => route('front.home'),
        ])
    </div>
</div></div>
@endsection
