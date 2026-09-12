<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu tiket = satu tempat.
 *
 * nullOnDelete: menghapus tempat tidak boleh menghapus riwayat penjualan —
 * tiket lama hanya kehilangan tempatnya, lalu lolos check-in tanpa verifikasi
 * gerbang (perilaku tiket event-wide).
 *
 * venue_id null = tiket berlaku di semua gerbang. Inilah yang membuat event
 * produksi tanpa tempat tetap berjalan persis seperti sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('venue_id')->nullable()
                ->constrained('eventner_venues')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('venue_id');
        });
    }
};
