<?php

namespace App\Services\Ai;

/**
 * Module 4 (Maintenance) - IA : classification automatique d'un incident
 * (catégorie, gravité, résumé) et recommandation d'intervention.
 */
class IncidentTriage
{
    private const RULES = [
        'safety' => ['critical', ['fumée', 'fumee', 'brûl', 'brul', 'feu', 'étincelle', 'etincelle', 'choc électrique', 'surchauffe', 'gonfl', 'explos', 'odeur']],
        'battery' => ['high', ['batterie', 'ne charge', 'décharge', 'decharge', 'autonomie', 'ne tient pas', 'tension']],
        'damage' => ['medium', ['cassé', 'casse', 'fissure', 'brisé', 'brise', 'rayure', 'choc', 'tordu', 'tombé', 'tombe']],
        'hardware' => ['medium', ['panne', 'ne démarre', 'ne demarre', 'ne fonctionne', 'ne marche', 'connecteur', 'câble', 'cable', 'ventilateur', 'écran', 'ecran']],
    ];

    private const ADVICE = [
        'safety' => 'Débrancher immédiatement, isoler l\'équipement, ne pas le réutiliser avant inspection par un technicien.',
        'battery' => 'Tester la capacité réelle (cycle complet charge/décharge) et contrôler le BMS ainsi que les connecteurs.',
        'damage' => 'Inspection visuelle complète, photos pour l\'assurance/caution, évaluer réparation ou remplacement.',
        'hardware' => 'Diagnostic des connecteurs, câbles et de l\'électronique de régulation avant remise en location.',
        'other' => 'Contacter le locataire pour plus de détails puis planifier un contrôle standard.',
    ];

    public function __construct(private LlmClient $llm)
    {
    }

    /** @return array{category:string,severity:string,summary:string,advice:string,source:string} */
    public function triage(string $title, string $description): array
    {
        $ai = $this->llm->json(
            'Tu es technicien de maintenance pour des équipements solaires en location. Format : {"category":"hardware|battery|safety|damage|other","severity":"low|medium|high|critical","summary":"une phrase","advice":"action recommandée en une phrase"}.',
            "Titre : {$title}\nDescription : {$description}",
            300
        );

        if ($ai
            && in_array($ai['category'] ?? null, array_keys(self::ADVICE), true)
            && in_array($ai['severity'] ?? null, ['low', 'medium', 'high', 'critical'], true)) {
            return [
                'category' => $ai['category'],
                'severity' => $ai['severity'],
                'summary' => (string) ($ai['summary'] ?? ''),
                'advice' => (string) ($ai['advice'] ?? self::ADVICE[$ai['category']]),
                'source' => 'llm',
            ];
        }

        return $this->fallback($title, $description);
    }

    private function fallback(string $title, string $description): array
    {
        $haystack = mb_strtolower($title.' '.$description);
        $category = 'other';
        $severity = 'low';

        foreach (self::RULES as $cat => [$sev, $keywords]) {
            foreach ($keywords as $kw) {
                if (str_contains($haystack, $kw)) {
                    $category = $cat;
                    $severity = $sev;
                    break 2;
                }
            }
        }

        return [
            'category' => $category,
            'severity' => $severity,
            'summary' => \Illuminate\Support\Str::limit(trim($description), 140),
            'advice' => self::ADVICE[$category],
            'source' => 'fallback',
        ];
    }
}
