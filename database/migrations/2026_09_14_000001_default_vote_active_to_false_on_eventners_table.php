<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Voting jadi fitur opt-in: eventner baru tidak lagi otomatis mengaktifkan
 * voting. Sebelumnya kolom ini default true dan tak pernah di-set di titik
 * pembuatan akun, sehingga setiap event baru membuka voting tanpa jadwal
 * (vote_start/vote_end NULL = tanpa batas waktu).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->boolean('vote_active')->default(false)->change();
        });

        // Baris lama yang tak pernah menyentuh Pengaturan Vote punya
        // vote_start/vote_end NULL — itu jejak nilai default, bukan pilihan
        // penyelenggara. Matikan supaya tidak ikut terbuka diam-diam.
        DB::table('eventners')
            ->whereNull('vote_start')
            ->whereNull('vote_end')
            ->update(['vote_active' => false]);
    }

    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->boolean('vote_active')->default(true)->change();
        });
    }
};
