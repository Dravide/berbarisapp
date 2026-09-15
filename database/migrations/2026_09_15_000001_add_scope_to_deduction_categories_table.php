<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pembedaan tegas antara dua jenis rubrik pengurangan:
     *
     * - 'category' (default, perilaku lama): menempel pada satu Kategori
     *   Penilaian dan hanya memotong kolom kategori itu.
     * - 'global': berlaku untuk semua tingkat lomba, tidak menempel pada
     *   kategori mana pun, dan memotong NILAI AKHIR di luar kolom kategori.
     *
     * Tidak bisa hanya mengandalkan assessment_category_id NULL sebagai
     * penanda, karena NULL sudah punya arti lain: baris lama yang belum
     * ditentukan targetnya (lihat migrasi 2026_06_18_110000). Kolom ini
     * membuat kedua arti itu bisa dibedakan query.
     */
    public function up(): void
    {
        Schema::table('deduction_categories', function (Blueprint $table) {
            $table->string('scope')->default('category')->after('assessment_category_id');
            $table->index(['eventner_id', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::table('deduction_categories', function (Blueprint $table) {
            $table->dropIndex(['eventner_id', 'scope']);
            $table->dropColumn('scope');
        });
    }
};
