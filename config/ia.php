<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proveedor de IA
    |--------------------------------------------------------------------------
    |
    | Define qué proveedor de IA utilizar para generar actividades.
    | Opciones disponibles: 'openai', 'gemini', 'qwen', 'ollama'
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
    | Configuración de Qwen Local
    |--------------------------------------------------------------------------
    |
    | Tu clave de API de Qwen (puede estar vacía si no se requiere autenticación)
    | Base URL: URL del servidor Qwen local
    |
    */

    'qwen_api_key' => env('QWEN_API_KEY', ''),
    'qwen_base_url' => env('QWEN_BASE_URL', 'http://localhost:8000'),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Ollama Local
    |--------------------------------------------------------------------------
    |
    | Tu clave de API de Ollama (puede estar vacía si no se requiere autenticación)
    | Host: URL del servidor Ollama local (ej: http://10.16.33.215:11434)
    |
    */

    'ollama_api_key' => env('OLLAMA_API_KEY', ''),
    'ollama_host' => env('OLLAMA_HOST', 'http://localhost:11434'),

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
        'qwen' => [
            'model' => env('QWEN_MODEL', 'qwen2.5:32b'),
            'temperature' => 0.7,
            'max_tokens' => 1200,
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
