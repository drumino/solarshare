@extends('layouts.front')
@section('title', 'Accueil')
@section('hero')
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <h1 class="display-5">Louez et partagez l'énergie solaire entre voisins.</h1>
                <p class="lead opacity-75">Panneaux portables, batteries, mini-éoliennes : accédez à l'énergie renouvelable sans acheter, ou rentabilisez votre matériel inutilisé.</p>
            </div>
            <div class="col-lg-5">
                <div class="row g-3 text-center">
                    <div class="col-4"><div class="stat"><strong>{{ $stats['equipment'] }}</strong>équipements</div></div>
                    <div class="col-4"><div class="stat"><strong>{{ $stats['rentals'] }}</strong>locations</div></div>
                    <div class="col-4"><div class="stat"><strong>{{ $stats['cities'] }}</strong>villes</div></div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h2 class="section-title h4 mb-3">Derniers avis</h2>
        <div class="row g-3">
            @forelse($latestReviews as $r)
                <div class="col-12">
                    <div class="card review-card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-semibold">{{ $r->equipment->title }}</div>
                                <div class="small text-muted">{{ $r->user->name }} · {{ $r->created_at->format('d/m/Y') }}</div>
                            </div>
                            <div class="text-end"><x-stars :rating="$r->rating" /></div>
                        </div>
                        <p class="mt-2 mb-0 small">{{ \Illuminate\Support\Str::limit($r->comment, 180) }}</p>
                    </div>
                </div>
            @empty
                <div class="col-12"><p class="text-muted">Aucun avis pour le moment. Soyez le premier à en publier un !</p></div>
            @endforelse
        </div>
    </div>
</div>
@endsection