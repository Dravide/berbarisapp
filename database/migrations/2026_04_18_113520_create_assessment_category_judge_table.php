<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assessment_category_judge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('judge_id')->constrained('judges')->onDelete('cascade');
            $table->foreignId('assessment_category_id')->constrained('assessment_categories')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_category_judge');
    }
};
