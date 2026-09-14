<?php

namespace Tests\Feature;

use App\Models\Eventner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menu publik mengikuti saklar penyelenggara.
 *
 * Vote sudah lama punya saklar di /eventner/vote-settings, tapi menu Vote di
 * navigasi event selalu tampil — termasuk saat saklarnya mati. Halaman vote-nya
 * sendiri memang menolak (view "closed"), jadi pembeli menekan menu yang
 * mengarah ke halaman kosong. Sekarang menu Vote disembunyikan dengan pola
 * yang sama seperti Tiket.
 */
class FrontendNavGatingTest extends TestCase
{
    use RefreshDatabase;

    private function eventner(array $atribut = []): Eventner
    {
        return Eventner::factory()->create(array_merge([
            'status' => 'approved',
            'vote_active' => true,
        ], $atribut));
    }

    /** Item bottom-nav yang benar-benar dirender, dari data-nav-nya. */
    private function itemNav(string $html): array
    {
        $awal = strpos($html, 'aria-label="Navigasi utama"');
        $this->assertNotFalse($awal, 'Bottom nav tidak ditemukan.');

        $akhir = strpos($html, '</nav>', $awal);
        $nav = substr($html, $awal, $akhir - $awal);

        preg_match_all('/data-nav="([^"]+)"/', $nav, $cocok);

        return $cocok[1];
    }

    public function test_menu_vote_tampil_saat_saklar_menyala()
    {
        $eventner = $this->eventner(['vote_active' => true]);

        $this->assertContains('vote', $this->itemNav($this->get("/event/{$eventner->slug}")->getContent()));
    }

    public function test_menu_vote_hilang_saat_saklar_mati()
    {
        $eventner = $this->eventner(['vote_active' => false]);

        $this->assertNotContains(
            'vote',
            $this->itemNav($this->get("/event/{$eventner->slug}")->getContent()),
            'Vote dimatikan di /eventner/vote-settings — menunya tidak boleh muncul.'
        );
    }

    /** Menu lain tidak ikut hilang saat vote dimatikan. */
    public function test_menu_lain_tetap_ada_saat_vote_dimatikan()
    {
        $eventner = $this->eventner(['vote_active' => false]);

        $nav = $this->itemNav($this->get("/event/{$eventner->slug}")->getContent());

        $this->assertContains('info', $nav);
        $this->assertContains('peserta', $nav);
        $this->assertContains('hasil', $nav);
    }

    public function test_menu_tiket_mengikuti_saklarnya_sendiri()
    {
        $eventner = $this->eventner(['ticket_active' => false]);

        $this->assertNotContains(
            'tiket',
            $this->itemNav($this->get("/event/{$eventner->slug}")->getContent()),
            'Prasyarat: tiket tanpa harga memang tidak tampil.'
        );
    }

    /**
     * Tautan voting di footer ikut disembunyikan, bukan cuma bottom-nav.
     *
     * URL-nya bisa berbentuk subdomain (/vote) atau jalur slug
     * (/event/{slug}/vote), tergantung subdomain event — jadi yang diperiksa
     * href-nya lewat helper yang sama dengan view.
     */
    public function test_footer_tidak_menautkan_vote_saat_saklar_mati()
    {
        $eventner = $this->eventner(['vote_active' => false]);

        $html = $this->get("/event/{$eventner->slug}")->getContent();

        $this->assertStringNotContainsString(
            'href="' . e(event_url($eventner->fresh(), 'vote')) . '"',
            $html
        );
    }

    public function test_footer_menautkan_vote_saat_saklar_menyala()
    {
        $eventner = $this->eventner(['vote_active' => true]);

        $html = $this->get("/event/{$eventner->slug}")->getContent();

        $this->assertStringContainsString(
            'href="' . e(event_url($eventner->fresh(), 'vote')) . '"',
            $html
        );
    }
}
