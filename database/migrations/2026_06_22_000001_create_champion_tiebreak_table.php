<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('champion_tiebreak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('champion_category_id')->constrained('champion_categories')->cascadeOnDelete();
            $table->foreignId('assessment_sub_category_id')->constrained('assessment_sub_categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['champion_category_id', 'assessment_sub_category_id'], 'champion_tiebreak_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('champion_tiebreak');
    }
};
