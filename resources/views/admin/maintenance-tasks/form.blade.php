@extends('layouts.admin')
@section('title', $task->exists ? 'Modifier la tâche' : 'Nouvelle tâche')
@section('heading', $task->exists ? 'Modifier la tâche' : 'Nouvelle tâche de maintenance')
@section('content')
<div class="card card-ad p-4" style="max-width:720px">
    <form method="POST" novalidate action="{{ $task->exists ? route('admin.maintenance-tasks.update', $task) : route('admin.maintenance-tasks.store') }}">
        @csrf @if($task->exists) @method('PUT') @endif
        <x-form.select name="incident_id" label="Incident" required placeholder="— Choisir —" :selected="$task->incident_id"
            :options="$incidents->mapWithKeys(fn ($i) => [$i->id => '#'.$i->id.' · '.$i->title.' ('.$i->equipment->title.')'])" />
        @php $advice = $incidents->firstWhere('id', old('incident_id', $task->incident_id))?->ai_advice; @endphp
        @if($advice)<div class="alert alert-light border small"><span class="ai-chip">IA</span> Intervention recommandée : {{ $advice }}</div>@endif
        <x-form.input name="title" label="Intitulé de l'intervention" :value="$task->title" required />
        <div class="row">
            <div class="col-md-6"><x-form.select name="technician_id" label="Technicien" :options="$technicians->pluck('name', 'id')" :selected="$task->technician_id" placeholder="— Non assigné —" /></div>
            <div class="col-md-6"><x-form.select name="status" label="Statut" :options="\App\Models\MaintenanceTask::STATUSES" :selected="$task->status" required /></div>
        </div>
        <div class="row">
            <div class="col-md-4"><x-form.input name="planned_at" label="Date prévue" type="date" :value="$task->planned_at?->format('Y-m-d')" required /></div>
            <div class="col-md-4"><x-form.input name="completed_at" label="Date de fin" type="date" :value="$task->completed_at?->format('Y-m-d')" /></div>
            <div class="col-md-4"><x-form.input name="cost" label="Coût (DT)" type="number" step="0.5" min="0" :value="$task->cost" /></div>
        </div>
        <x-form.textarea name="notes" label="Notes" :value="$task->notes" rows="3" />
        <p class="small text-muted">L'état de l'incident et de l'équipement est synchronisé automatiquement avec l'avancement des tâches.</p>
        <button class="btn btn-warning">Enregistrer</button>
        <a href="{{ route('admin.maintenance-tasks.index') }}" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>
@endsection
