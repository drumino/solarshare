@extends('layouts.front')
@section('title', 'Donner mon avis')
@section('content')
<div class="row justify-content-center"><div class="col-lg-6">
    <div class="card form-card p-4">
        <h1 class="h4">Votre avis sur « {{ $reservation->equipment->title }} »</h1>
        <p class="text-muted small">Location du {{ $reservation->start_date->format('d/m/Y') }} au {{ $reservation->end_date->format('d/m/Y') }}. Votre commentaire est analysé automatiquement (sentiment et modération).</p>
        <form method="POST" action="{{ route('front.reviews.store', $reservation) }}" novalidate>
            @csrf
            <x-form.select name="rating" label="Note" :options="[5 => '5 — Excellent', 4 => '4 — Très bien', 3 => '3 — Correct', 2 => '2 — Décevant', 1 => '1 — Mauvais']" :selected="5" required />
            <x-form.textarea name="comment" label="Commentaire" rows="5" required help="10 caractères minimum." />
            <button class="btn btn-sun">Publier mon avis</button>
            <a href="{{ route('front.reservations.show', $reservation) }}" class="btn btn-outline-secondary">Annuler</a>
        </form>
    </div>
</div></div>
@endsection
