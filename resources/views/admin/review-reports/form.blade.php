@extends('layouts.admin')
@section('title', $report->exists ? 'Traiter le signalement' : 'Nouveau signalement')
@section('heading', $report->exists ? 'Traiter le signalement' : 'Nouveau signalement')
@section('content')
<div class="card card-ad p-4" style="max-width:720px">
    <form method="POST" novalidate action="{{ $report->exists ? route('admin.review-reports.update', $report) : route('admin.review-reports.store') }}">
        @csrf @if($report->exists) @method('PUT') @endif
        <x-form.select name="review_id" label="Avis concerné" required placeholder="— Choisir —" :selected="$report->review_id"
            :options="$reviews->mapWithKeys(fn ($r) => [$r->id => '#'.$r->id.' · '.$r->user->name.' : '.\Illuminate\Support\Str::limit($r->comment, 50)])" />
        <x-form.select name="user_id" label="Signalé par" :options="$users->pluck('name', 'id')" :selected="$report->user_id" placeholder="— Choisir —" required />
        <div class="row">
            <div class="col-md-6"><x-form.select name="reason" label="Motif" :options="\App\Models\ReviewReport::REASONS" :selected="$report->reason" placeholder="— Choisir —" required /></div>
            <div class="col-md-6"><x-form.select name="status" label="Statut" :options="\App\Models\ReviewReport::STATUSES" :selected="$report->status" required /></div>
        </div>
        <x-form.textarea name="details" label="Détails" :value="$report->details" rows="3" help="Obligatoire si le motif est « Autre »." />
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="hide_review" value="1" id="hide_review">
            <label class="form-check-label" for="hide_review">Masquer l'avis du site (appliqué si le statut est « Traité »)</label>
        </div>
        <button class="btn btn-warning">Enregistrer</button>
        <a href="{{ route('admin.review-reports.index') }}" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>
@endsection
