<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eventner_id')->constrained()->onDelete('cascade');
            $table->foreignId('registration_id')->constrained()->onDelete('cascade');
            $table->foreignId('assessment_criteria_id')->constrained()->onDelete('cascade');
            $table->string('score');
            $table->timestamps();

            $table->unique(['registration_id', 'assessment_criteria_id'], 'unique_score_per_participant_criteria');
            $table->index('eventner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_scores');
    }
};
