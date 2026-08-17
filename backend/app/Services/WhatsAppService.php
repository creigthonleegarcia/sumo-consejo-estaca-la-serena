<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Enviar un mensaje de WhatsApp vía Twilio.
     */
    public function send(string $to, string $message): array
    {
        $integration = IntegrationSetting::where('provider', 'twilio')
            ->where('is_active', true)
            ->first();

        if (!$integration) {
            return ['success' => false, 'error' => 'Twilio no está configurado.'];
        }

        $config = $integration->config;
        $accountSid = $config['account_sid'] ?? null;
        $fromNumber = $config['from_number'] ?? null;

        if (!$accountSid || !$fromNumber) {
            return ['success' => false, 'error' => 'Configuración de Twilio incompleta.'];
        }

        try {
            $response = Http::withBasicAuth($accountSid, $integration->api_key)
                ->timeout(15)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                    'From' => "whatsapp:{$fromNumber}",
                    'To' => "whatsapp:{$to}",
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                return ['success' => true, 'sid' => $response->json('sid')];
            }

            Log::warning('Twilio API error', ['status' => $response->status(), 'body' => $response->body()]);
            return ['success' => false, 'error' => 'Error al enviar mensaje WhatsApp.'];
        } catch (\Exception $e) {
            Log::error('Twilio exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Error de conexión con Twilio.'];
        }
    }
}
