@extends('layouts.front')
@section('title', 'Mon profil')
@section('content')
<div class="row justify-content-center g-4">
    <div class="col-md-6">
        <div class="card form-card p-4">
            <h1 class="h4 mb-3"><i class="bi bi-person-gear text-warning"></i> Mon profil</h1>
            <form method="POST" action="{{ route('front.profile.update') }}" novalidate>
                @csrf @method('PUT')
                <x-form.input name="name" label="Nom complet" :value="$user->name" required />
                <x-form.input name="email" label="E-mail" type="email" :value="$user->email" required />
                <div class="row">
                    <div class="col-md-6"><x-form.input name="phone" label="Téléphone" :value="$user->phone" placeholder="+216 20 123 456" /></div>
                    <div class="col-md-6"><x-form.input name="city" label="Ville" :value="$user->city" /></div>
                </div>
                <p class="small text-muted">Rôle : <x-badge :status="$user->role" :labels="\App\Models\User::ROLES" /> · Membre depuis le {{ $user->created_at->format('d/m/Y') }}</p>
                <button class="btn btn-sun w-100">Enregistrer</button>
            </form>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card form-card p-4">
            <h2 class="h5 mb-3"><i class="bi bi-shield-lock text-warning"></i> Changer le mot de passe</h2>
            <form method="POST" action="{{ route('front.profile.password') }}" novalidate>
                @csrf @method('PUT')
                <x-form.input name="current_password" label="Mot de passe actuel" type="password" required />
                <x-form.input name="password" label="Nouveau mot de passe" type="password" required help="8 caractères minimum, avec lettres et chiffres." />
                <x-form.input name="password_confirmation" label="Confirmer le mot de passe" type="password" required />
                <button class="btn btn-outline-secondary w-100">Modifier le mot de passe</button>
            </form>
        </div>
    </div>
</div>
@endsection
