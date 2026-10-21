<?php

namespace App\Services\Ai;

use App\Models\Review;

class ReplySuggester
{
    public function __construct(private LlmClient $llm) {}

    /** @return array{text:string,engine:string} */
    public function suggest(Review $review): array
    {
        $aspects = $this->detectAspects($review->comment);
        $aspectStr = implode(', ', $aspects);

        $ai = $this->llm->complete(
            'Tu es le propriétaire d\'un équipement solaire loué. Rédige une réponse polie et professionnelle (max 80 mots) à cet avis. Mentionne les aspects évoqués. Ne promets aucun remboursement, ne demande pas de données personnelles. Réponds uniquement avec le texte de la réponse, sans guillemets.',
            "Note : {$review->rating}/5\nSentiment : {$review->sentiment}\nAvis : {$review->comment}\nAspects : {$aspectStr}",
            200
        );

        if ($ai) {
            return ['text' => trim($ai), 'engine' => 'llm'];
        }

        return ['text' => $this->local($review, $aspects), 'engine' => 'local'];
    }

    private function detectAspects(string $comment): array
    {
        $text = mb_strtolower($comment);
        $aspects = [];
        if (str_contains($text, 'prix') || str_contains($text, 'tarif') || str_contains($text, 'cher')) $aspects[] = 'prix';
        if (str_contains($text, 'qualité') || str_contains($text, 'qualite') || str_contains($text, 'état') || str_contains($text, 'etat')) $aspects[] = 'qualité';
        if (str_contains($text, 'ponctuel') || str_contains($text, 'retard') || str_contains($text, 'livraison')) $aspects[] = 'ponctualité';
        if (str_contains($text, 'communication') || str_contains($text, 'réponse') || str_contains($text, 'reponse') || str_contains($text, 'contact')) $aspects[] = 'communication';
        return $aspects ?: ['service'];
    }

    private function local(Review $review, array $aspects): string
    {
        $aspectPhrase = implode(', ', $aspects);
        return match ($review->sentiment) {
            'positive' => "Merci pour votre retour positif ! Nous sommes ravis que vous ayez apprécié {$aspectPhrase}. Au plaisir de vous revoir.",
            'neutral' => "Merci pour votre avis. Nous prenons en compte vos remarques sur {$aspectPhrase}. N'hésitez pas à nous donner plus de détails pour améliorer notre service.",
            default => "Nous sommes désolés pour votre expérience. Nous prenons vos remarques sur {$aspectPhrase} très au sérieux et restons à votre disposition pour vous aider. Contactez-nous pour échanger.",
        };
    }
}
