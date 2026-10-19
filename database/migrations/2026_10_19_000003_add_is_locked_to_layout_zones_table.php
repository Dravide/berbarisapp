<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kunci per zona: satu kotak yang sudah pas tak perlu ikut bisa tergeser.
 *
 * Kunci denah menahan seluruh gambar; kunci ini menahan satu kotak saja.
 * Kebutuhannya nyata di denah yang padat: arena dan pos penilaian sudah pas,
 * sementara DP 4 masih perlu digeser sedikit — dan mengunci seluruh denah
 * demi satu kotak yang belum selesai berarti tak bisa mengerjakan apa pun.
 *
 * Disimpan di barisnya sendiri, sama seperti kunci denah: yang dilindungi
 * kotaknya, bukan sesi orang yang kebetulan membukanya.
 *
 * Bawaannya false, jadi zona lama tak berubah kelakuannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('layout_zones', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('height_m');
        });
    }

    public function down(): void
    {
        Schema::table('layout_zones', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });
    }
};
