<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['presidencia', 'sumo_consejo']);
            $table->enum('modality', ['presencial', 'videoconferencia']);
            $table->string('location_or_url')->nullable();
            $table->string('platform')->nullable(); // Google Meet, Zoom, Teams
            $table->string('name');
            $table->text('agenda')->nullable();
            $table->timestamp('scheduled_at');
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'processed'])->default('scheduled');
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
