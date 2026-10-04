@extends('layouts.admin')
@section('title', $category->exists ? 'Modifier la catégorie' : 'Nouvelle catégorie')
@section('heading', $category->exists ? 'Modifier la catégorie' : 'Nouvelle catégorie')
@section('content')
<div class="card card-ad p-4" style="max-width:640px">
    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" novalidate>
        @csrf @if($category->exists) @method('PUT') @endif
        <x-form.input name="name" label="Nom" :value="$category->name" required />
        <x-form.input name="icon" label="Icône Bootstrap Icons" :value="$category->icon" required help="Ex. : bi-sun, bi-battery-charging, bi-wind (voir icons.getbootstrap.com)." />
        <x-form.textarea name="description" label="Description" :value="$category->description" rows="3" />
        <button class="btn btn-warning">Enregistrer</button>
        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>
@endsection
