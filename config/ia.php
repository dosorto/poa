<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proveedor de IA
    |--------------------------------------------------------------------------
    |
    | Define qué proveedor de IA utilizar para generar actividades.
    | Opciones disponibles: 'openai', 'gemini', 'ollama'
    |
    */

    'provider' => env('IA_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | API Key de Gemini
    |--------------------------------------------------------------------------
    |
    | Tu clave de API de Google Gemini.
    | Obtén una en: https://makersuite.google.com/app/apikey
    |
    */

    'gemini_api_key' => env('GEMINI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Ollama
    |--------------------------------------------------------------------------
    |
    | Host y API key opcional para una instancia local o remota de Ollama.
    |
    */

    'ollama_host' => env('OLLAMA_HOST', 'http://127.0.0.1:11434'),
    'ollama_api_key' => env('OLLAMA_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Modelos
    |--------------------------------------------------------------------------
    |
    | Configuración específica para cada proveedor
    |
    */

    'models' => [
        'openai' => [
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'temperature' => env('OPENAI_TEMPERATURE', 0.3),
            'max_tokens' => env('OPENAI_MAX_TOKENS', 700),
        ],
        'gemini' => [
            'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
            'temperature' => env('GEMINI_TEMPERATURE', 0.3),
            'max_tokens' => env('GEMINI_MAX_TOKENS', 700),
        ],
        'ollama' => [
            'model' => env('OLLAMA_MODEL', 'qwen2.5:7b-instruct-q8_0'),
            'temperature' => env('OLLAMA_TEMPERATURE', 0.2),
            'max_tokens' => env('OLLAMA_MAX_TOKENS', 700),
            'timeout' => env('OLLAMA_TIMEOUT', 25),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Throttling
    |--------------------------------------------------------------------------
    |
    | Tiempo mínimo en segundos entre solicitudes por usuario
    |
    */

    'throttle_seconds' => env('IA_THROTTLE_SECONDS', 30),

];
