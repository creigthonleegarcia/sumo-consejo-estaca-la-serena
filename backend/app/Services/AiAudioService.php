<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AiAudioService
{
    /**
     * Transcribir un archivo de audio usando Whisper API.
     */
    public function transcribe(string $audioPath): array
    {
        $integration = IntegrationSetting::where('provider', 'openai')
            ->where('is_active', true)
            ->first();

        if (!$integration) {
            return ['success' => false, 'error' => 'OpenAI no está configurado.'];
        }

        try {
            $filePath = Storage::disk('local')->path($audioPath);

            $response = Http::withToken($integration->api_key)
                ->timeout(120)
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => $integration->config['whisper_model'] ?? 'whisper-1',
                    'language' => 'es',
                    'response_format' => 'text',
                ]);

            if ($response->successful()) {
                return ['success' => true, 'text' => $response->body()];
            }

            Log::warning('Whisper API error', ['status' => $response->status()]);
            return ['success' => false, 'error' => 'Error en la API de Whisper.'];
        } catch (\Exception $e) {
            Log::error('Whisper exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Error de conexión con Whisper.'];
        }
    }

    /**
     * Generar acta estructurada desde una transcripción.
     */
    public function generateMinute(string $transcription, string $meetingName): array
    {
        $integration = IntegrationSetting::where('provider', 'openai')
            ->where('is_active', true)
            ->first();

        if (!$integration) {
            return ['success' => false, 'error' => 'OpenAI no está configurado.'];
        }

        try {
            $response = Http::withToken($integration->api_key)
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $integration->config['model'] ?? 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Eres un asistente de la Iglesia de Jesucristo. Genera un acta de reunión estructurada en formato Markdown a partir de la transcripción. Incluye: ## Asistentes, ## Agenda, ## Temas Tratados (con resumen de cada punto), ## Acuerdos y Compromisos, ## Próximos Pasos. Mantén un tono formal e institucional.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Reunión: {$meetingName}\n\nTranscripción:\n{$transcription}",
                        ],
                    ],
                    'max_tokens' => 3000,
                    'temperature' => 0.2,
                ]);

            if ($response->successful()) {
                return ['success' => true, 'text' => $response->json('choices.0.message.content')];
            }

            return ['success' => false, 'error' => 'Error al generar el acta.'];
        } catch (\Exception $e) {
            Log::error('GPT minute exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Error de conexión con OpenAI.'];
        }
    }
}
