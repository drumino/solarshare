@extends('layouts.admin')
@section('title', $incident->exists ? 'Modifier l\'incident' : 'Nouvel incident')
@section('heading', $incident->exists ? 'Modifier l\'incident' : 'Nouvel incident')
@section('content')
<div class="card card-ad p-4" style="max-width:780px">
    <form id="incident-form" method="POST" novalidate data-triage-url="{{ route('front.incidents.triage') }}"
          action="{{ $incident->exists ? route('admin.incidents.update', $incident) : route('admin.incidents.store') }}">
        @csrf @if($incident->exists) @method('PUT') @endif
        <div class="row">
            <div class="col-md-6"><x-form.select name="equipment_id" label="Équipement" :options="$equipmentList->pluck('title', 'id')" :selected="$incident->equipment_id" placeholder="— Choisir —" required /></div>
            <div class="col-md-6"><x-form.select name="reporter_id" label="Déclarant" :options="$users->pluck('name', 'id')" :selected="$incident->reporter_id" placeholder="— Choisir —" required /></div>
        </div>
        <x-form.select name="reservation_id" label="Réservation liée (optionnel)" placeholder="— Aucune —" :selected="$incident->reservation_id"
            :options="$reservations->mapWithKeys(fn ($r) => [$r->id => '#'.$r->id.' · '.$r->equipment->title])" />
        <x-form.input name="title" label="Titre" :value="$incident->title" required />
        <x-form.textarea name="description" label="Description" :value="$incident->description" rows="4" required />

        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-semibold">Classification</span>
            <button type="button" id="btn-ai-triage" class="btn btn-sm btn-outline-secondary"><i class="bi bi-stars"></i> Triage IA</button>
        </div>
        <div id="ai-triage-box" class="alert alert-light border d-none"></div>
        <div class="row">
            <div class="col-md-4"><x-form.select name="category" label="Catégorie" :options="\App\Models\Incident::CATEGORIES" :selected="$incident->category" required /></div>
            <div class="col-md-4"><x-form.select name="severity" label="Gravité" :options="\App\Models\Incident::SEVERITIES" :selected="$incident->severity" required /></div>
            <div class="col-md-4"><x-form.select name="status" label="Statut" :options="\App\Models\Incident::STATUSES" :selected="$incident->status" required /></div>
        </div>
        @if($incident->ai_advice)
            <div class="alert alert-light border small"><span class="ai-chip">IA</span> {{ $incident->ai_summary }}<br>Action recommandée : {{ $incident->ai_advice }}</div>
        @endif
        <button class="btn btn-warning">Enregistrer</button>
        <a href="{{ route('admin.incidents.index') }}" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>
@endsection
@push('scripts')<script src="{{ asset('js/incident-form.js') }}"></script>@endpush
