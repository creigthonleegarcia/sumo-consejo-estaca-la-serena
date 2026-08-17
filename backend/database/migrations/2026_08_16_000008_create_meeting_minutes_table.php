<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_minutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
            $table->string('audio_path')->nullable();
            $table->json('transcription')->nullable();
            $table->json('agile_minute')->nullable();
            $table->text('executive_summary')->nullable();
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('processing_error')->nullable();
            $table->timestamps();

            $table->unique('meeting_id'); // Una minuta por reunión
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_minutes');
    }
};
