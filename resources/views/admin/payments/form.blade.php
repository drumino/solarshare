@extends('layouts.admin')
@section('title', $payment->exists ? 'Modifier le paiement' : 'Nouveau paiement')
@section('heading', $payment->exists ? 'Modifier le paiement' : 'Nouveau paiement')
@section('content')
<div class="card card-ad p-4" style="max-width:720px">
    <form method="POST" novalidate action="{{ $payment->exists ? route('admin.payments.update', $payment) : route('admin.payments.store') }}">
        @csrf @if($payment->exists) @method('PUT') @endif
        <x-form.select name="reservation_id" label="Réservation" required placeholder="— Choisir —" :selected="$payment->reservation_id"
            :options="$reservations->mapWithKeys(fn ($r) => [$r->id => '#'.$r->id.' · '.$r->equipment->title.' · '.$r->user->name.' ('.number_format($r->total_price, 2).' DT)'])" />
        <div class="row">
            <div class="col-md-4"><x-form.input name="amount" label="Montant (DT)" type="number" step="0.01" min="0.5" :value="$payment->amount" required /></div>
            <div class="col-md-4"><x-form.select name="method" label="Mode" :options="\App\Models\Payment::METHODS" :selected="$payment->method" required /></div>
            <div class="col-md-4"><x-form.select name="status" label="Statut" :options="\App\Models\Payment::STATUSES" :selected="$payment->status" required /></div>
        </div>
        <div class="row">
            <div class="col-md-6"><x-form.input name="reference" label="Référence" :value="$payment->reference" help="Laisser vide pour générer automatiquement." /></div>
            <div class="col-md-6"><x-form.input name="paid_at" label="Date de paiement" type="datetime-local" :value="$payment->paid_at?->format('Y-m-d\TH:i')" help="Obligatoire si le statut est « Payé »." /></div>
        </div>
        <button class="btn btn-warning">Enregistrer</button>
        <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>
@endsection
