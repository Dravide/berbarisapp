<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create champion_categories table first (if not exists)
        if (!Schema::hasTable('champion_categories')) {
            Schema::create('champion_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('eventner_id')->constrained('eventners')->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // Then create the pivot table
        if (!Schema::hasTable('champion_assessment')) {
            Schema::create('champion_assessment', function (Blueprint $table) {
                $table->id();
                $table->foreignId('champion_category_id')->constrained('champion_categories')->cascadeOnDelete();
                $table->foreignId('assessment_category_id')->constrained('assessment_categories')->cascadeOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('champion_assessment');
        Schema::dropIfExists('champion_categories');
    }
};
