<?php

namespace App\Services\Ai;

class ReportTriage
{
    private const KEYWORDS = [
        'insulte' => ['con', 'idiot', 'imbécile', 'merde', 'connard', 'salaud', 'fuck', 'shit', 'stupid'],
        'arnaque' => ['arnaque', 'escroc', 'voleur', 'arnaqueur', 'scam'],
        'spam' => ['http', 'www.', 'lien', 'promo', 'publicité', 'pub'],
        'donnees_personnelles' => ['téléphone', 'email', 'adresse', 'numéro', '@'],
        'harcelement' => ['harcel', 'menace', 'menacer', 'tuer', 'crever'],
    ];

    /**
     * Catégorie de repli pour les motifs réellement envoyés par le formulaire front
     * (App\Models\ReviewReport::REASONS). Les mots-clés ci-dessus sont en français :
     * sans cette table, un motif comme "offensive" ne correspond à aucun mot-clé et
     * toute analyse locale retombait sur "autre"/"low".
     */
    private const REASON_CATEGORIES = [
        'offensive' => 'insulte',
        'spam' => 'spam',
        'fake' => 'autre',
        'other' => 'autre',
    ];

    public function __construct(private LlmClient $llm)
    {
    }

    /** @return array{category:string,severity:string,suggested_action:string} */
    public function classify(string $reason, ?string $reviewComment = null, ?string $details = null): array
    {
        $ai = $this->llm->json(
            'Tu classes un signalement d\'avis. Format JSON : {"category":"insulte|arnaque|spam|donnees_personnelles|harcelement|autre","severity":"low|medium|high","suggested_action":"masquer|ignorer|avertir"}.',
            "Motif : {$reason}\nDétails : ".($details ?? '')."\nAvis : ".($reviewComment ?? ''),
            120
        );

        if ($ai && isset($ai['category'], $ai['severity'], $ai['suggested_action'])
            && in_array($ai['severity'], ['low', 'medium', 'high'], true)
            && in_array($ai['suggested_action'], ['masquer', 'ignorer', 'avertir'], true)) {
            return [
                'category' => (string) $ai['category'],
                'severity' => (string) $ai['severity'],
                'suggested_action' => (string) $ai['suggested_action'],
            ];
        }

        return $this->fallback($reason, $reviewComment, $details);
    }

    private function fallback(string $reason, ?string $reviewComment, ?string $details): array
    {
        $text = mb_strtolower(trim($reason.' '.($details ?? '').' '.($reviewComment ?? '')));

        $detected = null;
        foreach (self::KEYWORDS as $cat => $words) {
            foreach ($words as $w) {
                if (str_contains($text, $w)) {
                    $detected = $cat;
                    break 2;
                }
            }
        }

        $baseline = self::REASON_CATEGORIES[mb_strtolower(trim($reason))] ?? null;

        $category = $this->mostSevere($detected, $baseline);

        return [
            'category' => $category,
            'severity' => $this->severityFor($category),
            'suggested_action' => $this->actionFor($category),
        ];
    }

    /**
     * La catégorie la plus grave l'emporte : un mot-clé trouvé dans les détails peut
     * élever la sévérité du motif choisi, mais jamais l'abaisser.
     */
    private function mostSevere(?string $detected, ?string $baseline): string
    {
        $candidates = array_values(array_filter([$detected, $baseline]));

        if ($candidates === []) {
            return 'autre';
        }

        usort($candidates, fn ($a, $b) => $this->rank($b) <=> $this->rank($a));

        return $candidates[0];
    }

    private function rank(string $category): int
    {
        return $this->severityFor($category) === 'high' ? 2 : 1;
    }

    private function severityFor(string $category): string
    {
        return match ($category) {
            'harcelement', 'donnees_personnelles', 'insulte', 'arnaque' => 'high',
            default => 'low',
        };
    }

    private function actionFor(string $category): string
    {
        return match ($category) {
            'insulte', 'arnaque', 'harcelement', 'donnees_personnelles' => 'masquer',
            'spam' => 'avertir',
            default => 'ignorer',
        };
    }
}
