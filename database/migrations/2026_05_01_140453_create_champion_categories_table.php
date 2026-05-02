<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('champion_assessment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('champion_category_id')->constrained('champion_categories')->cascadeOnDelete();
            $table->foreignId('assessment_category_id')->constrained('assessment_categories')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('champion_assessment');
    }
};
