<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Laman utama dipangkas dari 12 blok jadi 7 (hero, fitur, harga, event,
     * tiket, FAQ, CTA). Urutan section tersimpan di tabel settings, jadi
     * default baru di kode saja tidak cukup — barisnya harus ikut ditulis,
     * kalau tidak halaman tetap merender section yang komponen Blade-nya
     * sudah dihapus dan langsung error.
     *
     * Ditulis hanya kalau nilainya masih persis default lama. Kalau admin
     * pernah menyusun ulang section-nya sendiri, susunan itu miliknya dan
     * tidak boleh ditimpa migration.
     */
    private const ORDER = [
        'lama' => '["hero","features","about","statistics","eventners","ticket","vote","schedule","testimonials","faq","gallery","cta","contact"]',
        'baru' => '["hero","features","pricing","eventners","ticket","faq","cta"]',
    ];

    private const ACTIVE = [
        'lama' => '{"hero":true,"features":true,"about":true,"statistics":true,"eventners":true,"ticket":true,"vote":true,"schedule":true,"testimonials":true,"faq":true,"gallery":true,"cta":true,"contact":true}',
        'baru' => '{"hero":true,"features":true,"pricing":true,"eventners":true,"ticket":true,"faq":true,"cta":true}',
    ];

    public function up(): void
    {
        $this->ganti('landing_sections_order', self::ORDER['lama'], self::ORDER['baru']);
        $this->ganti('landing_sections_active', self::ACTIVE['lama'], self::ACTIVE['baru']);
    }

    public function down(): void
    {
        $this->ganti('landing_sections_order', self::ORDER['baru'], self::ORDER['lama']);
        $this->ganti('landing_sections_active', self::ACTIVE['baru'], self::ACTIVE['lama']);
    }

    /** Tulis nilai $ke hanya bila isinya masih sama dengan $dari. */
    private function ganti(string $key, string $dari, string $ke): void
    {
        DB::table('settings')
            ->where('key', $key)
            ->where('value', $dari)
            ->update(['value' => $ke]);
    }
};
