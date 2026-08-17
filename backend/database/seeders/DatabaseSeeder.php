<?php

namespace Database\Seeders;

use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Administrador General
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@estaca.cl',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Presidente de Estaca
        User::create([
            'name' => 'Presidente García',
            'email' => 'presidente@estaca.cl',
            'password' => Hash::make('password'),
            'role' => 'presidencia',
            'phone' => '+56912345678',
            'email_verified_at' => now(),
        ]);

        // Primer Consejero
        User::create([
            'name' => 'Consejero López',
            'email' => 'consejero1@estaca.cl',
            'password' => Hash::make('password'),
            'role' => 'presidencia',
            'phone' => '+56912345679',
            'email_verified_at' => now(),
        ]);

        // Secretario de Estaca
        User::create([
            'name' => 'Secretario Muñoz',
            'email' => 'secretario@estaca.cl',
            'password' => Hash::make('password'),
            'role' => 'secretario',
            'phone' => '+56912345680',
            'email_verified_at' => now(),
        ]);

        // Miembros del Sumo Consejo (12 miembros típicamente)
        $miembros = [
            'Carlos Rojas', 'Miguel Vargas', 'Fernando Silva',
            'Andrés Torres', 'Rodrigo Díaz', 'Pablo Soto',
            'Jorge Morales', 'Diego Herrera', 'Nicolás Fuentes',
            'Cristián Reyes', 'Sebastián Castro', 'Alejandro Pizarro',
        ];

        foreach ($miembros as $i => $nombre) {
            User::create([
                'name' => $nombre,
                'email' => 'sc' . ($i + 1) . '@estaca.cl',
                'password' => Hash::make('password'),
                'role' => 'sumo_consejo',
                'email_verified_at' => now(),
            ]);
        }

        // Integraciones predefinidas
        IntegrationSetting::create([
            'provider' => 'openai',
            'label' => 'OpenAI',
            'api_key' => env('OPENAI_API_KEY', 'sk-placeholder-configure-in-admin'),
            'config' => ['model' => 'gpt-4o', 'whisper_model' => 'whisper-1'],
            'is_active' => false,
            'status' => 'pending',
            'updated_by' => $admin->id,
        ]);

        IntegrationSetting::create([
            'provider' => 'twilio',
            'label' => 'Twilio WhatsApp',
            'config' => [],
            'is_active' => false,
            'status' => 'pending',
            'updated_by' => $admin->id,
        ]);
    }
}
