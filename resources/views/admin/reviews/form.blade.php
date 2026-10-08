@extends('layouts.admin')
@section('title', $review->exists ? 'Modifier l\'avis' : 'Nouvel avis')
@section('heading', $review->exists ? 'Modifier l\'avis' : 'Nouvel avis')
@section('content')
<div class="card card-ad p-4" style="max-width:720px">
    <form method="POST" novalidate action="{{ $review->exists ? route('admin.reviews.update', $review) : route('admin.reviews.store') }}">
        @csrf @if($review->exists) @method('PUT') @endif
        <x-form.select name="reservation_id" label="Réservation concernée" required placeholder="— Choisir —" :selected="$review->reservation_id"
            :options="$reservations->mapWithKeys(fn ($r) => [$r->id => '#'.$r->id.' · '.$r->equipment->title.' · '.$r->user->name])"
            help="L'équipement et l'auteur sont déduits de la réservation." />
        <x-form.select name="rating" label="Note" :options="[5 => '5', 4 => '4', 3 => '3', 2 => '2', 1 => '1']" :selected="$review->rating" required />
        <x-form.textarea name="comment" label="Commentaire" :value="$review->comment" rows="4" required />
        <div class="row">
            <div class="col-md-4"><x-form.select name="is_visible" label="Visibilité" :options="[1 => 'Visible', 0 => 'Masqué']" :selected="$review->is_visible ? 1 : 0" required /></div>
            <div class="col-md-8"><x-form.input name="moderation_note" label="Note de modération" :value="$review->moderation_note" /></div>
        </div>
        @if($review->exists)
            <p class="small text-muted">Analyse IA actuelle : <x-badge :status="$review->sentiment" :labels="\App\Models\Review::SENTIMENTS" /> (score {{ number_format($review->sentiment_score, 2) }}). Elle est recalculée si la note ou le commentaire change.</p>
        @endif
        <button class="btn btn-warning">Enregistrer</button>
        <a href="{{ route('admin.reviews.index') }}" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>
@endsection
