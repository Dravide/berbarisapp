<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kunci denah: menahan gambar yang sudah selesai supaya tak tergeser.
 *
 * Papan yang sudah pas gampang rusak tanpa sengaja — satu seretan kecil di
 * kanvas, atau satu jendela yang terbuka lebar di ponsel, dan arena bergeser
 * tanpa ada yang menyadarinya sampai denahnya dicetak. Kuncinya disimpan di
 * barisnya, bukan di sesi editor: yang dilindungi denahnya, bukan sesi orang
 * yang kebetulan membukanya. Kalau dikunci di sesi saja, memuat ulang halaman
 * membuka kuncinya kembali dan denah yang "terkunci" tetap bisa tergeser.
 *
 * Bawaannya false, jadi denah lama tak berubah kelakuannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_layouts', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('competition_layouts', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });
    }
};
