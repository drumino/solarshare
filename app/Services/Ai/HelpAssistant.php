<?php

namespace App\Services\Ai;

class HelpAssistant
{
    public function __construct(private LlmClient $llm)
    {
    }

    public function answer(string $question, string $page): array
    {
        $page = $this->normalizePage($page);

        if (config('solarshare.ai.driver') !== 'none') {
            $llm = $this->askLlm($question, $page);
            if ($llm !== null && $llm !== '') {
                return ['answer' => $llm, 'engine' => 'llm'];
            }
        }

        return ['answer' => $this->localFaq($question, $page), 'engine' => 'local'];
    }

    private function normalizePage(string $page): string
    {
        $page = mb_strtolower(trim($page));

        return in_array($page, ['avis', 'signalements', 'moderation'], true) ? $page : 'avis';
    }

    private function askLlm(string $question, string $page): ?string
    {
        try {
            $docs = [
                'avis' => 'Liste des avis avec recherche et filtres (sentiment, visibilité, produit). Analyse IA : sentiment, score, badge suspect. Création/modification : notes multi-critères, 3 photos max, historique. Masquer/afficher, suppression.',
                'signalements' => 'File triée par sévérité. Triage IA : catégorie, sévérité, action suggérée. Statuts pending, reviewed, dismissed. Traiter et Rejeter. Masquage automatique à 3 signalements. Notification admin si sévérité haute.',
                'moderation' => 'KPIs, graphique sentiment, moyenne mensuelle, aspects. File de signalements. Filtres date, sentiment, produit. Export CSV et PDF. Cache 5 minutes.',
            ];

            $text = $this->llm->complete(
                'Vous répondez en français aux questions d aide sur l administration SolarShare. Répondez en moins de 80 mots, uniquement à partir de la documentation de la page demandée. Si la réponse ne figure pas dans la documentation, répondez exactement : je ne sais pas. N inventez rien.',
                "Page : {$page}\nDocumentation : {$docs[$page]}\nQuestion : {$question}",
                160
            );

            if (is_string($text) && trim($text) !== '') {
                return trim($text);
            }
        } catch (\Throwable $e) {
            // bascule sur le moteur local
        }

        return null;
    }

    private function localFaq(string $question, string $page): string
    {
        $entries = $this->faqEntries();
        $normalized = $this->normalize($question);

        $best = null;
        $bestScore = 0;

        foreach ($entries[$page] as $entry) {
            $score = 0;
            foreach ($entry['keywords'] as $keyword) {
                if (str_contains($normalized, $this->normalize($keyword))) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $entry;
            }
        }

        if ($best !== null) {
            return $best['answer'];
        }

        return $this->defaultMessage($page);
    }

