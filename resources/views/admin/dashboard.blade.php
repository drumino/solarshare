@extends('layouts.admin')
@section('title', 'Tableau de bord')
@section('heading', 'Tableau de bord')
@section('content')
@php
    $kpiCards = [
        ['Utilisateurs', $kpis['users'], 'bi-people', 'primary'],
        ['Équipements', $kpis['equipment'].' ('.$kpis['pending_equipment'].' à valider)', 'bi-lightning-charge', 'warning'],
        ['Réservations', $kpis['reservations'], 'bi-calendar-check', 'info'],
        ['Revenus encaissés', number_format($kpis['revenue'], 2).' DT', 'bi-cash-coin', 'success'],
        ['Incidents ouverts', $kpis['open_incidents'], 'bi-exclamation-octagon', 'danger'],
        ['Signalements ouverts', $kpis['open_reports'], 'bi-flag', 'secondary'],
        ['Note moyenne', $kpis['avg_rating'].' / 5', 'bi-star-fill', 'warning'],
    ];
    $tr = fn ($data, $labels) => collect($data)->mapWithKeys(fn ($v, $k) => [($labels[$k] ?? $k) => $v]);
    $type = $tr($byType, \App\Models\Equipment::TYPES);
    $status = $tr($byStatus, \App\Models\Reservation::STATUSES);
    $sent = $tr($bySentiment, \App\Models\Review::SENTIMENTS);
    $sev = $tr($bySeverity, \App\Models\Incident::SEVERITIES);
@endphp

<div class="row g-3 mb-4">
    @foreach($kpiCards as [$label, $value, $icon, $color])
        <div class="col-sm-6 col-xl-3">
            <div class="card kpi p-3"><div class="d-flex align-items-center gap-3">
                <div class="icon bg-{{ $color }}-subtle text-{{ $color }}-emphasis"><i class="bi {{ $icon }}"></i></div>
                <div><div class="text-muted small">{{ $label }}</div><div class="fw-bold fs-5">{{ $value }}</div></div>
            </div></div>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6"><div class="card card-ad p-3"><h2 class="h6">Équipements par type</h2><canvas id="chType" height="160"></canvas></div></div>
    <div class="col-lg-6"><div class="card card-ad p-3"><h2 class="h6">Réservations par statut</h2><canvas id="chStatus" height="160"></canvas></div></div>
    <div class="col-lg-6"><div class="card card-ad p-3"><h2 class="h6">Sentiment des avis <span class="ai-chip">IA</span></h2><canvas id="chSent" height="160"></canvas></div></div>
    <div class="col-lg-6"><div class="card card-ad p-3"><h2 class="h6">Incidents par gravité</h2><canvas id="chSev" height="160"></canvas></div></div>
</div>

<div class="card card-ad">
    <div class="card-header bg-white fw-semibold">Dernières réservations</div>
    <div class="table-responsive"><table class="table mb-0 align-middle">
        <thead><tr><th>Équipement</th><th>Locataire</th><th>Période</th><th>Total</th><th>Statut</th></tr></thead>
        <tbody>
        @foreach($latestReservations as $r)
            <tr><td>{{ $r->equipment->title }}</td><td>{{ $r->user->name }}</td>
                <td>{{ $r->start_date->format('d/m') }} → {{ $r->end_date->format('d/m/Y') }}</td>
                <td>{{ number_format($r->total_price, 2) }} DT</td>
                <td><x-badge :status="$r->status" :labels="\App\Models\Reservation::STATUSES" /></td></tr>
        @endforeach
        </tbody></table></div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const palette = ['#f59e0b', '#16a34a', '#0ea5e9', '#8b5cf6', '#ef4444', '#64748b'];
const mk = (id, type, data) => new Chart(document.getElementById(id), {
  type,
  data: { labels: Object.keys(data), datasets: [{ data: Object.values(data), backgroundColor: palette, borderWidth: 0 }] },
  options: { plugins: { legend: { display: type !== 'bar', position: 'bottom' } }, scales: type === 'bar' ? { y: { beginAtZero: true, ticks: { precision: 0 } } } : {} },
});
mk('chType', 'bar', @json($type));
mk('chStatus', 'doughnut', @json($status));
mk('chSent', 'doughnut', @json($sent));
mk('chSev', 'bar', @json($sev));
</script>
@endpush
