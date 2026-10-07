<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gerbang halaman entry nilai panitia (host entry, path /panitia/{token}).
 *
 * Dua kolom, dua peran berbeda:
 *  - panitia_token: identitas event di URL. Unik, karena satu token harus
 *    menunjuk tepat satu event; kalau tidak, halaman tidak tahu milik siapa.
 *  - panitia_pin: izin mengetik. TIDAK unik dan tidak boleh — PIN hanya 6
 *    digit, tabrakan antar event tidak apa-apa karena token yang membedakan.
 *
 * Sengaja tidak memakai ulang checkin_pin: itu PIN gerbang tiket, beda orang
 * dan beda masa berlaku. Menggabungkannya berarti menekan satu nilai untuk dua
 * keperluan, dan mengganti salah satunya diam-diam mematikan yang lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->string('panitia_token', 48)->nullable()->unique()->after('checkin_token');
            $table->string('panitia_pin', 6)->nullable()->after('panitia_token');
        });
    }

    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dropUnique(['panitia_token']);
            $table->dropColumn(['panitia_token', 'panitia_pin']);
        });
    }
};