    /**
     * @return array<string, array{keywords: string[], answer: string}[]>
     */
    private function faqEntries(): array
    {
        return [
            'avis' => [
                ['keywords' => ['liste', 'avis', 'recherche'], 'answer' => 'Consultez la liste des avis et utilisez la barre de recherche.'],
                ['keywords' => ['filtre', 'sentiment', 'visibilité', 'visibilite', 'produit'], 'answer' => 'Filtrez les avis par sentiment, visibilité ou produit.'],
                ['keywords' => ['analyse', 'ia', 'sentiment', 'score', 'badge', 'suspect'], 'answer' => 'Chaque avis reçoit une analyse IA : sentiment, score et un badge suspect si nécessaire.'],
                ['keywords' => ['creation', 'création', 'modification', 'notes', 'photos', 'historique'], 'answer' => 'Créez ou modifiez un avis avec des notes multi-critères, 3 photos maximum et consultez l historique.'],
                ['keywords' => ['masquer', 'afficher', 'suppression'], 'answer' => 'Vous pouvez masquer, afficher ou supprimer un avis.'],
                ['keywords' => ['commentaire', 'évaluation', 'evaluation', 'note'], 'answer' => 'Un avis contient un commentaire et une note sur 5.'],
            ],
            'signalements' => [
                ['keywords' => ['file', 'signalements', 'severité', 'severite', 'tri'], 'answer' => 'Les signalements sont classés dans une file triée par sévérité.'],
                ['keywords' => ['triage', 'ia', 'catégorie', 'categorie', 'action', 'suggérée'], 'answer' => 'Le triage IA propose une catégorie, une sévérité et une action suggérée.'],
                ['keywords' => ['statut', 'pending', 'reviewed', 'dismissed', 'traiter', 'rejeter'], 'answer' => 'Statuts : pending, reviewed, dismissed. Actions disponibles : Traiter et Rejeter.'],
                ['keywords' => ['masquage', 'automatique', 'signalements'], 'answer' => 'Un avis est masqué automatiquement après 3 signalements.'],
                ['keywords' => ['notification', 'admin', 'severité', 'severite', 'haute'], 'answer' => 'L administrateur est notifié lorsqu un signalement a une sévérité haute.'],
                ['keywords' => ['signaler', 'signalement', 'inapproprié', 'inappropriate'], 'answer' => 'Signalez un avis inapproprié depuis sa page.'],
            ],
            'moderation' => [
                ['keywords' => ['kpi', 'indicateurs', 'tableau', 'bord'], 'answer' => 'Le tableau de bord affiche les KPIs de modération.'],
                ['keywords' => ['graphique', 'sentiment', 'moyenne', 'mensuelle', 'aspects'], 'answer' => 'Consultez le graphique de sentiment, la moyenne mensuelle et les aspects.'],
                ['keywords' => ['file', 'signalements', 'filtre', 'date', 'produit'], 'answer' => 'File de signalements avec filtres par date, sentiment et produit.'],
                ['keywords' => ['export', 'csv', 'pdf'], 'answer' => 'Exportez les données en CSV ou en PDF.'],
                ['keywords' => ['cache', 'minutes'], 'answer' => 'Les données sont mises en cache pendant 5 minutes.'],
                ['keywords' => ['moderation', 'modération'], 'answer' => 'La modération centralise les KPIs, les graphiques, la file de signalements et les exports.'],
            ],
        ];
    }

    private function defaultMessage(string $page): string
    {
        return match ($page) {
            'avis' => 'Je peux vous aider sur : la liste et la recherche des avis, les filtres (sentiment, visibilité, produit), l analyse IA, la création et la modification, le masquage et la suppression.',
            'signalements' => 'Je peux vous aider sur : la file de signalements, le triage IA, les statuts, Traiter et Rejeter, le masquage automatique et les notifications admin.',
            'moderation' => 'Je peux vous aider sur : les KPIs, le graphique de sentiment, la moyenne mensuelle, les aspects, la file de signalements, les filtres et l export CSV ou PDF.',
            default => 'Je peux vous aider sur : la liste et la recherche des avis, les filtres, l analyse IA et la suppression.',
        };
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $accents = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'æ' => 'ae', 'ã' => 'a', 'å' => 'a', 'ā' => 'a', 'ă' => 'a', 'ą' => 'a',
            'ç' => 'c', 'ć' => 'c', 'č' => 'c', 'đ' => 'd', 'ď' => 'd',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ē' => 'e', 'ė' => 'e', 'ę' => 'e', 'ě' => 'e',
            'ĝ' => 'g', 'ğ' => 'g', 'ḧ' => 'h',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ĩ' => 'i', 'ī' => 'i', 'į' => 'i', 'ı' => 'i',
            'ĵ' => 'j', 'ĺ' => 'l', 'ł' => 'l',
            'ń' => 'n', 'ň' => 'n', 'ñ' => 'n',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o', 'ø' => 'o', 'ō' => 'o', 'ő' => 'o', 'œ' => 'oe',
            'ṕ' => 'p', 'ŕ' => 'r', 'ř' => 'r', 'ß' => 'ss',
            'ś' => 's', 'š' => 's', 'ș' => 's', 'ť' => 't', 'ț' => 't',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ũ' => 'u', 'ū' => 'u', 'ų' => 'u', 'ů' => 'u',
            'ẁ' => 'w', 'ẅ' => 'w', 'ẇ' => 'w', 'ẉ' => 'w', 'ŵ' => 'w',
            'ý' => 'y', 'ÿ' => 'y', 'ź' => 'z', 'ż' => 'z', 'ž' => 'z',
        ];

        return strtr($text, $accents);
    }
}
