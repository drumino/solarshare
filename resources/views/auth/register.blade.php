@extends('layouts.front')
@section('title', 'Inscription')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card form-card p-4">
            <h1 class="h4 mb-3"><i class="bi bi-person-plus text-warning"></i> Créer un compte</h1>
            <form method="POST" action="{{ route('register.store') }}" novalidate>
                @csrf
                <x-form.input name="name" label="Nom complet" required />
                <x-form.input name="email" label="E-mail" type="email" required />
                <div class="row">
                    <div class="col-md-6"><x-form.input name="phone" label="Téléphone" placeholder="+216 20 123 456" /></div>
                    <div class="col-md-6"><x-form.input name="city" label="Ville" /></div>
                </div>
                <x-form.input name="password" label="Mot de passe" type="password" required help="8 caractères minimum, avec lettres et chiffres." />
                <x-form.input name="password_confirmation" label="Confirmer le mot de passe" type="password" required />
                <button class="btn btn-sun w-100">S'inscrire</button>
            </form>
        </div>
    </div>
</div>
@endsection
