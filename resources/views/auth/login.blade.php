@extends('layouts.front')
@section('title', 'Connexion')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card form-card p-4">
            <h1 class="h4 mb-3"><i class="bi bi-box-arrow-in-right text-warning"></i> Connexion</h1>
            <form method="POST" action="{{ route('login.attempt') }}" novalidate>
                @csrf
                <x-form.input name="email" label="E-mail" type="email" required autofocus />
                <x-form.input name="password" label="Mot de passe" type="password" required />
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">Se souvenir de moi</label>
                </div>
                <button class="btn btn-sun w-100">Se connecter</button>
            </form>
            <p class="text-center mt-3 mb-0 small">Pas encore de compte ? <a href="{{ route('register') }}">Inscrivez-vous</a></p>
        </div>
    </div>
</div>
@endsection
