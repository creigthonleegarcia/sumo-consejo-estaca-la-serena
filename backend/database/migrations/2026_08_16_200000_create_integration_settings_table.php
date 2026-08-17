<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar rol 'admin' al enum de users
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'presidencia', 'secretario', 'sumo_consejo') DEFAULT 'sumo_consejo'");

        // Tabla para almacenar configuraciones de integraciones con API keys cifradas
        Schema::create('integration_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique(); // openai, twilio, google, etc.
            $table->string('label');               // Nombre amigable: "OpenAI", "Twilio WhatsApp"
            $table->text('api_key')->nullable();    // Cifrado con cast 'encrypted'
            $table->json('config')->nullable();     // Configuración adicional (modelos, regiones, etc.)
            $table->boolean('is_active')->default(false);
            $table->enum('status', ['pending', 'valid', 'invalid', 'expired'])->default('pending');
            $table->text('status_message')->nullable(); // Mensaje de error o validación
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_settings');
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('presidencia', 'secretario', 'sumo_consejo') DEFAULT 'sumo_consejo'");
    }
};
