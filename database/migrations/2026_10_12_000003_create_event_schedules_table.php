<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jadwal pertandingan — pertemuan per tingkat (+grup, +babak) di suatu
     * venue pada jam tertentu. Terpisah dari event_rundowns: rundown adalah
     * urutan acara global, jadwal adalah pertandingan yang bisa diikat ke
     * tempat dan babak.
     *
     * tanggal nullable: event satu hari tak perlu mengisinya — render
     * memakai eventners.tanggal sebagai hari.
     */
    public function up(): void
    {
        Schema::create('event_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eventner_id')->constrained('eventners')->cascadeOnDelete();
            $table->foreignId('competition_category_id')->constrained('competition_categories')->cascadeOnDelete();
            $table->foreignId('competition_group_id')->nullable()->constrained('competition_groups')->nullOnDelete();
            $table->foreignId('competition_round_id')->nullable()->constrained('competition_rounds')->nullOnDelete();
            $table->foreignId('eventner_venue_id')->nullable()->constrained('eventner_venues')->nullOnDelete();
            $table->string('title')->nullable();
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->date('tanggal')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['eventner_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_schedules');
    }
};
