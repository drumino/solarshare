@extends('layouts.admin')
@section('title', 'Paiements')
@section('heading', 'Paiements')
@section('content')
<div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
    <form class="row g-2" method="GET">
        <div class="col-auto"><select name="status" class="form-select"><option value="">Tous statuts</option>
            @foreach(\App\Models\Payment::STATUSES as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-auto"><select name="method" class="form-select"><option value="">Tous modes</option>
            @foreach(\App\Models\Payment::METHODS as $k => $l)<option value="{{ $k }}" @selected(request('method') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-funnel"></i></button></div>
    </form>
    <a href="{{ route('admin.payments.create') }}" class="btn btn-warning"><i class="bi bi-plus-lg"></i> Nouveau paiement</a>
</div>
<div class="card card-ad"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Référence</th><th>Réservation</th><th>Client</th><th>Montant</th><th>Mode</th><th>Statut</th><th>Date</th><th></th></tr></thead>
    <tbody>
    @forelse($payments as $p)
        <tr><td class="font-monospace">{{ $p->reference }}</td><td>#{{ $p->reservation_id }} · {{ $p->equipment_title }}</td><td>{{ $p->user_name }}</td>
            <td class="fw-semibold">{{ number_format($p->amount, 2) }} DT</td><td>{{ \App\Models\Payment::METHODS[$p->method] ?? $p->method }}</td>
            <td><x-badge :status="$p->status" :labels="\App\Models\Payment::STATUSES" /></td>
            <td>{{ $p->paid_at?->format('d/m/Y H:i') ?? '—' }}</td>
            <td class="text-end text-nowrap"><a href="{{ route('admin.payments.edit', $p) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                @include('partials.delete', ['route' => route('admin.payments.destroy', $p)])</td></tr>
    @empty
        <tr><td colspan="8" class="text-center text-muted py-4">Aucun paiement.</td></tr>
    @endforelse
    </tbody></table></div></div>
<div class="mt-3">{{ $payments->links() }}</div>
@endsection
