<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * normalisasi_no_hp(): satu nomor, satu bentuk simpanan.
 *
 * Orang mengetik nomor dengan cara berbeda-beda. Kalau disimpan apa adanya,
 * "+62 812-3456-7890" dan "08123456789" jadi dua nomor berbeda di database,
 * dan admin yang menyalinnya ke WhatsApp harus menebak-nebak.
 */
class NoHpTest extends TestCase
{
    public function test_bentuk_umum_indonesia_disamakan()
    {
        $harusSama = [
            '08123456789',
            '0812 3456 789',
            '0812-3456-789',
            '(0812) 3456-789',
            '+62 812-3456-789',
            '+6281234567 89',
            '628123456789',
            '8123456789',
        ];

        foreach ($harusSama as $bentuk) {
            $this->assertSame(
                '08123456789',
                normalisasi_no_hp($bentuk),
                "Bentuk {$bentuk} tidak disamakan.",
            );
        }
    }

    public function test_spasi_dan_tanda_baca_dibuang()
    {
        $this->assertSame('081234567890', normalisasi_no_hp('  0812.3456.7890  '));
        $this->assertSame('081234567890', normalisasi_no_hp("0812\t3456\n7890"));
    }

    public function test_nomor_kosong_menjadi_null()
    {
        $this->assertNull(normalisasi_no_hp(null));
        $this->assertNull(normalisasi_no_hp(''));
        $this->assertNull(normalisasi_no_hp('   '));
        $this->assertNull(normalisasi_no_hp('--'));
    }

    /**
     * Nomor yang tidak dikenali dikembalikan apa adanya (hanya digit), bukan
     * dipaksa jadi bentuk yang salah — pengguna harus melihat kembali apa yang
     * ia ketik, lalu ditolak validasi di halamannya.
     */
    public function test_nomor_asing_tidak_dipaksa_jadi_08()
    {
        $this->assertSame('12345', normalisasi_no_hp('12345'));
        $this->assertSame('0211234567', normalisasi_no_hp('021-1234567'));
        $this->assertSame('12345678901', normalisasi_no_hp('12345678901'));
    }

    /** Nomor 62 yang panjangnya di luar kebiasaan tetap dikonversi. */
    public function test_awalan_62_tetap_dikonversi_apa_pun_panjangnya()
    {
        $this->assertSame('0812345678901', normalisasi_no_hp('+62 812 3456 78901'));
        $this->assertSame('08123', normalisasi_no_hp('628123'));
    }
}
