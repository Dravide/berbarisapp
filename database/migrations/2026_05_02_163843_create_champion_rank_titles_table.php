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
        Schema::create('champion_rank_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('champion_category_id')->constrained('champion_categories')->cascadeOnDelete();
            $table->string('title'); // e.g. "Juara Utama", "Harapan 1"
            $table->unsignedInteger('rank_start'); // e.g. 1
            $table->unsignedInteger('rank_end'); // e.g. 3
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('champion_rank_titles');
    }
};
