<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uang muka (DP) biaya pendaftaran — pengaturan per tingkat lomba.
 *
 * `dp_amount` dan `dp_percent` sengaja nullable dan saling eksklusif: tingkat
 * yang keduanya kosong TIDAK menawarkan DP sama sekali, dan perilakunya persis
 * seperti sebelum migrasi ini. Fitur ini harus mati secara default — panitia
 * yang tidak pernah meminta DP tidak boleh tiba-tiba melihat pilihan bayar
 * separuh di portal pendaftarnya.
 *
 * `dp_amount` menang atas `dp_percent` bila keduanya terisi. Alasannya bukan
 * selera: nominal tetap bisa dicocokkan langsung dengan mutasi rekening,
 * sedangkan persentase menghasilkan angka berdesimal (mis. 33% dari Rp 500.000)
 * yang tidak pernah persis sama dengan yang ditransfer orang.
 *
 * `dp_days` = tenggat pelunasan, dihitung dari saat DP diverifikasi. Null =
 * tanpa tenggat. Ini tenggat PENANDA, bukan pembatal: lewat tenggat tidak
 * membatalkan pendaftaran (lihat Registration::lewatTenggatDp()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_categories', function (Blueprint $table) {
            $table->decimal('dp_amount', 12, 2)->nullable()->after('registration_fee');
            $table->unsignedTinyInteger('dp_percent')->nullable()->after('dp_amount');
            $table->unsignedSmallInteger('dp_days')->nullable()->after('dp_percent');
        });
    }

    public function down(): void
    {
        Schema::table('competition_categories', function (Blueprint $table) {
            $table->dropColumn(['dp_amount', 'dp_percent', 'dp_days']);
        });
    }
};
