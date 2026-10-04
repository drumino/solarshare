@extends('layouts.front')
@section('title', 'Signaler un incident')
@section('content')
<div class="row justify-content-center"><div class="col-lg-7">
    <div class="card form-card p-4">
        <h1 class="h4">Signaler un incident</h1>
        <p class="text-muted small">Décrivez le problème : l'IA peut proposer automatiquement la catégorie et la gravité.</p>
        <form id="incident-form" method="POST" action="{{ route('front.incidents.store') }}" data-triage-url="{{ route('front.incidents.triage') }}" novalidate>
            @csrf
            <x-form.select name="equipment_id" label="Équipement concerné" :options="$equipmentList->pluck('title', 'id')" :selected="$selected" placeholder="— Choisir —" required />
            <x-form.input name="title" label="Titre" required />
            <x-form.textarea name="description" label="Description détaillée" rows="5" required help="20 caractères minimum." />

            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-semibold">Classification</span>
                <button type="button" id="btn-ai-triage" class="btn btn-sm btn-outline-secondary"><i class="bi bi-stars"></i> Analyser avec l'IA</button>
            </div>
            <div id="ai-triage-box" class="alert alert-light border d-none"></div>
            <div class="row">
                <div class="col-md-6"><x-form.select name="category" label="Catégorie" :options="\App\Models\Incident::CATEGORIES" placeholder="Auto (IA)" /></div>
                <div class="col-md-6"><x-form.select name="severity" label="Gravité" :options="\App\Models\Incident::SEVERITIES" placeholder="Auto (IA)" /></div>
            </div>
            <button class="btn btn-sun">Envoyer</button>
            <a href="{{ route('front.incidents.index') }}" class="btn btn-outline-secondary">Annuler</a>
        </form>
    </div>
</div></div>
@endsection
@push('scripts')<script src="{{ asset('js/incident-form.js') }}"></script>@endpush
