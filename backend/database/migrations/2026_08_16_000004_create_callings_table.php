<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('callings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposed_by')->constrained('users')->cascadeOnDelete();
            $table->string('member_name');
            $table->string('calling_name');
            $table->string('ward');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('voting_deadline');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('callings');
    }
};
