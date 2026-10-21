<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fonctionnalité IA (avis & signalements : analyse de sentiment)
    |--------------------------------------------------------------------------
    | driver : none | anthropic | openai (API compatible OpenAI : OpenAI, Groq, Ollama...)
    | Sans clé API (driver "none"), l'analyse d'avis utilise un moteur
    | de secours local (règles + statistiques) : l'application reste utilisable.
    */
    'ai' => [
        'driver'   => env('AI_DRIVER', 'none'),
        'api_key'  => env('AI_API_KEY'),
        'model'    => env('AI_MODEL'),
        'base_url' => env('AI_BASE_URL'),
        'timeout'  => (int) env('AI_TIMEOUT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Avis et classement bayésien des équipements
    |--------------------------------------------------------------------------
    */
    'reviews' => [
        'top_rated_min_avg'  => 4.5,
        'top_rated_min_count' => 10,
        'bayesian_m'         => 5,
    ],

];
