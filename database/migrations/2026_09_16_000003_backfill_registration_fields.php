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
