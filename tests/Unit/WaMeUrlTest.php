<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * wa.me menolak awalan "0" dan "+": nomor admin yang bebas bentuknya harus
 * dinormalisasi sebelum ditempel di href, atau tautannya mati.
 */
class WaMeUrlTest extends TestCase
{
    public function test_awalan_nol_jadi_kode_negara()
    {
        $this->assertSame('https://wa.me/6281234567890', wa_me_url('0812-3456-7890'));
    }

    public function test_tanda_plus_dibuang()
    {
        $this->assertSame('https://wa.me/6281234567890', wa_me_url('+62 812-3456-7890'));
    }

    public function test_nomor_tanpa_kode_negara_dapat_62()
    {
        $this->assertSame('https://wa.me/6281234567890', wa_me_url('81234567890'));
    }

    public function test_sudah_internasional_tetap()
    {
        $this->assertSame('https://wa.me/6281234567890', wa_me_url('6281234567890'));
    }

    public function test_karakter_non_digit_dibuang()
    {
        $this->assertSame('https://wa.me/6281234567890', wa_me_url('(0812) 3456-7890'));
    }

    public function test_kosong_mengembalikan_null()
    {
        $this->assertNull(wa_me_url(''));
        $this->assertNull(wa_me_url('   '));
        $this->assertNull(wa_me_url(null));
    }
}
