<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

/**
 * Client LLM minimal. Drivers : anthropic | openai (compatible OpenAI, Groq, Ollama...) | none.
 * Retourne null en cas d'échec : chaque fonctionnalité IA bascule alors sur son moteur local.
 */
class LlmClient
{
    public function enabled(): bool
    {
        $driver = config('solarshare.ai.driver');

        return match ($driver) {
            'anthropic' => filled(config('solarshare.ai.api_key')),
            'openai' => filled(config('solarshare.ai.api_key')) || filled(config('solarshare.ai.base_url')),
            default => false,
        };
    }

    public function complete(string $system, string $prompt, int $maxTokens = 600): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            return match (config('solarshare.ai.driver')) {
                'anthropic' => $this->anthropic($system, $prompt, $maxTokens),
                'openai' => $this->openai($system, $prompt, $maxTokens),
                default => null,
            };
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /** Demande une réponse JSON et la décode (null si invalide). */
    public function json(string $system, string $prompt, int $maxTokens = 500): ?array
    {
        $text = $this->complete($system.' Réponds uniquement avec un objet JSON valide, sans texte autour.', $prompt, $maxTokens);

        if ($text && preg_match('/\{.*\}/s', $text, $m)) {
            $data = json_decode($m[0], true);

            return is_array($data) ? $data : null;
        }

        return null;
    }

    private function anthropic(string $system, string $prompt, int $maxTokens): ?string
    {
        $base = rtrim(config('solarshare.ai.base_url') ?: 'https://api.anthropic.com', '/');

        $response = Http::timeout(config('solarshare.ai.timeout'))
            ->withHeaders([
                'x-api-key' => config('solarshare.ai.api_key'),
                'anthropic-version' => '2023-06-01',
            ])
            ->post($base.'/v1/messages', [
                'model' => config('solarshare.ai.model') ?: 'claude-sonnet-5-5',
                'max_tokens' => $maxTokens,
                'system' => $system,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ])
            ->throw();

        return data_get($response->json(), 'content.0.text');
    }

    private function openai(string $system, string $prompt, int $maxTokens): ?string
    {
        $base = rtrim(config('solarshare.ai.base_url') ?: 'https://api.openai.com/v1', '/');

        $response = Http::timeout(config('solarshare.ai.timeout'))
            ->withToken((string) config('solarshare.ai.api_key'))
            ->post($base.'/chat/completions', [
                'model' => config('solarshare.ai.model') ?: 'gpt-4o-mini',
                'max_tokens' => $maxTokens,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ])
            ->throw();

        return data_get($response->json(), 'choices.0.message.content');
    }
}
