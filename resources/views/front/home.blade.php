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
                    @auth
                        <a href="{{ route('front.profile.edit') }}" class="btn btn-sun btn-lg"><i class="bi bi-person-gear"></i> Mon profil</a>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-sun btn-lg">Créer un compte</a>
                        <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg">Se connecter</a>
                    @endauth
                </div>
            </div>
            <div class="col-lg-5">
                <div class="row g-3 text-center">
                    <div class="col-6"><div class="stat"><strong>{{ $stats['members'] }}</strong>membres</div></div>
                    <div class="col-6"><div class="stat"><strong>{{ $stats['cities'] }}</strong>villes</div></div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('content')
<h2 class="section-title h4 mb-3">Comment ça marche ?</h2>
<div class="row g-4 mb-5 text-center">
    @foreach([['1','Inscrivez-vous','Créez votre compte en quelques secondes.'],['2','Complétez','Renseignez votre ville et votre téléphone.'],['3','Gérez','Mettez à jour votre profil et votre mot de passe.']] as [$n,$t,$d])
        <div class="col-md-4"><span class="step-circle">{{ $n }}</span><h3 class="h6 mt-2">{{ $t }}</h3><p class="small text-muted">{{ $d }}</p></div>
    @endforeach
</div>
@endsection
