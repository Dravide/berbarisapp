<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ukuran meter milik satu zona, lepas dari ukuran papan.
 *
 * Arena lomba kerap berukuran tetap — 15 × 15 m, 20 × 20 m — sementara papannya
 * cuma kanvas untuk menggambar. Tanpa kolom ini ukuran arena hanya bisa
 * disimpulkan dari persen papan, jadi panitia tak punya cara mengisi "arena
 * saya 15 meter" kecuali dengan mengutak-atik ukuran papan yang justru
 * menggambarkan lapangan, bukan arena.
 *
 * NULL berarti zona ini memang tak punya ukuran sendiri, dan ukurannya
 * dihitung dari papan seperti sebelumnya — zona lama tak berubah kelakuannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('layout_zones', function (Blueprint $table) {
            $table->decimal('width_m', 8, 2)->nullable()->after('h');
            $table->decimal('height_m', 8, 2)->nullable()->after('width_m');
        });
    }

    public function down(): void
    {
        Schema::table('layout_zones', function (Blueprint $table) {
            $table->dropColumn(['width_m', 'height_m']);
        });
    }
};
