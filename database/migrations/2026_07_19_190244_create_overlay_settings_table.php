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
        Schema::create('overlay_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eventner_id')->constrained()->cascadeOnDelete();
            $table->json('components')->nullable();
            $table->boolean('show_header')->default(true);
            $table->boolean('show_vote_leaderboard')->default(true);
            $table->boolean('show_kegiatan')->default(true);
            $table->boolean('show_footer')->default(true);
            $table->string('marquee_text')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overlay_settings');
    }
};
