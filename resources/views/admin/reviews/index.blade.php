@extends('layouts.admin')
@section('title', 'Avis')
@section('heading', 'Avis')
@section('content')
<div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
    <form class="row g-2" method="GET">
        <div class="col-auto"><input name="q" class="form-control" placeholder="Rechercher dans les commentaires" value="{{ request('q') }}"></div>
        <div class="col-auto"><select name="sentiment" class="form-select"><option value="">Tous sentiments</option>
            @foreach(\App\Models\Review::SENTIMENTS as $k => $l)<option value="{{ $k }}" @selected(request('sentiment') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-auto"><select name="visible" class="form-select"><option value="">Visibilité</option>
            <option value="1" @selected(request('visible') === '1')>Visibles</option><option value="0" @selected(request('visible') === '0')>Masqués</option></select></div>
        <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-funnel"></i></button></div>
    </form>
    <a href="{{ route('admin.reviews.create') }}" class="btn btn-warning"><i class="bi bi-plus-lg"></i> Nouvel avis</a>
</div>
<div class="card card-ad"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Équipement</th><th>Auteur</th><th>Note</th><th>Commentaire</th><th>Analyse IA</th><th>Visible</th><th></th></tr></thead>
    <tbody>
    @forelse($reviews as $r)
        <tr><td class="fw-semibold">{{ $r->equipment_title }}</td><td>{{ $r->user_name }}</td>
            <td><x-stars :rating="$r->rating" /></td>
            <td class="small" style="max-width:280px">{{ \Illuminate\Support\Str::limit($r->comment, 90) }}
                @if($r->reports_count)<br><span class="badge text-bg-danger"><i class="bi bi-flag"></i> {{ $r->reports_count }} signalement(s)</span>@endif</td>
            <td><x-badge :status="$r->sentiment" :labels="\App\Models\Review::SENTIMENTS" /><br><small class="text-muted">score {{ number_format($r->sentiment_score, 2) }}</small>
                @if($r->moderation_note)<br><small class="text-danger">{{ $r->moderation_note }}</small>@endif</td>
            <td>{!! $r->is_visible ? '<i class="bi bi-eye text-success"></i>' : '<i class="bi bi-eye-slash text-danger"></i>' !!}</td>
            <td class="text-end text-nowrap"><a href="{{ route('admin.reviews.edit', $r) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                @include('partials.delete', ['route' => route('admin.reviews.destroy', $r)])</td></tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted py-4">Aucun avis.</td></tr>
    @endforelse
    </tbody></table></div></div>
<div class="mt-3">{{ $reviews->links() }}</div>
@endsection
