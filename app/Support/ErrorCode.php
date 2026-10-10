<?php

namespace App\Support;

/**
 * Kode error publik untuk pelaporan: ER-XXXXXX (contoh ER-986734).
 * Alfabet tanpa 0/1/I/L/O agar tidak salah baca saat disebut lisan
 * atau diketik ulang user.
 */
class ErrorCode
{
    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    /**
     * Kode acak — untuk baris data uji / fallback.
     */
    public static function generate(): string
    {
        $alphabet = self::ALPHABET;
        $max = strlen($alphabet) - 1;
        $suffix = '';

        for ($i = 0; $i < 6; $i++) {
            $suffix .= $alphabet[random_int(0, $max)];
        }

        return 'ER-' . $suffix;
    }

    /**
     * Kode deterministik dari sidik jari: error yang sama (class+file+line)
     * selalu menghasilkan kode yang sama, jadi user cukup melaporkan satu
     * kode untuk semua kejadian error itu.
     */
    public static function fromFingerprint(string $fingerprint): string
    {
        $hex = sha1($fingerprint);
        $alphabet = self::ALPHABET;
        $n = strlen($alphabet);
        $suffix = '';

        for ($i = 0; $i < 6; $i++) {
            $suffix .= $alphabet[hexdec(substr($hex, $i * 2, 2)) % $n];
        }

        return 'ER-' . $suffix;
    }

    public static function isValid(string $code): bool
    {
        return preg_match('/^ER-[2-9A-HJ-NP-Z]{6}$/', $code) === 1;
    }
}
