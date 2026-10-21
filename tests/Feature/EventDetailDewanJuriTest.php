<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Judge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kartu "Dewan Juri" di halaman detail event publik.
 *
 * Sebelumnya kartu ini menggambar satu chip per RUBRIK juri. Rubrik menempel
 * pada seri peserta, bukan pada juri, jadi di event nyata daftarnya kosong dan
 * chip-nya berakhir sebagai baris tanpa isi — nama juri telanjang tanpa satu
 * pun keterangan. Sekarang tugasnya dibaca dari competition_group_judge, sumber
 * yang sama dengan tablet juri dan modal penugasan panitia.
 */
class EventDetailDewanJuriTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;
    private CompetitionCategory $induk;
    private CompetitionCategory $tingkat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventner = Eventner::factory()->create(['status' => 'approved']);

        $this->induk = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'PBB Putri',
        ]);

        $this->tingkat = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->induk->id,
            'name' => 'Regu Inti',
        ]);
    }

    private function juri(string $nama, ?string $foto = null): Judge
    {
        return Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => $nama,
            'photo' => $foto,
        ]);
    }

    /** Satu baris penugasan; jalur tulis sebenarnya adalah CompetitionGroup::syncJudges(). */
    private function tugaskan(Judge $juri, string $scope = 'level'): void
    {
        DB::table('competition_group_judge')->insert([
            'judge_id' => $juri->id,
            'competition_category_id' => $this->tingkat->id,
            'scope' => $scope,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Tugas juri ditulis "Induk — Tingkat", dari pivot, bukan dari rubrik.
     *
     * Tes ini yang membedakan kartu baru dari yang lama: dengan rubrik kosong —
     * keadaan event sungguhan — kartu lama hanya mencetak nama.
     */
    public function test_tugas_juri_dibaca_dari_penugasan_bukan_rubrik()
    {
        $this->tugaskan($this->juri('Ahmad Sudrajat'));

        $this->get("/event/{$this->eventner->slug}")
            ->assertStatus(200)
            ->assertSee('Dewan Juri', false)
            ->assertSee('Ahmad Sudrajat', false)
            ->assertSee('PBB Putri — Regu Inti', false)
            ->assertSee('1 Juri', false);
    }

    /**
     * Juri tanpa penugasan tidak dibiarkan berbaris kosong: barisnya menulis
     * "Penilai" dengan huruf miring.
     *
     * Kalimat kosong terbaca seperti render yang gagal — pembaca tak punya cara
     * membedakan "belum ditugaskan" dari "halamannya rusak".
     */
    public function test_juri_tanpa_penugasan_menulis_penilai()
    {
        $this->juri('Tanpa Tugas');

        $this->get("/event/{$this->eventner->slug}")
            ->assertStatus(200)
            ->assertSee('Tanpa Tugas', false)
            ->assertSee('Penilai', false);
    }

    /**
     * Urutannya A–Z, bukan urutan insert.
     *
     * Tabel `judges` tak punya sort_order, jadi urutan bawaan berbeda tiap kali
     * data disemai — dan daftar juri yang berubah-ubah antar halaman terbaca
     * seperti data yang tak bisa dipercaya.
     */
    public function test_juri_diurutkan_a_sampai_z()
    {
        $this->juri('Citra Dewi');
        $this->juri('Ahmad Sudrajat');
        $this->juri('Budi Santoso');

        $html = $this->get("/event/{$this->eventner->slug}")->assertStatus(200)->getContent();

        $posisi = fn (string $nama) => strpos($html, $nama);

        $this->assertLessThan($posisi('Budi Santoso'), $posisi('Ahmad Sudrajat'));
        $this->assertLessThan($posisi('Citra Dewi'), $posisi('Budi Santoso'));
    }

    /**
     * Juri tanpa foto mendapat monogram dua inisial, bukan bidang kosong.
     *
     * Bidang potret yang menganga saat fotonya tak ada membuat dewan tanpa foto
     * terbaca seperti daftar gambar yang gagal dimuat — persis masalah yang
     * bentuk kartu ini selesaikan.
     */
    public function test_juri_tanpa_foto_mendapat_monogram_dua_inisial()
    {
        $this->juri('Ahmad Sudrajat');

        $this->get("/event/{$this->eventner->slug}")
            ->assertStatus(200)
            ->assertSee('>AS<', false);
    }

    /** Juri berfoto memakai gambarnya, dan tidak dibuatkan monogram. */
    public function test_juri_berfoto_memakai_gambar_bukan_monogram()
    {
        $this->juri('Ahmad Sudrajat', 'judges/foto.jpg');

        $this->get("/event/{$this->eventner->slug}")
            ->assertStatus(200)
            ->assertSee('storage/judges/foto.jpg', false)
            ->assertDontSee('>AS<', false);
    }

    /**
     * Bentuknya ditentukan LEBAR layar, bukan jumlah juri.
     *
     * Ambang lamanya `count() > 6`, dan itu bocor tepat di rentang yang paling
     * butuh penjagaan: `auto-fit` sudah menurunkan kolomnya sendiri saat kartu
     * potret tak muat, jadi pada 820 px delapan juri masih tiga kolom (672 px)
     * sementara pada 800 px — dua puluh piksel lebih sempit — ia jatuh ke dua
     * kolom dan tingginya berlipat jadi 1136 px. Ambang yang membaca jumlah tak
     * pernah menyala di situ.
     *
     * Kedua bentuk ikut ter-render: server tak tahu lebar layar pembaca, jadi
     * CSS yang memilih. Yang dijaga di sini adalah pilihannya, bukan jumlah
     * simpangannya — dan `lg:hidden` / `hidden lg:block` adalah satu-satunya
     * yang menentukan bentuk mana yang terlihat.
     */
    public function test_bentuk_juri_dipilih_oleh_lebar_layar_bukan_jumlah_juri()
    {
        foreach (range(1, 8) as $i) {
            $this->juri("Juri Ke{$i}");
        }

        $html = $this->get("/event/{$this->eventner->slug}")->assertStatus(200)->getContent();

        $this->assertStringContainsString('8 Juri', $html);

        // Baris kompak di bawah `lg`, potret di atasnya — dan tak ada bentuk
        // ketiga yang menyala di rentang lain.
        $this->assertStringContainsString('lg:hidden', $html);
        $this->assertStringContainsString('hidden lg:block', $html);

        // Delapan juri tetap delapan, di kedua bentuk: tak ada yang
        // disembunyikan di balik tombol. (Keduanya ter-render di HTML yang
        // sama — 2 kemunculan per nama — jadi yang dituntut adalah
        // kehadirannya, bukan jumlahnya.)
        foreach (range(1, 8) as $i) {
            $this->assertStringContainsString("Juri Ke{$i}", $html);
        }

        $this->assertStringNotContainsString('Lihat semua juri', $html);
    }

    /**
     * Dewan kecil tidak diperlakukan berbeda dari dewan besar.
     *
     * Sebelumnya dua juri dan delapan juri memakai bentuk yang berbeda di
     * ponsel — yang satu potret, yang satu baris — padahal layarnya sama-sama
     * sempit. Sekarang aturannya satu: lebar.
     */
    public function test_dewan_kecil_memakai_aturan_lebar_yang_sama()
    {
        $this->juri('Ahmad Sudrajat');
        $this->juri('Budi Santoso');

        $html = $this->get("/event/{$this->eventner->slug}")->assertStatus(200)->getContent();

        $this->assertStringContainsString('lg:hidden', $html);
        $this->assertStringContainsString('hidden lg:block', $html);
        $this->assertStringNotContainsString('Lihat semua juri', $html);
    }

    /** Event tanpa juri tidak menampilkan kartu kosong. */
    public function test_event_tanpa_juri_tidak_menampilkan_kartu()
    {
        $this->get("/event/{$this->eventner->slug}")
            ->assertStatus(200)
            ->assertDontSee('Dewan Juri', false)
            ->assertDontSee('0 Juri', false);
    }
}
