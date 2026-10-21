<?php

namespace App\Services\Ai;

/**
 * Module 3 (Avis) - IA : analyse de sentiment + modération automatique du contenu.
 */
class ReviewAnalyzer
{
    private const POSITIVE = ['excellent', 'super', 'parfait', 'génial', 'genial', 'top', 'bravo', 'recommande', 'satisfait', 'efficace', 'rapide', 'propre', 'fiable', 'merci', 'impeccable', 'great', 'good', 'perfect', 'love'];

    private const NEGATIVE = ['nul', 'mauvais', 'décevant', 'decevant', 'panne', 'cassé', 'casse', 'sale', 'lent', 'arnaque', 'problème', 'probleme', 'déçu', 'decu', 'défectueux', 'defectueux', 'bad', 'broken', 'terrible', 'worst'];

    private const TOXIC = ['con', 'connard', 'idiot', 'imbécile', 'imbecile', 'merde', 'salaud', 'escroc', 'voleur', 'fdp', 'ntm', 'fuck', 'shit', 'stupid'];

    public function __construct(private LlmClient $llm)
    {
    }

    /** @return array{sentiment:string,score:float,toxic:bool,note:?string,source:string,aspects:array,flags:array} */
    public function analyze(string $comment, int $rating, ?float $criteriaAvg = null): array
    {
        $ai = $this->llm->json(
            'Tu analyses des avis de location d\'équipements solaires. Format : {"sentiment":"positive|neutral|negative","score":-1..1,"toxic":true|false,"reason":"courte explication","aspects":["prix","qualité","ponctualité","communication"]}. "toxic" vaut true pour insultes, menaces, spam ou données personnelles. Aspects parmi : prix, qualité, ponctualité, communication.',
            "Note : {$rating}/5\nAvis : {$comment}",
            200
        );

        if ($ai && isset($ai['sentiment']) && in_array($ai['sentiment'], ['positive', 'neutral', 'negative'], true)) {
            $score = max(-1, min(1, (float) ($ai['score'] ?? 0)));
            $aspects = is_array($ai['aspects'] ?? null) ? array_values($ai['aspects']) : $this->extractAspects($comment);
            return [
                'sentiment' => $ai['sentiment'],
                'score' => $score,
                'toxic' => (bool) ($ai['toxic'] ?? false),
                'note' => ! empty($ai['toxic']) ? ($ai['reason'] ?? 'Contenu signalé par l\'IA') : null,
                'source' => 'llm',
                'aspects' => $aspects,
                'flags' => $this->computeFlags($score, $criteriaAvg),
            ];
        }

        $fallback = $this->fallback($comment, $rating);
        $fallback['aspects'] = $this->extractAspects($comment);
        $fallback['flags'] = $this->computeFlags((float) $fallback['score'], $criteriaAvg);
        return $fallback;
    }

    private function extractAspects(string $comment): array
    {
        $text = mb_strtolower($comment);
        $map = [
            'prix' => ['prix', 'tarif', 'cher', 'price', 'cost'],
            'qualité' => ['qualité', 'qualite', 'état', 'etat', 'quality', 'condition'],
            'ponctualité' => ['ponctuel', 'retard', 'livraison', 'punctual', 'delivery'],
            'communication' => ['communication', 'réponse', 'reponse', 'contact', 'répond', 'respond'],
        ];
        $result = [];
        foreach ($map as $aspect => $words) {
            foreach ($words as $w) {
                if (str_contains($text, $w)) {
                    $result[] = $aspect;
                    break;
                }
            }
        }
        return array_values(array_unique($result));
    }

    private function computeFlags(float $score, ?float $criteriaAvg): array
    {
        $flags = [];
        if ($criteriaAvg !== null) {
            if ($criteriaAvg >= 4 && $score < -0.4) {
                $flags[] = 'criteria_comment_mismatch';
            }
            if ($criteriaAvg <= 2 && $score > 0.4) {
                $flags[] = 'criteria_comment_mismatch';
            }
        }
        return array_values(array_unique($flags));
    }

private function fallback(string $comment, int $rating): array
    {
        $words = preg_split('/[^\p{L}]+/u', mb_strtolower($comment), -1, PREG_SPLIT_NO_EMPTY);

        $pos = count(array_intersect($words, self::POSITIVE));
        $neg = count(array_intersect($words, self::NEGATIVE));
        $toxicHits = array_values(array_intersect($words, self::TOXIC));

        $lexical = ($pos + $neg) > 0 ? ($pos - $neg) / ($pos + $neg) : 0;
        $fromRating = ($rating - 3) / 2;
        $score = round(0.6 * $fromRating + 0.4 * $lexical, 2);

        $hasLink = (bool) preg_match('~https?://|www\.~i', $comment);

        return [
            'sentiment' => $score > 0.25 ? 'positive' : ($score < -0.25 ? 'negative' : 'neutral'),
            'score' => $score,
            'toxic' => $toxicHits !== [] || $hasLink,
            'note' => $toxicHits !== [] ? 'Langage inapproprié détecté' : ($hasLink ? 'Lien externe détecté (spam possible)' : null),
            'source' => 'fallback',
        ];
    }
}
