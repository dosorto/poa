<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IAService
{
    protected $provider;
    protected $apiKey;

    public function __construct()
    {
        $this->provider = config('ia.provider', 'openai');
        $this->apiKey = $this->getApiKey();
    }

    protected function getApiKey()
    {
        return match ($this->provider) {
            'gemini' => config('ia.gemini_api_key'),
            'ollama' => config('ia.ollama_api_key'),
            default => config('openai.api_key'),
        };
    }

    public function generarActividad($nombreActividad, $contextoInstitucion = 'Universidad Nacional Autónoma de Honduras')
    {
        $prompt = $this->construirPrompt($nombreActividad, $contextoInstitucion);
        $startedAt = microtime(true);

        try {
            $data = match ($this->provider) {
                'gemini' => $this->generarConGemini($prompt),
                'ollama' => $this->generarConOllama($prompt),
                'openai' => $this->generarConOpenAI($prompt),
                default => throw new \InvalidArgumentException("Proveedor de IA no soportado: {$this->provider}"),
            };

            Log::info('Generación con IA completada', [
                'provider' => $this->getProviderName(),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);

            return $data;
        } catch (\Exception $e) {
            Log::error("Error en IAService ({$this->provider}): " . $e->getMessage(), [
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);
            throw $e;
        }
    }

    protected function construirPrompt($nombreActividad, $contextoInstitucion)
    {
        return <<<PROMPT
Genera una actividad POA para "{$contextoInstitucion}" a partir de este nombre: "{$nombreActividad}".

Devuelve solo JSON válido con esta estructura exacta:
{
  "descripcion": "1-2 oraciones profesionales",
  "resultadoActividad": "1 resultado concreto y medible",
  "poblacion_objetivo": "grupo beneficiado",
  "medio_verificacion": "evidencias de cumplimiento",
  "indicadores": [
    {
      "nombre": "máximo 100 caracteres",
      "descripcion": "qué mide y fórmula solo si aplica",
      "cantidadPlanificada": 100,
      "isCantidad": true,
      "isPorcentaje": false
    }
  ]
}
Incluye 2-3 indicadores altamente específicos, cuantitativos, de gestión o producto. Evita indicadores de satisfacción o encuestas. Cada indicador debe conservar exactamente estos campos: nombre, descripcion, cantidadPlanificada, isCantidad, isPorcentaje.
PROMPT;
    }

    protected function generarConOpenAI($prompt)
    {
        if (empty($this->apiKey)) {
            throw new \Exception('No se ha configurado la API Key de OpenAI. Por favor, agrega OPENAI_API_KEY en tu archivo .env o cambia el proveedor a Gemini con IA_PROVIDER=gemini');
        }

        $client = \OpenAI::client($this->apiKey);
        $config = config('ia.models.openai');

        $response = $client->chat()->create([
            'model' => $config['model'],
            'messages' => [
                ['role' => 'system', 'content' => 'Eres un asistente experto en planificación estratégica institucional. Respondes únicamente con JSON válido sin formato markdown.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => (float) $config['temperature'],
            'max_tokens' => (int) $config['max_tokens'],
            'response_format' => ['type' => 'json_object'],
        ]);

        $content = $response->choices[0]->message->content;
        return $this->procesarRespuesta($content);
    }

    protected function generarConGemini($prompt)
    {
        $config = config('ia.models.gemini');
        $modelName = $config['model'];
        $url = "https://generativelanguage.googleapis.com/v1/models/{$modelName}:generateContent?key={$this->apiKey}";

        $response = Http::timeout(30)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => (float) $config['temperature'],
                    'maxOutputTokens' => (int) $config['max_tokens'],
                    'responseMimeType' => 'application/json',
                ],
            ]);

        if (!$response->successful()) {
            $errorData = $response->json();
            $error = $errorData['error']['message'] ?? $response->body();
            Log::error('Error de Gemini API', [
                'status' => $response->status(),
                'error' => $errorData,
                'url' => $url
            ]);
            throw new \Exception("Error de Gemini API: {$error}");
        }

        $responseData = $response->json();
        $content = $response->json('candidates.0.content.parts.0.text');
        
        if (!$content) {
            Log::error('No se encontró contenido en la respuesta de Gemini', [
                'response' => $responseData,
                'candidates' => $response->json('candidates')
            ]);
            throw new \Exception('No se recibió respuesta válida de Gemini. Por favor, revisa los logs para más detalles.');
        }

        return $this->procesarRespuesta($content);
    }

    protected function generarConOllama($prompt)
    {
        $config = config('ia.models.ollama');
        $host = rtrim(config('ia.ollama_host'), '/');
        $url = "{$host}/api/chat";

        $request = Http::timeout((int) $config['timeout'])
            ->withHeaders(['Content-Type' => 'application/json']);

        if (!empty($this->apiKey)) {
            $request = $request->withToken($this->apiKey);
        }

        Log::info('Conectando a Ollama API', [
            'url' => $url,
            'model' => $config['model'],
        ]);

        $response = $request->post($url, [
            'model' => $config['model'],
            'stream' => false,
            'format' => 'json',
            'messages' => [
                ['role' => 'system', 'content' => 'Responde únicamente JSON válido. No uses markdown.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'options' => [
                'temperature' => (float) $config['temperature'],
                'num_predict' => (int) $config['max_tokens'],
            ],
        ]);

        if (!$response->successful()) {
            Log::error('Error de Ollama API', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception("Error de Ollama API: {$response->body()}");
        }

        $content = $response->json('message.content');

        if (!$content) {
            Log::error('No se encontró contenido en la respuesta de Ollama', [
                'response' => $response->json(),
            ]);
            throw new \Exception('No se recibió respuesta válida de Ollama.');
        }

        return $this->procesarRespuesta($content);
    }

    protected function procesarRespuesta($content)
    {
        // Limpiar posibles markdown
        $content = trim($content);
        $content = preg_replace('/^```json\s*/', '', $content);
        $content = preg_replace('/^```\s*/', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);
        
        $data = json_decode($content, true);

        if (!$data && preg_match('/\{.*\}/s', $content, $matches)) {
            $data = json_decode($matches[0], true);
        }

        if (!$data) {
            Log::error('Error decodificando JSON', ['content' => $content]);
            throw new \Exception('No se pudo procesar la respuesta de la IA. Intente nuevamente.');
        }

        // Validar que tenga los campos necesarios
        $camposRequeridos = ['descripcion', 'resultadoActividad', 'poblacion_objetivo', 'medio_verificacion'];
        foreach ($camposRequeridos as $campo) {
            if (!isset($data[$campo])) {
                $data[$campo] = '';
            }
        }

        if (is_array($data['poblacion_objetivo'])) {
            $data['poblacion_objetivo'] = implode(', ', $data['poblacion_objetivo']);
        }

        $data['indicadores'] = collect($data['indicadores'] ?? [])
            ->map(function ($indicador) {
                return [
                    'nombre' => (string) ($indicador['nombre'] ?? ''),
                    'descripcion' => (string) ($indicador['descripcion'] ?? ''),
                    'cantidadPlanificada' => $indicador['cantidadPlanificada'] ?? 0,
                    'isCantidad' => (bool) ($indicador['isCantidad'] ?? false),
                    'isPorcentaje' => (bool) ($indicador['isPorcentaje'] ?? false),
                ];
            })
            ->values()
            ->all();

        return $data;
    }

    public function getProvider()
    {
        return $this->provider;
    }

    public function getProviderName()
    {
        return match ($this->provider) {
            'gemini' => 'Google Gemini',
            'ollama' => 'Ollama (Local)',
            default => 'OpenAI',
        };
    }
}
