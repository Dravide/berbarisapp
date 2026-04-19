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
        Schema::create('assessment_criterias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_sub_category_id')->constrained('assessment_sub_categories')->onDelete('cascade');
            $table->string('name');
            $table->json('score_options');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_criterias');
    }
};
