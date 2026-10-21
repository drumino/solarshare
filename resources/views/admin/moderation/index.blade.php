@extends('layouts.admin')
@section('title', 'Modération')
@section('heading', 'Tableau de modération')
@section('content')
<div class="mb-3">
    <p class="text-muted mb-0">Vue d'ensemble de la modération des avis.</p>
</div>

<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-auto"><label class="form-label small mb-0">Du</label><input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control"></div>
    <div class="col-auto"><label class="form-label small mb-0">Au</label><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control"></div>
    <div class="col-auto"><label class="form-label small mb-0">Sentiment</label><select name="sentiment" class="form-select"><option value="">Tous</option>
        @foreach(['positive'=>'Positif','neutral'=>'Neutre','negative'=>'Négatif'] as $k=>$l)<option value="{{ $k }}" @selected(($filters['sentiment'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="col-auto"><label class="form-label small mb-0">Produit</label><select name="equipment_id" class="form-select"><option value="">Tous les produits</option>
        @foreach($equipment as $eq)<option value="{{ $eq->id }}" @selected(($filters['equipment_id'] ?? '') == $eq->id)>{{ $eq->title }}</option>@endforeach</select></div>
    <div class="col-auto"><button class="btn btn-outline-secondary">Filtrer</button></div>
    <div class="col-auto"><a href="{{ route('admin.moderation.export', $filters) }}" class="btn btn-warning">Exporter CSV</a></div>
    <div class="col-auto"><a href="{{ route('admin.moderation.pdf', $filters) }}" class="btn btn-outline-danger">Exporter PDF</a></div>
</form>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-6 col-xl-3"><div class="card card-ad p-3"><h6 class="mb-1">Total avis</h6><h3 class="mb-0">{{ $kpis['total'] }}</h3></div></div>
    <div class="col-12 col-md-6 col-xl-3"><div class="card card-ad p-3"><h6 class="mb-1">Avis masqués</h6><h3 class="mb-0">{{ $kpis['hidden'] }}</h3></div></div>
    <div class="col-12 col-md-6 col-xl-3"><div class="card card-ad p-3"><h6 class="mb-1">Part toxicité</h6><h3 class="mb-0">{{ $kpis['toxic_share'] }}%</h3></div></div>
    <div class="col-12 col-md-6 col-xl-3"><div class="card card-ad p-3"><h6 class="mb-1">Signalements en attente</h6><h3 class="mb-0">{{ $kpis['pending_reports'] }}</h3></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-4"><div class="card card-ad p-3">
        <h6>Sentiment</h6>
        @if(array_sum($sentiment) === 0)<p class="text-muted">Aucune donnée</p>@else
        <div style="height:280px; position:relative;"><canvas id="sentimentChart"></canvas></div>@endif
    </div></div>
    <div class="col-12 col-lg-4"><div class="card card-ad p-3">
        <h6>Moyenne mensuelle</h6>
        @if(empty(array_filter($monthly, fn($v)=>$v!==null)))<p class="text-muted">Aucune donnée</p>@else
        <div style="height:280px; position:relative;"><canvas id="monthlyChart"></canvas></div>@endif
    </div></div>
    <div class="col-12 col-lg-4"><div class="card card-ad p-3">
        <h6>Aspects</h6>
        @if(empty($aspects))<p class="text-muted">Aucune donnée</p>@else
        <div style="height:280px; position:relative;"><canvas id="aspectsChart"></canvas></div>@endif
    </div></div>
</div>

<div class="card card-ad">
    <div class="card-header">File de signalements</div>
    <div class="table-responsive" style="overflow-x:auto;"><table class="table mb-0">
        <thead><tr><th>Avis</th><th>Sévérité</th><th>Date</th><th></th></tr></thead>
        <tbody>
        @forelse($queue as $r)
            <tr><td>{{ \Illuminate\Support\Str::limit($r->review?->comment, 60) }}</td>
                <td><span class="badge text-bg-{{ $r->severity==='high'?'danger':($r->severity==='medium'?'warning':'success') }}">{{ $r->severity }}</span></td>
                <td>{{ $r->created_at->format('d/m/Y') }}</td>
                <td><a href="{{ route('admin.review-reports.show', $r) }}" class="btn btn-sm btn-outline-primary">Voir</a></td></tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted py-4">Aucune donnée</td></tr>
        @endforelse
        </tbody></table></div>
</div>

@if(! empty($filters['equipment_id']) && $productReviews->isNotEmpty())
<div class="card card-ad mt-3">
    <div class="card-header">Avis du produit</div>
    <div class="table-responsive" style="overflow-x:auto;"><table class="table mb-0">
        <thead><tr><th>Auteur</th><th>Note</th><th>Sentiment</th><th>Commentaire</th><th>Visible</th></tr></thead>
        <tbody>
        @foreach($productReviews as $pr)
            <tr><td>{{ $pr->user?->name }}</td>
                <td><x-stars :rating="$pr->rating" /></td>
                <td><x-badge :status="$pr->sentiment" :labels="\App\Models\Review::SENTIMENTS" /></td>
                <td class="small" style="max-width:280px">{{ \Illuminate\Support\Str::limit($pr->comment, 90) }}</td>
                <td>{!! $pr->is_visible ? '<i class="bi bi-eye text-success"></i>' : '<i class="bi bi-eye-slash text-danger"></i>' !!}</td></tr>
        @endforeach
        </tbody></table></div>
    <div class="card-body"><a href="{{ route('admin.reviews.index', ['equipment_id' => $filters['equipment_id']]) }}" class="btn btn-sm btn-outline-primary">Voir tous les avis de ce produit</a></div>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const sentimentData = @json(array_values($sentiment));
if (document.getElementById('sentimentChart')) {
    new Chart(document.getElementById('sentimentChart'), {type:'doughnut', data:{labels:['Positif','Neutre','Négatif'], datasets:[{data:sentimentData}]}, options:{responsive:true,maintainAspectRatio:false}});
}
const monthlyLabels = @json(array_keys($monthly));
const monthlyValues = @json(array_values($monthly));
if (document.getElementById('monthlyChart')) {
    new Chart(document.getElementById('monthlyChart'), {type:'line', data:{labels:monthlyLabels, datasets:[{label:'Moyenne', data:monthlyValues}]}, options:{responsive:true,maintainAspectRatio:false}});
}
const aspectsLabels = @json(array_keys($aspects));
const aspectsValues = @json(array_values($aspects));
if (document.getElementById('aspectsChart')) {
    new Chart(document.getElementById('aspectsChart'), {type:'bar', data:{labels:aspectsLabels, datasets:[{label:'Occurrences', data:aspectsValues}]}, options:{responsive:true,maintainAspectRatio:false}});
}
</script>
@endsection
