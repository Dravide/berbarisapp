<?php

use App\Models\Eventner;
use App\Models\RegistrationField;
use Illuminate\Database\Migrations\Migration;

/**
 * Isi registration_fields untuk event yang sudah ada.
 *
 * Tanpa ini, event lama akan tampil dengan formulir kosong: form publik dan
 * portal magic link merender field dari tabel ini, dan tidak ada seeder yang
 * menjalankannya. Event baru terisi otomatis lewat ensureDefaults() saat
 * formulirnya pertama kali dibuka.
 *
 * Toggle lama (surat_tugas_required, kwitansi_required) diwarisi ke
 * is_required + is_active lewat RegistrationField::defaults().
 *
 * Idempoten: ensureDefaults() memakai firstOrCreate, jadi aman dijalankan ulang
 * dan tidak menimpa perubahan panitia.
 *
 * Urutan berkas penting: migrasi ini menulis lewat model, dan kolom yang
 * ditulisnya (`section`, `sub_fields`, `wilayah_level`) baru ada setelah
 * 2026_09_16_000004 dan 2026_09_18_000002. Dijalankan lebih awal, ia gagal di
 * basis data yang sudah punya event — "Unknown column 'wilayah_level'" — tapi
 * lolos di migrate:fresh karena saat itu belum ada event untuk diisi. Karena itu
 * namanya sengaja 2026_09_18_000003, setelah kedua penambah kolom itu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Eventner::query()->orderBy('id')->chunkById(100, function ($eventners) {
            foreach ($eventners as $eventner) {
                RegistrationField::ensureDefaults($eventner);
            }
        });
    }

    public function down(): void
    {
        // Sengaja tidak menghapus: field bisa sudah diubah panitia, dan
        // menghapusnya berarti kehilangan definisi formulir mereka.
    }
};
