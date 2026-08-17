<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiTextService
{
    /**
     * Mejorar un informe de mayordomía usando GPT-4o.
     */
    public function improveReport(string $text, string $context = 'informe de mayordomía'): array
    {
        $integration = IntegrationSetting::where('provider', 'openai')
            ->where('is_active', true)
            ->first();

        if (!$integration) {
            return [
                'success' => false,
                'error' => 'La integración de OpenAI no está configurada o está desactivada. Configúrela en Integraciones.',
            ];
        }

        try {
            $response = Http::withToken($integration->api_key)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $integration->config['model'] ?? 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "Eres un asistente de la Iglesia de Jesucristo de los Santos de los Últimos Días. Tu tarea es mejorar la redacción de un {$context}. Mantén el contenido original pero mejora la claridad, gramática y estructura. Responde SOLO con el texto mejorado, sin explicaciones ni comentarios adicionales. Mantén un tono formal pero cálido, apropiado para la Iglesia.",
                        ],
                        [
                            'role' => 'user',
                            'content' => $text,
                        ],
                    ],
                    'max_tokens' => 2000,
                    'temperature' => 0.3,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'text' => $response->json('choices.0.message.content'),
                ];
            }

            Log::warning('OpenAI API error', ['status' => $response->status(), 'body' => $response->body()]);

            return [
                'success' => false,
                'error' => 'Error al comunicarse con OpenAI. Intente nuevamente.',
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI API exception', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Error de conexión con el servicio de IA.',
            ];
        }
    }
}
