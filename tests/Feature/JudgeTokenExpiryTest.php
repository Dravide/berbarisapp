<?php

namespace Tests\Feature;

use App\Models\Eventner;
use App\Models\Judge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Masa berlaku link/QR tablet juri.
 *
 * Token juri tidak punya masa berlaku sendiri: tanpa batas, QR lomba yang
 * sudah selesai tetap bisa dinilai bertahun-tahun, dan penutupnya cuma disiplin
 * panitia menekan "Ganti Token". Batasnya diturunkan dari tanggal event supaya
 * tidak ada tanggal baru yang perlu dicetak ulang di kartu akses.
 *
 * Dua hal yang dijaga ketat di sini:
 *
 *  1. Batas jatuh di AKHIR hari, bukan awal. Lomba sering rampung lewat tengah
 *     malam; mengunci tepat saat tanggal berganti akan memutus juri di tengah
 *     penilaian terakhir.
 *  2. tanggal_akhir kosong JATUH KE tanggal + 7 hari, bukan ke "tanpa batas".
 *     Di DB nyata hampir semua event belum mengisi tanggal_akhir, jadi
 *     memperlakukannya sebagai tanpa batas membuat fitur ini tidak menutup apa
 *     pun untuk sebagian besar event.
 */
class JudgeTokenExpiryTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private Judge $judge;

    protected function setUp(): void
    {
        parent::setUp();

        // Waktu dipaku: batas tanggal adalah urusan perbandingan hari, dan tes
        // yang bergantung jam berjalan akan gagal sendiri tengah malam.
        Carbon::setTestNow('2026-09-26 10:00:00');

        $user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'tanggal' => '2026-09-26',
            'tanggal_akhir' => null,
        ]);

        $this->judge = Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Akeng',
        ]);

        config(['app.entry_host' => 'entry.berbaris.test']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** Request di host entry milik platform (config app.entry_host). */
    private function tablet(string $path)
    {
        return $this->get('http://'.judge_entry_host().$path);
    }

    private function juriTab()
    {
        return $this->tablet('/juri/'.$this->judge->access_token);
    }

    private function setTanggal(?string $tanggal, ?string $akhir = null): void
    {
        $this->eventner->update([
            'tanggal' => $tanggal,
            'tanggal_akhir' => $akhir,
        ]);
    }

    // ---------- tanggal_akhir diisi: batas = akhir hari terakhir ----------

    public function test_hari_terakhir_event_masih_bisa_dibuka()
    {
        $this->setTanggal('2026-09-25', '2026-09-26');

        $this->juriTab()->assertOk();
    }

    /** Akhir hari, bukan awal: jam 23:00 di hari terakhir masih sah. */
    public function test_lewat_tengah_malam_di_hari_terakhir_masih_bisa_dibuka()
    {
        $this->setTanggal('2026-09-26', '2026-09-26');
        Carbon::setTestNow('2026-09-26 23:30:00');

        $this->juriTab()->assertOk();
    }

    public function test_sehari_setelah_hari_terakhir_ditolak()
    {
        $this->setTanggal('2026-09-25', '2026-09-26');
        Carbon::setTestNow('2026-09-27 00:30:00');

        $this->juriTab()->assertNotFound();
    }

    public function test_event_lama_jauh_ditolak()
    {
        $this->setTanggal('2025-03-01', '2025-03-02');

        $this->juriTab()->assertNotFound();
    }

    // ---------- tanggal_akhir kosong: jatuh ke tanggal + 7 hari ----------

    public function test_tanpa_tanggal_akhir_pakai_tenggat_tujuh_hari()
    {
        $this->setTanggal('2026-09-20');

        // Hari ke-6 sesudah tanggal: masih di dalam tenggat.
        Carbon::setTestNow('2026-09-26 10:00:00');
        $this->juriTab()->assertOk();
    }

    public function test_tenggat_tujuh_hari_juga_jatuh_di_akhir_hari()
    {
        $this->setTanggal('2026-09-20');
        Carbon::setTestNow('2026-09-26 23:59:00');

        // Hari ke-6 (20 + 6 = 26) — masih hari terakhir tenggat.
        $this->juriTab()->assertOk();
    }

    public function test_lewat_tenggat_tujuh_hari_ditolak()
    {
        $this->setTanggal('2026-09-20');
        Carbon::setTestNow('2026-09-27 00:01:00');

        $this->juriTab()->assertNotFound();
    }

    /** Semua event uji di DB nyata belum punya tanggal_akhir — inilah jalur yang paling sering dipakai. */
    public function test_event_dengan_tanggal_saja_tetap_punya_batas()
    {
        $this->setTanggal('2026-09-26');

        $this->assertNotNull(
            $this->eventner->fresh()->judgeAccessExpiresAt(),
            'Event bertanggal tanpa tanggal_akhir tidak boleh berarti tanpa batas.'
        );
    }

    // ---------- tanpa tanggal sama sekali ----------

    /**
     * Tanpa tanggal, tak ada batas yang bisa dihitung.
     *
     * Cabang ini tak bisa diuji lewat HTTP: kolomnya NOT NULL, jadi request
     * selalu menemukan event bertanggal. Yang diuji di sini atributnya langsung
     * — perannya jaring pengaman bagi baris yang entah bagaimana tersimpan
     * tanpa tanggal, dan pemanggilnya (halaman juri) memilih membiarkan lewat
     * ketimbang mengunci event yang panitianya belum mengisi apa pun.
     */
    public function test_tanpa_tanggal_tidak_ada_batas()
    {
        $this->eventner->tanggal = null;
        $this->eventner->tanggal_akhir = null;

        $this->assertNull($this->eventner->judgeAccessExpiresAt());
    }

    // ---------- perilaku penolakan ----------

    /**
     * Token kedaluwarsa menjawab 404 yang sama dengan token ngawur.
     *
     * Kalau kedaluwarsa dijawab 403, penebak token belajar bahwa tokennya BENAR
     * dan hanya perlu menunggu — informasi paling berguna yang bisa mereka
     * dapat. Alasan sebenarnya masuk log, bukan ke layar.
     */
    public function test_token_kedaluwarsa_tidak_bisa_dibedakan_dari_token_ngawur()
    {
        $this->setTanggal('2025-03-01', '2025-03-02');

        $kedaluwarsa = $this->juriTab();
        $ngawur = $this->tablet('/juri/token-yang-tidak-pernah-ada');

        $kedaluwarsa->assertNotFound();
        $ngawur->assertNotFound();
        $this->assertSame($ngawur->getStatusCode(), $kedaluwarsa->getStatusCode());
    }

    /** Penolakan dicatat: panitia butuh jejak saat juri mengeluh tak bisa masuk. */
    public function test_penolakan_dicatat_di_log()
    {
        $this->setTanggal('2025-03-01', '2025-03-02');

        Log::spy();

        $this->juriTab()->assertNotFound();

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($pesan, $konteks = []) => str_contains($pesan, 'melewati batas tanggal')
                && ($konteks['judge_id'] ?? null) === $this->judge->id);
    }

    /** Token yang masih berlaku tidak menghasilkan catatan penolakan. */
    public function test_token_berlaku_tidak_mencatat_penolakan()
    {
        $this->setTanggal('2026-09-26', '2026-09-27');

        Log::spy();

        $this->juriTab()->assertOk();

        Log::shouldNotHaveReceived('warning');
    }

    // ---------- batas tidak bergeser kalau tanggal_akhir salah isi ----------

    /**
     * tanggal_akhir lebih tua dari tanggal tidak memperpanjang akses.
     *
     * Kalau tanggal_akhir dipakai tanpa syarat, salah isi seperti ini membuat
     * akses mati lebih cepat dari tanggal lomba — juri terkunci di hari-H, dan
     * panitia tak punya cara tahu sebabnya selain membaca log. Jatuh ke tanggal
     * + 7 hari selalu lebih longgar daripada tanggal_akhir yang lebih tua,
     * sehingga tidak ada perpanjangan diam-diam.
     */
    public function test_tanggal_akhir_lebih_tua_dari_tanggal_tidak_memperpendek_akses()
    {
        $this->setTanggal('2026-09-26', '2026-09-01');

        $batas = $this->eventner->fresh()->judgeAccessExpiresAt();

        $this->assertTrue(
            $batas->gte(Carbon::parse('2026-10-02 23:59:59')),
            'Batas harus paling tidak sepanjang tanggal + 7 hari.'
        );
        $this->juriTab()->assertOk();
    }

    // ---------- pencabutan token lama tetap berlaku ----------

    /** Batas tanggal melengkapi "Ganti Token", bukan menggantikannya. */
    public function test_ganti_token_tetap_mencabut_akses_segera()
    {
        $this->setTanggal('2026-09-26', '2026-09-27');

        $lama = $this->judge->access_token;
        $this->judge->update(['access_token' => \Illuminate\Support\Str::random(16)]);

        $this->tablet('/juri/'.$lama)->assertNotFound();
        $this->juriTab()->assertOk();
    }
}
