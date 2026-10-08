@extends('layouts.admin')
@section('title', $user->exists ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur')
@section('heading', $user->exists ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur')
@section('content')
<div class="card card-ad p-4" style="max-width:640px">
    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" novalidate>
        @csrf @if($user->exists) @method('PUT') @endif
        <x-form.input name="name" label="Nom complet" :value="$user->name" required />
        <x-form.input name="email" label="E-mail" type="email" :value="$user->email" required />
        <div class="row">
            <div class="col-md-6"><x-form.input name="phone" label="Téléphone" :value="$user->phone" placeholder="+216 20 123 456" /></div>
            <div class="col-md-6"><x-form.input name="city" label="Ville" :value="$user->city" /></div>
        </div>
        <x-form.select name="role" label="Rôle" :options="\App\Models\User::ROLES" :selected="$user->role" required />
        <x-form.input name="password" label="Mot de passe" type="password" :required="! $user->exists"
            :help="$user->exists ? 'Laisser vide pour conserver le mot de passe actuel.' : '8 caractères minimum, avec lettres et chiffres.'" />
        <x-form.input name="password_confirmation" label="Confirmer le mot de passe" type="password" :required="! $user->exists" />
        <button class="btn btn-warning">Enregistrer</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>
@endsection
