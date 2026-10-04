@extends('layouts.front')
@section('title', 'Assistant énergie')
@section('content')
<h1 class="section-title h3"><i class="bi bi-stars text-warning"></i> Assistant énergie</h1>
<p class="text-muted">Décrivez vos appareils : l'assistant calcule votre consommation, dimensionne panneau et batterie, puis propose du matériel disponible.</p>

<div class="row g-4">
    <div class="col-lg-6">
        <form method="POST" action="{{ route('front.advisor.analyze') }}" class="card form-card p-4" novalidate>
            @csrf
            <h2 class="h6">Mes appareils</h2>
            <div class="mb-2 d-flex flex-wrap gap-1">
                @foreach($presets as $p)
                    <button type="button" class="btn btn-sm btn-outline-secondary preset" data-name="{{ $p['name'] }}" data-watts="{{ $p['watts'] }}" data-hours="{{ $p['hours'] }}">+ {{ $p['name'] }}</button>
                @endforeach
            </div>
            <table class="table table-sm align-middle">
                <thead><tr><th>Appareil</th><th style="width:100px">Watts</th><th style="width:110px">h / jour</th><th></th></tr></thead>
                <tbody id="appliance-rows">
                @php $rows = old('appliances', $old ?? [['name' => '', 'watts' => '', 'hours' => '']]); @endphp
                @foreach($rows as $i => $row)
                    <tr>
                        <td><input type="text" name="appliances[{{ $i }}][name]" class="form-control @error("appliances.$i.name") is-invalid @enderror" value="{{ $row['name'] ?? '' }}" placeholder="Appareil" required></td>
                        <td><input type="number" name="appliances[{{ $i }}][watts]" class="form-control @error("appliances.$i.watts") is-invalid @enderror" value="{{ $row['watts'] ?? '' }}" min="1" max="5000" required></td>
                        <td><input type="number" step="0.5" name="appliances[{{ $i }}][hours]" class="form-control @error("appliances.$i.hours") is-invalid @enderror" value="{{ $row['hours'] ?? '' }}" min="0.1" max="24" required></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <button type="button" id="add-row" class="btn btn-sm btn-outline-secondary mb-3 align-self-start"><i class="bi bi-plus"></i> Ajouter un appareil</button>

            <x-form.input name="days" label="Durée de location (jours)" type="number" min="1" :max="config('solarshare.max_rental_days')" :value="old('days', $result['summary']['days'] ?? 3)" required />
            <x-form.textarea name="context" label="Contexte (optionnel)" rows="2" help="Ex. : week-end de camping, coupure de courant fréquente, chantier..." />
            @if($errors->any())<div class="text-danger small mb-2">Vérifiez les champs en rouge.</div>@endif
            <button class="btn btn-sun">Analyser mes besoins</button>
        </form>
    </div>

    <div class="col-lg-6">
        @if($result)
            @php $s = $result['summary']; @endphp
            <div class="card form-card p-4 mb-3">
                <h2 class="h6">Résultat</h2>
                <div class="row text-center g-2">
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><strong class="fs-5">{{ $s['daily_wh'] }}</strong><small class="d-block text-muted">Wh / jour</small></div></div>
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><strong class="fs-5">{{ $s['peak_watts'] }}</strong><small class="d-block text-muted">W cumulés</small></div></div>
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><strong class="fs-5">{{ $s['panel_watts_needed'] }}</strong><small class="d-block text-muted">W de panneaux</small></div></div>
                    <div class="col-6 col-md-3"><div class="border rounded p-2"><strong class="fs-5">{{ $s['battery_wh_needed'] }}</strong><small class="d-block text-muted">Wh de batterie</small></div></div>
                </div>
                <h3 class="h6 mt-3">Conseils <span class="ai-chip">{{ $result['advice']['source'] === 'llm' ? 'IA' : 'moteur local' }}</span></h3>
                <ul class="mb-0">@foreach($result['advice']['lines'] as $line)<li>{{ $line }}</li>@endforeach</ul>
            </div>

            @if($result['partial'])
                <div class="alert alert-warning">Aucun équipement disponible ne couvre entièrement le besoin : voici les plus puissants (couverture partielle, envisagez de cumuler plusieurs unités).</div>
            @endif

            @foreach(['panels' => 'Panneaux recommandés', 'batteries' => 'Batteries recommandées'] as $key => $label)
                <h3 class="h6 section-title">{{ $label }}</h3>
                @forelse($result[$key] as $eq)
                    <a href="{{ route('front.equipment.show', $eq) }}" class="card form-card p-3 mb-2 text-decoration-none text-dark">
                        <div class="d-flex justify-content-between">
                            <span><strong>{{ $eq->title }}</strong><br><small class="text-muted">{{ $eq->city }} · {{ $eq->power_watts ? $eq->power_watts.' W' : '' }} {{ $eq->capacity_wh ? $eq->capacity_wh.' Wh' : '' }}</small></span>
                            <span class="price-tag">{{ number_format($eq->price_per_day, 2) }} DT/j</span>
                        </div>
                    </a>
                @empty
                    <p class="text-muted small">Aucun équipement disponible dans cette catégorie.</p>
                @endforelse
            @endforeach
        @else
            <div class="ai-banner p-4"><h2 class="h6">Comment ça marche ?</h2>
                <p class="mb-0 text-muted">Ajoutez vos appareils (ou utilisez les raccourcis), indiquez la durée, puis lancez l'analyse. Aucun compte n'est nécessaire.</p></div>
        @endif
    </div>
</div>
@endsection
@push('scripts')<script src="{{ asset('js/advisor.js') }}"></script>@endpush
