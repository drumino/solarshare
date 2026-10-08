@extends('layouts.admin')
@section('title', 'Signalements')
@section('heading', 'Signalements d\'avis')
@section('content')
<div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
    <form class="row g-2" method="GET">
        <div class="col-auto"><select name="status" class="form-select"><option value="">Tous statuts</option>
            @foreach(\App\Models\ReviewReport::STATUSES as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-auto"><select name="reason" class="form-select"><option value="">Tous motifs</option>
            @foreach(\App\Models\ReviewReport::REASONS as $k => $l)<option value="{{ $k }}" @selected(request('reason') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-funnel"></i></button></div>
    </form>
    <a href="{{ route('admin.review-reports.create') }}" class="btn btn-warning"><i class="bi bi-plus-lg"></i> Nouveau signalement</a>
</div>
<div class="card card-ad"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Avis signalé</th><th>Signalé par</th><th>Motif</th><th>Statut</th><th>Date</th><th></th></tr></thead>
    <tbody>
    @forelse($reports as $r)
        <tr><td class="small" style="max-width:300px">{{ \Illuminate\Support\Str::limit($r->review_comment, 80) }}
                @unless($r->review_visible)<br><span class="badge text-bg-secondary">Avis masqué</span>@endunless</td>
            <td>{{ $r->reporter_name }}</td>
            <td>{{ \App\Models\ReviewReport::REASONS[$r->reason] ?? $r->reason }}@if($r->details)<br><small class="text-muted">{{ \Illuminate\Support\Str::limit($r->details, 50) }}</small>@endif</td>
            <td><x-badge :status="$r->status" :labels="\App\Models\ReviewReport::STATUSES" /></td>
            <td>{{ $r->created_at->format('d/m/Y') }}</td>
            <td class="text-end text-nowrap"><a href="{{ route('admin.review-reports.edit', $r) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                @include('partials.delete', ['route' => route('admin.review-reports.destroy', $r)])</td></tr>
    @empty
        <tr><td colspan="6" class="text-center text-muted py-4">Aucun signalement.</td></tr>
    @endforelse
    </tbody></table></div></div>
<div class="mt-3">{{ $reports->links() }}</div>
@endsection
