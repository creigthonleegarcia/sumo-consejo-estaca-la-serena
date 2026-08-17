<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationSetting extends Model
{
    protected $fillable = [
        'provider',
        'label',
        'api_key',
        'config',
        'is_active',
        'status',
        'status_message',
        'validated_at',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'config' => 'array',
            'is_active' => 'boolean',
            'validated_at' => 'datetime',
        ];
    }

    /**
     * Hidden por defecto para no exponer keys en JSON.
     */
    protected $hidden = ['api_key'];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // --- Helpers ---

    public function isValid(): bool
    {
        return $this->status === 'valid';
    }

    public function isConfigured(): bool
    {
        return $this->api_key !== null;
    }

    /**
     * Devuelve la key enmascarada para mostrar en UI.
     * Ej: sk-proj-****...oYjA8A
     */
    public function getMaskedKeyAttribute(): ?string
    {
        if (!$this->api_key) {
            return null;
        }

        $key = $this->api_key;
        $len = strlen($key);

        if ($len <= 12) {
            return str_repeat('•', $len);
        }

        return substr($key, 0, 8) . '••••••••' . substr($key, -6);
    }

    /**
     * Proveedores soportados con su metadata.
     */
    public static function supportedProviders(): array
    {
        return [
            [
                'provider' => 'openai',
                'label' => 'OpenAI',
                'description' => 'GPT-4o para redacción RAE y Whisper para transcripción de audio.',
                'config_schema' => [
                    'model' => ['type' => 'select', 'label' => 'Modelo de texto', 'options' => ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo'], 'default' => 'gpt-4o'],
                    'whisper_model' => ['type' => 'select', 'label' => 'Modelo de audio', 'options' => ['whisper-1'], 'default' => 'whisper-1'],
                ],
                'validation_endpoint' => 'https://api.openai.com/v1/models',
            ],
            [
                'provider' => 'twilio',
                'label' => 'Twilio WhatsApp',
                'description' => 'Notificaciones por WhatsApp a los miembros del consejo.',
                'config_schema' => [
                    'account_sid' => ['type' => 'text', 'label' => 'Account SID'],
                    'auth_token' => ['type' => 'password', 'label' => 'Auth Token'],
                    'whatsapp_from' => ['type' => 'text', 'label' => 'Número WhatsApp (ej: +14155238886)'],
                ],
                'validation_endpoint' => null,
            ],
        ];
    }
}
