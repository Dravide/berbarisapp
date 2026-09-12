<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tempat pelaksanaan lomba. Satu event boleh punya lebih dari satu tempat
     * (mis. LOBB di SMA 1, RUKIBRA di SMA 2), dan tiap tingkat lomba menunjuk
     * salah satunya lewat competition_categories.venue_id.
     */
    public function up(): void
    {
        Schema::create('eventner_venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eventner_id')->constrained('eventners')->cascadeOnDelete();
            $table->string('name');
            $table->string('alamat')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('google_maps_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventner_venues');
    }
};
