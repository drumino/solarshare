@extends('layouts.front')
@section('title', 'Mes incidents')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="section-title h3 mb-0">Mes incidents</h1>
    <a href="{{ route('front.incidents.create') }}" class="btn btn-sun"><i class="bi bi-plus-lg"></i> Signaler</a>
</div>
<div class="card form-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Incident</th><th>Équipement</th><th>Gravité</th><th>Statut</th><th>Interventions</th></tr></thead>
            <tbody>
            @forelse($incidents as $i)
                <tr>
                    <td><strong>{{ $i->title }}</strong><br><small class="text-muted">{{ \App\Models\Incident::CATEGORIES[$i->category] ?? $i->category }} · {{ $i->created_at->format('d/m/Y') }}</small>
                        @if($i->ai_advice)<br><small><span class="ai-chip">IA</span> {{ $i->ai_advice }}</small>@endif</td>
                    <td>{{ $i->equipment->title }}</td>
                    <td><x-badge :status="$i->severity" :labels="\App\Models\Incident::SEVERITIES" /></td>
                    <td><x-badge :status="$i->status" :labels="\App\Models\Incident::STATUSES" /></td>
                    <td>{{ $i->tasks->count() }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Aucun incident signalé.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $incidents->links() }}</div>
@endsection
