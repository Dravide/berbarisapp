<?php

namespace App\Support;

use App\Models\Registration;

/**
 * Izin sementara mengunduh sertifikat, disimpan di session.
 *
 * Kenapa session dan bukan token di URL — ini keputusan keamanan, bukan
 * kemudahan:
 *
 * Jalur unduhan sertifikat yang sudah ada memakai `registrations.magic_token`
 * di path (/reg/{token}/certificate). Token itu BUKAN kredensial sertifikat: ia
 * membuka portal kontingen, tempat pendaftar bisa MENGUBAH data pasukannya —
 * nama anggota, nomor HP, berkas unggahan. Menaruhnya di laman publik yang
 * bisa dibuka siapa saja berarti membocorkan lebih dari sekadar sertifikat,
 * dan tautan yang tersalin ke grup WhatsApp akan tetap sah selamanya.
 *
 * Yang dipakai di sini justru lebih ketat: id registrasi hanya boleh diunduh
 * selama orang itu benar-benar baru saja mencari NISN-nya — dan izinnya
 * kedaluwarsa sendiri.
 *
 * Satu NISN biasanya menemukan beberapa sertifikat (satu per mata lomba per
 * pasukan), jadi yang disimpan adalah DAFTAR id, bukan satu id.
 */
class AksesUnduhSertifikat
{
    private const KUNCI_SESI = 'cert.grant';

    /** Menit izin berlaku sejak pencarian. */
    public const MASA_BERLAKU_MENIT = 30;

    /** Batas jumlah registrasi per pencarian. */
    public const BATAS_MAKS = 100;

    /**
     * Beri izin mengunduh seluruh registrasi yang ditemukan satu pencarian.
     *
     * Jumlahnya dibatasi: NISN yang terdaftar di lebih dari BATAS_MAKS baris
     * hampir pasti salah ketik atau nomor yang dipakai bersama, dan menerima
     * semuanya berarti satu sesi menyimpan izin untuk ratusan berkas.
     */
    public static function berikan(iterable $registrasi, string $nisn): void
    {
        $daftar = collect($registrasi)
            ->map(fn ($r) => $r instanceof Registration ? $r->id : (int) $r)
            ->unique()
            ->take(self::BATAS_MAKS)
            ->values()
            ->all();

        session([self::KUNCI_SESI => [
            'ids' => $daftar,
            'nisn' => $nisn,
            'sampai' => now()->addMinutes(self::MASA_BERLAKU_MENIT)->getTimestamp(),
        ]]);
    }

    /**
     * Izin yang masih berlaku, atau null bila belum pernah mencari / sudah
     * kedaluwarsa.
     *
     * Kedaluwarsa diperiksa saat DIBACA, bukan lewat tugas terjadwal: tidak
     * ada scheduler yang menjamin berjalan di setiap pemasangan, sedangkan
     * cap waktu di session selalu ada.
     *
     * @return array{ids: array<int, int>, nisn: string, sampai: int}|null
     */
    public static function berlaku(): ?array
    {
        $grant = session(self::KUNCI_SESI);

        if (! is_array($grant) || ! isset($grant['sampai'])) {
            return null;
        }

        if (now()->getTimestamp() > (int) $grant['sampai']) {
            self::lupakan();

            return null;
        }

        return [
            'ids' => array_map('intval', $grant['ids'] ?? []),
            'nisn' => (string) ($grant['nisn'] ?? ''),
            'sampai' => (int) $grant['sampai'],
        ];
    }

    /** Boleh tidak registrasi ini diunduh pada permintaan sekarang. */
    public static function bolehUnduh(int $registrationId): bool
    {
        $grant = self::berlaku();

        return $grant !== null && in_array($registrationId, $grant['ids'], true);
    }

    /** Buang izinnya — dipakai saat pencarian gagal supaya izin lama tidak tertinggal. */
    public static function lupakan(): void
    {
        session()->forget(self::KUNCI_SESI);
    }
}
