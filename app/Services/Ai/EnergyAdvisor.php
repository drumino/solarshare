<?php

namespace App\Services\Ai;

use App\Models\Equipment;

/**
 * Module 2 (Réservations) - IA : assistant de dimensionnement énergétique.
 * Calcule les besoins (Wh/jour), le dimensionnement panneau + batterie,
 * recommande du matériel DISPONIBLE et rédige des conseils personnalisés.
 */
class EnergyAdvisor
{
    public function __construct(private LlmClient $llm)
    {
    }

    public function analyze(array $appliances, int $days, ?string $context = null): array
    {
        $dailyWh = collect($appliances)->sum(fn ($a) => $a['watts'] * $a['hours']);
        $peakWatts = collect($appliances)->sum('watts');

        $sunHours = config('solarshare.peak_sun_hours');
        $efficiency = config('solarshare.system_efficiency');

        $panelWattsNeeded = (int) ceil($dailyWh / ($sunHours * $efficiency));
        $batteryWhNeeded = (int) ceil($dailyWh * 1.25); // 1 jour d'autonomie + marge pertes

        $panels = Equipment::available()->where('type', 'solar_panel')
            ->where('power_watts', '>=', $panelWattsNeeded)
            ->orderBy('price_per_day')->take(3)->get();

        $batteries = Equipment::available()->where('type', 'battery')
            ->where('capacity_wh', '>=', $batteryWhNeeded)
            ->orderBy('price_per_day')->take(3)->get();

        // Si rien ne couvre le besoin, proposer les plus puissants (couverture partielle)
        $partial = false;
        if ($panels->isEmpty()) {
            $panels = Equipment::available()->where('type', 'solar_panel')->orderByDesc('power_watts')->take(2)->get();
            $partial = $panels->isNotEmpty();
        }
        if ($batteries->isEmpty()) {
            $batteries = Equipment::available()->where('type', 'battery')->orderByDesc('capacity_wh')->take(2)->get();
            $partial = $partial || $batteries->isNotEmpty();
        }

        $summary = [
            'daily_wh' => (int) round($dailyWh),
            'peak_watts' => (int) $peakWatts,
            'panel_watts_needed' => $panelWattsNeeded,
            'battery_wh_needed' => $batteryWhNeeded,
            'days' => $days,
        ];

        return [
            'summary' => $summary,
            'panels' => $panels,
            'batteries' => $batteries,
            'partial' => $partial,
            'advice' => $this->advice($appliances, $summary, $context),
        ];
    }

    private function advice(array $appliances, array $s, ?string $context): array
    {
        $list = collect($appliances)->map(fn ($a) => "{$a['name']} ({$a['watts']} W, {$a['hours']} h/j)")->implode('; ');

        $text = $this->llm->complete(
            'Tu es un conseiller en énergie solaire pour la plateforme SolarShare (Tunisie). Donne 3 à 5 conseils courts et concrets en français (une phrase par ligne, sans markdown).',
            "Appareils : {$list}\nBesoin : {$s['daily_wh']} Wh/jour sur {$s['days']} jour(s), puissance cumulée {$s['peak_watts']} W.\nContexte : ".($context ?: 'non précisé'),
            400
        );

        if ($text) {
            return ['lines' => array_values(array_filter(array_map('trim', preg_split('/\R/', $text)))), 'source' => 'llm'];
        }

        $lines = [
            "Votre consommation estimée est de {$s['daily_wh']} Wh par jour : prévoyez au moins {$s['panel_watts_needed']} W de panneaux et {$s['battery_wh_needed']} Wh de stockage.",
            'Orientez les panneaux plein sud, inclinés d\'environ 30°, et évitez toute ombre entre 10 h et 16 h.',
            'Limitez les appareils gourmands (chauffage, bouilloire, fer) : ils épuisent vite la batterie.',
        ];
        if ($s['peak_watts'] > 1500) {
            $lines[] = 'La puissance simultanée dépasse 1500 W : vérifiez que l\'onduleur supporte ce pic ou échelonnez l\'usage des appareils.';
        }
        if ($s['days'] > 7) {
            $lines[] = 'Pour une longue location, prévoyez une batterie de secours pour les journées peu ensoleillées.';
        }

        return ['lines' => $lines, 'source' => 'fallback'];
    }
}
