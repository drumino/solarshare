@extends('layouts.admin')
@section('title', 'Signalement #'.$report->id)
@section('heading', 'Signalement #'.$report->id)
@section('content')
<div class="card card-ad p-4 mb-3">
    <h5>Avis signalé</h5>
    <p class="mb-1"><strong>Note :</strong> {{ $report->review?->rating }}/5</p>
    <p class="mb-1"><strong>Auteur :</strong> {{ $report->review?->user?->name ?? 'Utilisateur supprimé' }}</p>
    <p class="mb-1"><strong>Sentiment :</strong> {{ \App\Models\Review::SENTIMENTS[$report->review?->sentiment] ?? $report->review?->sentiment }}</p>
    <p class="mb-1"><strong>Visible :</strong> {{ ($report->review?->is_visible ?? false) ? 'Oui' : 'Non' }}</p>
    <p class="mb-1"><strong>Commentaire :</strong> {{ $report->review?->comment }}</p>
</div>
<div class="card card-ad p-4 mb-3">
    <h5>Signalement</h5>
    <p class="mb-1"><strong>Signalé par :</strong> {{ $report->reporter?->name ?? 'Utilisateur supprimé' }}</p>
    <p class="mb-1"><strong>Motif :</strong> {{ \App\Models\ReviewReport::REASONS[$report->reason] ?? $report->reason }}</p>
    @if($report->details)<p class="mb-1"><strong>Détails :</strong> {{ $report->details }}</p>@endif
    <p class="mb-1"><strong>Catégorie IA :</strong> {{ $report->category }}</p>
    <p class="mb-1"><strong>Sévérité :</strong>
        @php $colors=['low'=>'success','medium'=>'warning','high'=>'danger']; @endphp
        <span class="badge text-bg-{{ $colors[$report->severity] ?? 'secondary' }}">{{ \App\Models\ReviewReport::SEVERITIES[$report->severity] ?? $report->severity }}</span>
    </p>
    <p class="mb-1"><strong>Action suggérée :</strong> {{ $report->suggested_action }}</p>
</div>
<div class="d-flex gap-2">
    <form method="POST" action="{{ route('admin.review-reports.traiter', $report) }}" class="card card-ad p-3">
        @csrf
        <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="hide_review" value="1" id="hide"><label class="form-check-label" for="hide">Masquer l'avis</label></div>
        <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="show_review" value="1" id="show"><label class="form-check-label" for="show">Conserver (réafficher) l'avis</label></div>
        <button class="btn btn-success">Traiter</button>
    </form>
    <form method="POST" action="{{ route('admin.review-reports.rejeter', $report) }}">
        @csrf
        <button class="btn btn-danger">Rejeter</button>
    </form>
</div>
<a href="{{ route('admin.review-reports.index') }}" class="btn btn-outline-secondary mt-3">Retour</a>
@endsection
