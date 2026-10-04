@extends('layouts.front')
@section('title', 'Accueil')

@section('hero')
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <h1 class="display-5">Louez et partagez l'énergie solaire entre voisins.</h1>
                <p class="lead opacity-75">Panneaux portables, batteries, mini-éoliennes : accédez à l'énergie renouvelable sans acheter, ou rentabilisez votre matériel inutilisé.</p>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('front.equipment.index') }}" class="btn btn-sun btn-lg">Explorer le catalogue</a>
                    <a href="{{ route('front.advisor') }}" class="btn btn-outline-light btn-lg"><i class="bi bi-stars"></i> Calculer mes besoins</a>
                </div>
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
<h2 class="section-title h4 mb-3">Catégories</h2>
<div class="row g-3 mb-5">
    @foreach($categories as $cat)
        <div class="col-6 col-md-3">
            <a href="{{ route('front.equipment.index', ['category' => $cat->id]) }}" class="text-decoration-none text-dark">
                <div class="card cat-card text-center p-3 h-100">
                    <i class="bi {{ $cat->icon }}"></i>
                    <div class="fw-semibold mt-2">{{ $cat->name }}</div>
                    <small class="text-muted">{{ $cat->equipment_count }} disponible(s)</small>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="d-flex justify-content-between align-items-end mb-3">
    <h2 class="section-title h4 mb-0">Derniers équipements</h2>
    <a href="{{ route('front.equipment.index') }}">Tout voir <i class="bi bi-arrow-right"></i></a>
</div>
<div class="row g-4 mb-5">
    @forelse($featured as $eq)
        <div class="col-md-6 col-lg-4">@include('partials.equipment-card', ['eq' => $eq])</div>
    @empty
        <p class="text-muted">Aucun équipement disponible pour le moment.</p>
    @endforelse
</div>

<h2 class="section-title h4 mb-3">Comment ça marche ?</h2>
<div class="row g-4 mb-5 text-center">
    @foreach([['1','Trouvez','Filtrez par type, ville et prix.'],['2','Réservez','Choisissez vos dates et payez en ligne.'],['3','Utilisez','Récupérez le matériel et profitez de l\'énergie.'],['4','Évaluez','Laissez un avis et signalez tout incident.']] as [$n,$t,$d])
        <div class="col-6 col-md-3"><span class="step-circle">{{ $n }}</span><h3 class="h6 mt-2">{{ $t }}</h3><p class="small text-muted">{{ $d }}</p></div>
    @endforeach
</div>

<div class="ai-banner p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <h2 class="h5 mb-1"><i class="bi bi-stars"></i> Assistant énergie intelligent</h2>
        <p class="mb-0 text-muted">Listez vos appareils : l'IA calcule vos besoins et recommande le matériel disponible.</p>
    </div>
    <a href="{{ route('front.advisor') }}" class="btn btn-sun">Essayer</a>
</div>
@endsection
