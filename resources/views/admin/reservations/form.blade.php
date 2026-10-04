@extends('layouts.admin')
@section('title', $reservation->exists ? 'Modifier la réservation' : 'Nouvelle réservation')
@section('heading', $reservation->exists ? 'Modifier la réservation #'.$reservation->id : 'Nouvelle réservation')
@section('content')
<div class="card card-ad p-4" style="max-width:720px">
    <form method="POST" novalidate action="{{ $reservation->exists ? route('admin.reservations.update', $reservation) : route('admin.reservations.store') }}">
        @csrf @if($reservation->exists) @method('PUT') @endif
        <div class="row">
            <div class="col-md-6"><x-form.select name="equipment_id" label="Équipement" :options="$equipmentList->pluck('title', 'id')" :selected="$reservation->equipment_id" placeholder="— Choisir —" required /></div>
            <div class="col-md-6"><x-form.select name="user_id" label="Locataire" :options="$users->pluck('name', 'id')" :selected="$reservation->user_id" placeholder="— Choisir —" required /></div>
        </div>
        <div class="row">
            <div class="col-md-4"><x-form.input name="start_date" label="Début" type="date" :value="$reservation->start_date?->format('Y-m-d')" required /></div>
            <div class="col-md-4"><x-form.input name="end_date" label="Fin" type="date" :value="$reservation->end_date?->format('Y-m-d')" required /></div>
            <div class="col-md-4"><x-form.select name="status" label="Statut" :options="\App\Models\Reservation::STATUSES" :selected="$reservation->status" required /></div>
        </div>
        <x-form.textarea name="notes" label="Remarques" :value="$reservation->notes" rows="2" />
        <p class="text-muted small">Le prix total est calculé automatiquement (jours × prix journalier).</p>
        <button class="btn btn-warning">Enregistrer</button>
        <a href="{{ route('admin.reservations.index') }}" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>
@endsection
