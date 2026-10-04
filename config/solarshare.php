<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fonctionnalités IA
    |--------------------------------------------------------------------------
    | driver : none | anthropic | openai (API compatible OpenAI : OpenAI, Groq, Ollama...)
    | Sans clé API (driver "none"), chaque fonctionnalité IA utilise un moteur
    | de secours local (règles + statistiques) : l'application reste utilisable.
    */
    'ai' => [
        'driver'   => env('AI_DRIVER', 'none'),
        'api_key'  => env('AI_API_KEY'),
        'model'    => env('AI_MODEL'),
        'base_url' => env('AI_BASE_URL'),
        'timeout'  => (int) env('AI_TIMEOUT', 20),
    ],

    'max_rental_days' => 30,

    // Heures d'ensoleillement équivalentes par jour (Tunisie) et rendement système
    'peak_sun_hours' => 5,
    'system_efficiency' => 0.75,
];
