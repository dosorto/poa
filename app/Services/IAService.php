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
            'qwen' => config('ia.qwen_api_key'),
            'ollama' => config('ia.ollama_api_key'),
            default => config('openai.api_key'),
        };
    }

    public function generarActividad($nombreActividad, $contextoInstitucion = 'Universidad Nacional Autónoma de Honduras', array $contextoAdicional = [])
    {
        $prompt = $this->construirPrompt($nombreActividad, $contextoInstitucion, $contextoAdicional);
        $startedAt = microtime(true);

        try {
            $data = match ($this->provider) {
                'gemini' => $this->generarConGemini($prompt),
                'qwen' => $this->generarConQwen($prompt),
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

    protected function construirPrompt($nombreActividad, $contextoInstitucion, array $contextoAdicional = [])
    {
        $lineasContexto = collect($contextoAdicional)
            ->filter(fn ($valor) => filled($valor))
            ->map(fn ($valor, $clave) => '- ' . str_replace('_', ' ', ucfirst($clave)) . ': ' . $valor)
            ->implode("\n");

        $contextoEspecifico = $lineasContexto
            ? "\n            ## CONTEXTO ESPECÍFICO DE LA ACTIVIDAD\n\n            {$lineasContexto}\n"
            : '';

        return "Eres un experto en planificación estratégica institucional de la Universidad Nacional Autónoma de Honduras (UNAH), con profundo conocimiento del Plan Estratégico Institucional (PEI) 2024-2027 y del modelo de Gestión por Resultados adoptado por la institución.

            ## CONTEXTO INSTITUCIONAL UNAH

            **Misión:** Contribuir a través de la formación de profesionales, la investigación y la vinculación Universidad-Sociedad al desarrollo humano sostenible del país, atendiendo la pertinencia académica para las diversas necesidades regionales y el ámbito nacional.

            **Tres funciones sustantivas:**
            1. DOCENCIA — Formación de profesionales de grado (licenciatura, técnico, tecnólogo) y posgrado (maestría, doctorado, especialidad) en los 5 campos del conocimiento (Ciencias Sociales, Físico-Matemáticas, Biológicas y de Salud, Administrativas, Agro-forestales).
            2. INVESTIGACIÓN — Investigación científica, humanística y tecnológica (DICIHT). Productos: investigaciones finalizadas, artículos en revistas indexadas, marcas/patentes, becas de investigación.
            3. VINCULACIÓN UNIVERSIDAD-SOCIEDAD (DVUS) — Proyectos en los ejes: APS (Atención Primaria en Salud), ENF (Educación No Formal), Comunicación y Difusión, Seguimiento a Egresados, Desarrollo Local y Cultural.

            **Áreas programáticas del PEI:**
            - Mejoramiento de la Calidad, Equidad y Pertinencia
            - Fortalecimiento Institucional

            **Dimensiones estratégicas:** Desarrollo Académico, Investigación, Vinculación, Desarrollo Estudiantil, Gobernabilidad Universitaria, Gestión Académica y Administrativa, Sistema de Educación Superior.

            **Unidades Ejecutoras (UE):** Facultades, Centros Regionales Universitarios (CRU), Instituto Tecnológico Superior de Tela (ITST), CRAED, Telecentros. Cada UE formula su Plan Operativo Anual (POA) articulado con el PEI y con asignación presupuestaria.

            **Eje transversal:** Aseguramiento de la Calidad — todos los procesos deben orientarse a la autoevaluación, acreditación y mejora continua.

            **Poblaciones objetivo válidas en la UNAH:**
            Estudiantes de nuevo ingreso, estudiantes de reingreso, estudiantes en riesgo académico, docentes universitarios, personal administrativo, personal de servicio, comunidad universitaria en general, egresados/graduados, comunidad hondureña (en vinculación), autoridades universitarias, investigadores.

            **Medios de verificación usados en el POA-UNAH:**
            Informes técnicos de avance, actas de reunión o sesión, listas de asistencia con firma, registros fotográficos, resoluciones o acuerdos del Consejo Universitario/JDU, documentos o manuales aprobados, reportes del sistema (SINAHP, SGA, SEDI), publicaciones o evidencias de difusión, certificados o diplomas emitidos, contratos o convenios firmados.

            **Tipos de indicadores del PEI (usar como referencia de formato):**
            - Cantidad absoluta: \"Total de investigaciones científicas desarrolladas\", \"Número de convenios firmados\", \"Cantidad de docentes capacitados\"
            - Porcentaje/tasa: \"Tasa de incremento de estudiantes matriculados\", \"Porcentaje de Unidades Ejecutoras con POA aprobado\", \"Porcentaje de avance en implementación del SIAC\"
            - Hito/entregable binario (0 o 1): \"Manual de macroprocesos elaborado\", \"Política de vinculación aprobada\"

            ---

            ## TAREA

            Basándote en el nombre de actividad: **'{$nombreActividad}'**
            Contexto institucional: **{$contextoInstitucion}**
            {$contextoEspecifico}

            Genera un JSON con los siguientes campos para incluir en el Plan Operativo Anual (POA):

            - **descripcion**: Descripción detallada y profesional (2-3 oraciones). Debe explicar qué se hará, cómo se enmarca en alguna de las tres funciones sustantivas (docencia, investigación o vinculación) y cómo contribuye al PEI 2024-2027.
            - **resultadoActividad**: El resultado concreto y medible esperado (1 oración clara), expresado como un logro o entregable verificable.
            - **poblacion_objetivo**: La población específica de la UNAH que se beneficiará (usar las poblaciones válidas del contexto anterior).
            - **medio_verificacion**: El medio oficial que se usará para comprobar el cumplimiento (usar los medios de verificación válidos del contexto anterior).
            - **indicadores**: Array de **2-3 indicadores específicos** alineados al estilo del PEI. Cada indicador debe tener:
            * **nombre**: Nombre conciso siguiendo el formato del PEI (máximo 100 caracteres). Ejemplos de formato: 'Número de docentes capacitados en X', 'Tasa de incremento de Y', 'Porcentaje de avance en Z'.
            * **descripcion**: Qué mide y cómo se calcula. Si es porcentaje incluir fórmula: (valor obtenido / valor planificado) * 100.
            * **cantidadPlanificada**: Meta numérica o porcentual exacta (ej: 100 si la meta es 100%, 5 si la meta son 5 documentos).
            * **isCantidad**: true si mide una cantidad absoluta (conteo), false en caso contrario.
            * **isPorcentaje**: true si mide una tasa o proporción (%), false en caso contrario.

            **Reglas obligatorias:**
            - Los indicadores NO pueden ser de satisfacción ni requerir encuestas.
            - Al menos uno debe ser de cantidad absoluta (isCantidad: true).
            - Usar terminología oficial UNAH: 'Unidad Ejecutora', 'Plan Operativo Anual', 'funciones sustantivas', 'comunidad universitaria', 'CRU', etc.
            - El resultado debe ser coherente con las metas reales del PEI 2024-2027.

            Responde ÚNICAMENTE con el JSON válido, sin markdown ni explicaciones adicionales.";
    }

    protected function generarConOpenAI($prompt)
    {
        if (empty($this->apiKey)) {
            throw new \Exception('No se ha configurado la API Key de OpenAI. Por favor, agrega OPENAI_API_KEY en tu archivo .env o cambia el proveedor a Ollama con IA_PROVIDER=ollama');
        }

        $client = \OpenAI::client($this->apiKey);
        $config = config('ia.models.openai');

        $response = $client->chat()->create([
            'model' => $config['model'],
            'messages' => [
                ['role' => 'system', 'content' => 'Eres un asistente experto en planificación estratégica institucional. Respondes únicamente con JSON válido sin formato markdown.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => (float) $config['temperature'],
            'max_tokens' => (int) $config['max_tokens'],
            'response_format' => ['type' => 'json_object'],
        ]);

        return $this->procesarRespuesta($response->choices[0]->message->content);
    }

    protected function generarConGemini($prompt)
    {
        if (empty($this->apiKey)) {
            throw new \Exception('No se ha configurado la API Key de Gemini. Usa IA_PROVIDER=ollama para la IA local.');
        }

        $config = config('ia.models.gemini');
        $modelName = $config['model'];
        $url = "https://generativelanguage.googleapis.com/v1/models/{$modelName}:generateContent?key={$this->apiKey}";

        $response = Http::timeout(30)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => (float) $config['temperature'],
                    'maxOutputTokens' => (int) $config['max_tokens'],
                ],
            ]);

        if (!$response->successful()) {
            $errorData = $response->json();
            $error = $errorData['error']['message'] ?? $response->body();
            Log::error('Error de Gemini API', [
                'status' => $response->status(),
                'error' => $errorData,
            ]);
            throw new \Exception("Error de Gemini API: {$error}");
        }

        $content = $response->json('candidates.0.content.parts.0.text');

        if (!$content) {
            throw new \Exception('No se recibió respuesta válida de Gemini.');
        }

        return $this->procesarRespuesta($content);
    }

    protected function generarConQwen($prompt)
    {
        $baseUrl = rtrim(config('ia.qwen_base_url', 'http://localhost:8000'), '/');
        $config = config('ia.models.qwen');
        $url = "{$baseUrl}/v1/chat/completions";

        Log::info('Conectando a Qwen API', [
            'url' => $url,
            'model' => $config['model'],
        ]);

        $response = Http::timeout(60)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, [
                'model' => $config['model'],
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un asistente experto en planificación estratégica institucional. Respondes únicamente con JSON válido sin formato markdown.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => (float) $config['temperature'],
                'max_tokens' => (int) $config['max_tokens'],
            ]);

        if (!$response->successful()) {
            $errorData = $response->json();
            $error = $errorData['error']['message'] ?? $response->body();
            Log::error('Error de Qwen API', [
                'status' => $response->status(),
                'error' => $errorData,
            ]);
            throw new \Exception("Error de Qwen API: {$error}");
        }

        $content = $response->json('choices.0.message.content');

        if (!$content) {
            throw new \Exception('No se recibió respuesta válida de Qwen.');
        }

        return $this->procesarRespuesta($content);
    }

    protected function generarConOllama($prompt)
    {
        $host = rtrim(config('ia.ollama_host', 'http://localhost:11434'), '/');
        $config = config('ia.models.ollama');
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
                ['role' => 'system', 'content' => 'Eres un asistente experto en planificación estratégica institucional. Respondes únicamente con JSON válido sin formato markdown.'],
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
            'qwen' => 'Qwen (Local)',
            'ollama' => 'Ollama (Local)',
            default => 'OpenAI',
        };
    }
}
