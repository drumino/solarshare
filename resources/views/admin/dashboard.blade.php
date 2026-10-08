@extends('layouts.admin')
@section('title', 'Tableau de bord')
@section('heading', 'Tableau de bord')
@section('content')
@php
    $kpiCards = [
        ['Utilisateurs', $kpis['users'], 'bi-people', 'primary'],
        ['Administrateurs', $kpis['admins'], 'bi-shield-lock', 'dark'],
        ['Membres', $kpis['members'], 'bi-person', 'success'],
        ['Inscrits ce mois-ci', $kpis['new_this_month'], 'bi-person-plus', 'warning'],
    ];
    $role = collect($byRole)->mapWithKeys(fn ($v, $k) => [(\App\Models\User::ROLES[$k] ?? $k) => $v]);
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
    <div class="col-lg-6"><div class="card card-ad p-3"><h2 class="h6">Utilisateurs par rôle</h2><canvas id="chRole" height="160"></canvas></div></div>
    <div class="col-lg-6"><div class="card card-ad p-3"><h2 class="h6">Utilisateurs par ville</h2><canvas id="chCity" height="160"></canvas></div></div>
</div>

<div class="card card-ad">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between">
        <span>Dernières inscriptions</span>
        <a href="{{ route('admin.users.index') }}" class="small">Tout voir <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="table-responsive"><table class="table mb-0 align-middle">
        <thead><tr><th>Nom</th><th>E-mail</th><th>Ville</th><th>Rôle</th><th>Inscrit le</th></tr></thead>
        <tbody>
        @foreach($latestUsers as $u)
            <tr><td>{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ $u->city ?? '—' }}</td>
                <td><x-badge :status="$u->role" :labels="\App\Models\User::ROLES" /></td>
                <td>{{ $u->created_at->format('d/m/Y') }}</td></tr>
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
mk('chRole', 'doughnut', @json($role));
mk('chCity', 'bar', @json($byCity));
</script>
@endpush
