<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Rapport de modération SolarShare</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 24px; }
    h1 { font-size: 18px; margin-bottom: 4px; }
    h2 { font-size: 13px; margin-top: 16px; margin-bottom: 6px; border-bottom: 1px solid #ccc; padding-bottom: 2px; }
    .meta { color: #555; font-size: 10px; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
    th { background: #f3f4f6; font-weight: bold; }
    .kpi { font-weight: bold; }
</style>
</head>
<body>
    <h1>Rapport de modération SolarShare</h1>
    <div class="meta">Généré le {{ $generatedAt->format('d/m/Y H:i') }}</div>
    <div class="meta">Filtres actifs :
        @if(!empty($filters['from'])) du {{ $filters['from'] }} @endif
        @if(!empty($filters['to'])) au {{ $filters['to'] }} @endif
        @if(!empty($filters['sentiment'])) — sentiment : {{ $filters['sentiment'] }} @endif
        @if(!empty($filters['equipment_id'])) — produit #{{ $filters['equipment_id'] }} @endif
        @if(empty($filters['from']) && empty($filters['to']) && empty($filters['sentiment']) && empty($filters['equipment_id'])) aucun @endif
    </div>

    <h2>Indicateurs clés</h2>
    <table>
        <tr><th>Indicateur</th><th>Valeur</th></tr>
        <tr><td>Total avis</td><td class="kpi">{{ $kpis['total'] ?? '' }}</td></tr>
        <tr><td>Avis masqués</td><td class="kpi">{{ $kpis['hidden'] ?? '' }}</td></tr>
        <tr><td>Part toxicité</td><td class="kpi">{{ $kpis['toxic_share'] ?? '' }}%</td></tr>
        <tr><td>Signalements en attente</td><td class="kpi">{{ $kpis['pending_reports'] ?? '' }}</td></tr>
    </table>

    <h2>Répartition des sentiments</h2>
    <table>
        <tr><th>Sentiment</th><th>Nombre</th></tr>
        @foreach($sentiment as $k => $v)
            <tr><td>{{ $k }}</td><td>{{ $v }}</td></tr>
        @endforeach
    </table>

    <h2>Moyenne mensuelle (12 mois)</h2>
    <table>
        <tr><th>Mois</th><th>Moyenne</th></tr>
        @foreach($monthly as $k => $v)
            <tr><td>{{ $k }}</td><td>{{ $v === null ? '—' : number_format($v, 2) }}</td></tr>
        @endforeach
    </table>

    <h2>Aspects les plus fréquents</h2>
    <table>
        <tr><th>Aspect</th><th>Occurrences</th></tr>
        @foreach($aspects as $k => $v)
            <tr><td>{{ $k }}</td><td>{{ $v }}</td></tr>
        @endforeach
    </table>

    <h2>File de signalements (10 en attente)</h2>
    <table>
        <tr><th>Avis</th><th>Sévérité</th><th>Date</th></tr>
        @forelse($queue as $r)
            <tr>
                <td>{{ \Illuminate\Support\Str::limit($r->review?->comment, 60) }}</td>
                <td>{{ $r->severity }}</td>
                <td>{{ $r->created_at?->format('d/m/Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="3">Aucun signalement en attente.</td></tr>
        @endforelse
    </table>
</body>
</html>