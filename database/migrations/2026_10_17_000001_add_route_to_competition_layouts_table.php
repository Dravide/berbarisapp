<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alur gerak pasukan di atas denah.
 *
 * Satu alur per denah, disimpan sebagai satu kolom JSON berisi titik-titik
 * berurutan. Alur bukan tabel sendiri karena ia tak pernah dibaca sebagian:
 * editor menggambar seluruhnya, publik menampilkannya utuh, PDF mencetaknya
 * sekali. Memecahnya jadi baris-baris hanya menambah satu join tanpa manfaat.
 *
 * `route_points` NULL berarti denah ini memang belum punya alur — beda dari
 * alur kosong. Denah lama sesudah migrasi ini tetap tampil seperti sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_layouts', function (Blueprint $table) {
            $table->json('route_points')->nullable()->after('background_image');
            $table->boolean('route_visible')->default(true)->after('route_points');
        });
    }

    public function down(): void
    {
        Schema::table('competition_layouts', function (Blueprint $table) {
            $table->dropColumn(['route_points', 'route_visible']);
        });
    }
};
