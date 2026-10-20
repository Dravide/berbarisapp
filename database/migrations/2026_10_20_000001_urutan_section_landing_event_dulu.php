<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Laman depan didahulukan EVENT: hero, event, tiket, vote, baru fitur dan
     * harga. Urutan section tersimpan di tabel settings, jadi default baru di
     * kode saja tidak cukup.
     *
     * Ternyata tidak cukup juga kalau cuma mengandalkan penyelipan otomatis di
     * `LandingPage::lengkapiUrutan()`: fungsi itu menghormati urutan relatif
     * yang tersimpan, jadi `features` dan `pricing` yang sudah ada di urutan
     * lama tetap berada di depan `eventners` — persis kebalikan dari yang
     * diminta. Barisnya harus ditulis ulang.
     *
     * Ditulis hanya kalau nilainya masih persis default lama. Kalau admin
     * pernah menyusun ulang section-nya sendiri, susunan itu miliknya dan
     * tidak boleh ditimpa migration.
     */
    private const ORDER = [
        'baru' => '["hero","eventners","ticket","vote","features","pricing","perbandingan","faq","team","sponsor","cta"]',
        'lama' => '["hero","features","pricing","eventners","ticket","faq","cta"]',
    ];

    private const ACTIVE = [
        'baru' => '{"hero":true,"eventners":true,"ticket":true,"vote":true,"features":true,"pricing":true,"perbandingan":true,"faq":true,"team":true,"sponsor":true,"cta":true}',
        'lama' => '{"hero":true,"features":true,"pricing":true,"eventners":true,"ticket":true,"faq":true,"cta":true}',
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
