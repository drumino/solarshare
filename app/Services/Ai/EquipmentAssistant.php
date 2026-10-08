<?php

namespace App\Services\Ai;

use App\Models\Equipment;

/**
 * Module 1 (Catalogue) - IA :
 *  - génération d'une annonce attractive (LLM, sinon modèle de texte local)
 *  - suggestion de prix journalier (formule technique + moyenne du marché SolarShare)
 */
class EquipmentAssistant
{
    public function __construct(private LlmClient $llm)
    {
    }

    public function describe(array $d): array
    {
        $type = Equipment::TYPES[$d['type']] ?? 'équipement';
        $specs = collect([
            ! empty($d['power_watts']) ? $d['power_watts'].' W' : null,
            ! empty($d['capacity_wh']) ? $d['capacity_wh'].' Wh' : null,
            Equipment::CONDITIONS[$d['condition'] ?? 'bon'] ?? null,
            ! empty($d['city']) ? 'à '.$d['city'] : null,
        ])->filter()->implode(', ');

        $text = $this->llm->complete(
            'Tu rédiges des annonces de location d\'équipements d\'énergie renouvelable pour la plateforme SolarShare (Tunisie). Ton chaleureux, factuel, 3 à 4 phrases, en français, sans emoji ni liste.',
            "Type : {$type}\nTitre : ".($d['title'] ?? '')."\nCaractéristiques : {$specs}\nMentionne les usages typiques (camping, chantier, coupure de courant, événement) et une consigne de bon usage.",
            350
        );

        if ($text) {
            return ['text' => trim($text), 'source' => 'llm'];
        }

        $title = $d['title'] ?? $type;
        $use = match ($d['type']) {
            'solar_panel' => 'idéal pour recharger vos appareils en camping, en déplacement ou lors d\'une coupure de courant',
            'battery' => 'parfait pour stocker l\'énergie solaire et alimenter vos appareils en toute autonomie',
            'wind' => 'conçu pour produire de l\'électricité d\'appoint sur un site dégagé et venteux',
            'inverter' => 'pratique pour convertir et réguler l\'énergie de vos panneaux et batteries',
            default => 'utile pour vos besoins ponctuels en énergie renouvelable',
        };

        return [
            'text' => "{$title} ({$specs}) : {$use}. Équipement entretenu et vérifié avant chaque location. "
                .'Merci de le restituer propre, complet et de respecter les consignes de sécurité.',
            'source' => 'fallback',
        ];
    }

    public function suggestPrice(array $d): array
    {
        $watts = (int) ($d['power_watts'] ?? 0);
        $wh = (int) ($d['capacity_wh'] ?? 0);

        $formula = match ($d['type']) {
            'solar_panel', 'wind' => max(4, $watts * 0.03),
            'battery' => max(5, $wh * 0.012),
            'inverter' => max(4, $watts * 0.015),
            default => 8,
        };

        $factor = match ($d['condition'] ?? 'bon') {
            'neuf' => 1.15,
            'tres_bon' => 1.0,
            'bon' => 0.9,
            default => 0.75,
        };
        $formula *= $factor;

        $market = Equipment::query()
            ->where('type', $d['type'])
            ->where('status', 'available')
            ->avg('price_per_day');

        $suggested = $market ? (0.6 * $formula + 0.4 * (float) $market) : $formula;
        $suggested = round($suggested * 2) / 2; // pas de 0,5 DT

        return [
            'suggested' => $suggested,
            'min' => round($suggested * 0.8 * 2) / 2,
            'max' => round($suggested * 1.25 * 2) / 2,
            'deposit' => max(10, round($suggested * 10 / 5) * 5),
            'explanation' => $market
                ? 'Calcul basé sur la puissance/capacité, l\'état du matériel et le prix moyen observé sur SolarShare ('.number_format((float) $market, 2).' DT/jour).'
                : 'Calcul basé sur la puissance/capacité et l\'état du matériel (pas encore de références sur la plateforme).',
        ];
    }
}
