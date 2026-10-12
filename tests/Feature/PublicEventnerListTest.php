<?php

namespace Tests\Feature;

use App\Models\Eventner;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tombol mengapung WhatsApp di layout landing + halaman daftar penyelenggara
 * (/eventner) sebagai tujuan "Lihat Semua Penyelenggara" di landing.
 */
class PublicEventnerListTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_daftar_penyelenggara_loads()
    {
        Eventner::factory(3)->create(['status' => 'approved']);

        $this->get('/eventner')->assertStatus(200);
    }

    /** Landing menampilkan enam kartu teratas; sisanya hidup di /eventner. */
    public function test_daftar_lengkap_menampilkan_semua_penyelenggara()
    {
        Eventner::factory()->create(['status' => 'approved', 'nama_event' => 'Lomba A']);
        Eventner::factory()->create(['status' => 'approved', 'nama_event' => 'Lomba B']);

        $html = $this->get('/eventner')->getContent();

        $this->assertStringContainsString('Lomba A', $html);
        $this->assertStringContainsString('Lomba B', $html);
    }

    /** Pending tak punya halaman publik — kartunya akan menautkan ke 404. */
    public function test_daftar_menyembunyikan_event_belum_aktif()
    {
        Eventner::factory()->pending()->create(['nama_event' => 'Lomba Belum Disetujui']);

        $this->get('/eventner')->assertDontSee('Lomba Belum Disetujui', false);
    }

    public function test_daftar_kekosongan_menampilkan_pesan()
    {
        $html = $this->get('/eventner')->getContent();

        $this->assertStringContainsString('Belum ada penyelenggara yang terdaftar.', $html);
    }

    /** Halaman daftar memakai kartu yang sama dengan landing. */
    public function test_daftar_memakai_kartu_penyelenggara_yang_sama()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'tanggal' => now()->subMonths(2)->toDateString(),
        ]);

        $html = $this->get('/eventner')->getContent();

        $this->assertStringContainsString('Terlaksana', $html);
        $this->assertStringContainsString('card-past', $html);
        $this->assertTrue($eventner->exists);
    }

    /** Urutan terbaru dulu: tanggal pelaksanaan paling jauh (paling baru) di atas. */
    public function test_daftar_mengurutkan_terbaru_di_atas()
    {
        Eventner::factory()->create([
            'status' => 'approved',
            'nama_event' => 'Lomba Dekat',
            'tanggal' => now()->addDays(3)->toDateString(),
        ]);
        Eventner::factory()->create([
            'status' => 'approved',
            'nama_event' => 'Lomba Jauh',
            'tanggal' => now()->addMonth()->toDateString(),
        ]);

        $html = $this->get('/eventner')->getContent();

        $posisiJauh = strpos($html, 'Lomba Jauh');
        $posisiDekat = strpos($html, 'Lomba Dekat');
        $this->assertNotFalse($posisiJauh);
        $this->assertNotFalse($posisiDekat);
        $this->assertLessThan($posisiDekat, $posisiJauh, 'Tanggal paling baru harus tampil sebelum yang lebih dekat.');
    }

    /** Pagination nyata: 16 per halaman, halaman kedua memuat sisa event. */
    public function test_daftar_memiliki_halaman_kedua()
    {
        // Nama terurut: 18 event bernomor, halaman 2 harus memuat Lomba 17.
        // Tanggal acak factory bisa menyelipkan Lomba 17 ke halaman 1, jadi
        // urutan tanggalnya dikunci ikut nomor.
        Eventner::factory()->count(18)
            ->sequence(fn ($seq) => [
                'nama_event' => 'Lomba ' . ($seq->index + 1),
                'tanggal' => now()->addDays($seq->index + 1)->toDateString(),
            ])
            ->create(['status' => 'approved']);
        $response = $this->get('/eventner?page=2');

        $response->assertStatus(200);
        // Livewire merender link pagination sebagai atribut wire:click, bukan
        // href biasa — cukup pastikan halaman 2 memuat sisa 2 event. Urutan
        // turun: halaman 1 = Lomba 18…3, halaman 2 = Lomba 2, Lomba 1.
        $this->assertStringContainsString('Lomba 1<', $response->getContent());
        $this->assertStringContainsString('Lomba 2<', $response->getContent());
        $this->assertStringNotContainsString('Lomba 17', $response->getContent());
    }

    /** Tombol "Lihat Semua" hanya tampil saat section-nya menyala dan ada data. */
    public function test_landing_menampilkan_tautan_lihat_semua()
    {
        Eventner::factory()->create(['status' => 'approved']);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString(route('public.eventners'), $html);
        $this->assertStringContainsString('Lihat Semua Penyelenggara', $html);
    }

    /** Footer ikut menautkan halaman daftar. */
    public function test_footer_landing_menautkan_halaman_penyelenggara()
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString(route('public.eventners'), $html);
    }

    /**
     * Tombol mengapung WhatsApp dibaca dari setting landing_contact — nomor
     * bebas dari admin (0812…, +62…) dinormalisasi jadi bentuk wa.me.
     */
    public function test_tombol_mengapung_whatsapp_tampil_dengan_nomor_ternormalisasi()
    {
        Setting::set('landing_contact', json_encode([
            'phone' => '0812-3456-7890',
            'email' => 'hi@example.com',
            'address' => 'Bandung',
        ]));

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('wa.me/6281234567890', $html);
        $this->assertStringContainsString('brand-whatsapp', $html);
    }

    /** Tanpa nomor, tombolnya hilang — jangan tautan mati wa.me/ kosong. */
    public function test_tombol_mengapung_hilang_tanpa_nomor_whatsapp()
    {
        Setting::set('landing_contact', json_encode([
            'phone' => '',
            'email' => 'hi@example.com',
            'address' => 'Bandung',
        ]));

        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString('wa.me/', $html);
        $this->assertStringNotContainsString('brand-whatsapp', $html);
    }

    /** Tombol mengapung juga hidup di halaman daftar penyelenggara. */
    public function test_tombol_mengapung_ikut_di_halaman_daftar()
    {
        Setting::set('landing_contact', json_encode([
            'phone' => '0812-3456-7890',
            'email' => 'hi@example.com',
            'address' => 'Bandung',
        ]));

        $this->get('/eventner')->assertSee('wa.me/6281234567890', false);
    }
}
