<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntegrationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class IntegrationController extends Controller
{
    /**
     * Lista todas las integraciones con sus proveedores soportados.
     */
    public function index(): JsonResponse
    {
        $settings = IntegrationSetting::with('updater:id,name')
            ->orderBy('provider')
            ->get()
            ->map(function ($setting) {
                $setting->masked_key = $setting->masked_key;
                return $setting;
            });

        $providers = IntegrationSetting::supportedProviders();

        return response()->json([
            'data' => $settings,
            'providers' => $providers,
        ]);
    }

    /**
     * Crear o actualizar una integración.
     */
    public function upsert(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => 'required|string|max:50',
            'label' => 'required|string|max:100',
            'api_key' => 'nullable|string',
            'config' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $setting = IntegrationSetting::updateOrCreate(
            ['provider' => $request->provider],
            [
                'label' => $request->label,
                'api_key' => $request->api_key,
                'config' => $request->config,
                'is_active' => $request->boolean('is_active', false),
                'status' => 'pending',
                'status_message' => null,
                'validated_at' => null,
                'updated_by' => $request->user()->id,
            ]
        );

        return response()->json([
            'message' => "Integración '{$setting->label}' guardada. Ejecuta la validación para verificar la API key.",
            'data' => $this->enrichSetting($setting),
        ]);
    }

    /**
     * Validar que la API key funciona conectándose al proveedor.
     */
    public function validate(Request $request, IntegrationSetting $integration): JsonResponse
    {
        if (!$integration->isConfigured()) {
            return response()->json([
                'message' => 'No hay API key configurada para validar.',
            ], 422);
        }

        $result = match ($integration->provider) {
            'openai' => $this->validateOpenAI($integration),
            'twilio' => $this->validateTwilio($integration),
            default => ['valid' => false, 'message' => 'Proveedor no soportado para validación automática.'],
        };

        $integration->update([
            'status' => $result['valid'] ? 'valid' : 'invalid',
            'status_message' => $result['message'],
            'validated_at' => now(),
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => $result['message'],
            'data' => $this->enrichSetting($integration->fresh()),
        ]);
    }

    /**
     * Eliminar una integración.
     */
    public function destroy(IntegrationSetting $integration): JsonResponse
    {
        $label = $integration->label;
        $integration->delete();

        return response()->json([
            'message' => "Integración '{$label}' eliminada.",
        ]);
    }

    /**
     * Toggle activar/desactivar una integración.
     */
    public function toggle(Request $request, IntegrationSetting $integration): JsonResponse
    {
        $integration->update([
            'is_active' => !$integration->is_active,
            'updated_by' => $request->user()->id,
        ]);

        $state = $integration->is_active ? 'activada' : 'desactivada';

        return response()->json([
            'message' => "Integración '{$integration->label}' {$state}.",
            'data' => $this->enrichSetting($integration),
        ]);
    }

    // =========================================
    // Validaciones por proveedor
    // =========================================

    private function validateOpenAI(IntegrationSetting $setting): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $setting->api_key,
            ])->timeout(10)->get('https://api.openai.com/v1/models');

            if ($response->successful()) {
                $models = collect($response->json('data', []))->pluck('id');
                $config = $setting->config ?? [];
                $selectedModel = $config['model'] ?? 'gpt-4o';
                $hasModel = $models->contains($selectedModel);

                return [
                    'valid' => true,
                    'message' => $hasModel
                        ? "✅ Conexión exitosa. Modelo '{$selectedModel}' disponible. {$models->count()} modelos accesibles."
                        : "✅ Conexión exitosa pero el modelo '{$selectedModel}' no está disponible. Modelos disponibles: " . $models->take(10)->join(', '),
                ];
            }

            if ($response->status() === 401) {
                return ['valid' => false, 'message' => '❌ API Key inválida o revocada.'];
            }

            if ($response->status() === 429) {
                return ['valid' => false, 'message' => '⚠️ Rate limit alcanzado. La key es válida pero tiene restricciones.'];
            }

            return ['valid' => false, 'message' => "❌ Error HTTP {$response->status()}: " . $response->body()];
        } catch (\Exception $e) {
            return ['valid' => false, 'message' => '❌ Error de conexión: ' . $e->getMessage()];
        }
    }

    private function validateTwilio(IntegrationSetting $setting): array
    {
        $config = $setting->config ?? [];
        $sid = $config['account_sid'] ?? null;
        $token = $config['auth_token'] ?? null;

        if (!$sid || !$token) {
            return ['valid' => false, 'message' => '❌ Falta Account SID o Auth Token en la configuración.'];
        }

        try {
            $response = Http::withBasicAuth($sid, $token)
                ->timeout(10)
                ->get("https://api.twilio.com/2010-04-01/Accounts/{$sid}.json");

            if ($response->successful()) {
                $accountName = $response->json('friendly_name', 'Unknown');
                return ['valid' => true, 'message' => "✅ Conexión exitosa. Cuenta: {$accountName}"];
            }

            return ['valid' => false, 'message' => '❌ Credenciales de Twilio inválidas.'];
        } catch (\Exception $e) {
            return ['valid' => false, 'message' => '❌ Error de conexión: ' . $e->getMessage()];
        }
    }

    // =========================================
    // Helpers
    // =========================================

    private function enrichSetting(IntegrationSetting $setting): array
    {
        $data = $setting->toArray();
        $data['masked_key'] = $setting->masked_key;
        return $data;
    }
}
