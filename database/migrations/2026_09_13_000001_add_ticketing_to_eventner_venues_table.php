<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tiket per tempat: kapasitas, harga, dan gerbang sendiri.
 *
 * Semua kolom nullable dengan sengaja — event yang cuma punya satu tempat
 * (dan tidak mau mengurus kuota/gerbang terpisah) tidak perlu mengisi apa pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventner_venues', function (Blueprint $table) {
            // null = tanpa batas (sejajar competition_categories.kuota).
            $table->unsignedInteger('ticket_kuota')->nullable();
            // null = pakai eventners.ticket_price sebagai harga fallback.
            $table->unsignedInteger('ticket_price')->nullable();
            // Token gerbang tempat ini. null = pakai eventners.checkin_token.
            $table->string('checkin_token', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('eventner_venues', function (Blueprint $table) {
            $table->dropUnique(['checkin_token']);
            $table->dropColumn(['ticket_kuota', 'ticket_price', 'checkin_token']);
        });
    }
};
