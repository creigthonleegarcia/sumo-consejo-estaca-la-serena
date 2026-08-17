<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calling_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calling_id')->constrained('callings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('vote', ['approve', 'disapprove']);
            $table->timestamp('voted_at');
            $table->timestamps();

            $table->unique(['calling_id', 'user_id']); // Un voto por usuario por llamamiento
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calling_votes');
    }
};
