<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JudgeAccessCardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Eventner $eventner;
    private Judge $judge;
    private CompetitionCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
            'nama_event' => 'Lomba Baris Berbaris 2026',
            'diselenggarakan_oleh' => 'Yayasan Contoh',
        ]);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'LOBB',
        ]);
        $this->category = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
            'name' => 'U13',
        ]);

        $this->judge = Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Budi Santoso',
        ]);

        $rubric = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Ketepatan Gerakan',
            'competition_category_id' => $this->category->id,
        ]);
        $this->judge->assessmentCategories()->attach($rubric->id);

        config(['app.entry_host' => 'entry.berbaris.test']);
    }

    /** Kartu satu juri: PDF 2 lembar berhasil diunduh. */
    public function test_kartu_akses_satu_juri_berhasil_diunduh()
    {
        $response = $this->actingAs($this->user)
            ->get(route('eventner.judges.kartu-akses', $this->judge->id));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('Budi_Santoso', $response->headers->get('content-disposition'));
    }

    /** Tanpa judge_id: kartu semua juri, satu juri per dua halaman. */
    public function test_kartu_akses_semua_juri_berhasil_diunduh()
    {
        Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Siti Aminah']);

        $response = $this->actingAs($this->user)
            ->get(route('eventner.judges.kartu-akses'));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('Semua_Juri', $response->headers->get('content-disposition'));
    }

    /**
     * Isi kartu: nama juri, event, tugas penilaian, dan instruksi menutup QR
     * dengan lembar 1. QR-nya sendiri ada di lembar 2.
     */
    public function test_kartu_memuat_identitas_juri_dan_instruksi_menutup_qr()
    {
        $html = $this->renderCard();

        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringContainsString('Lomba Baris Berbaris 2026', $html);
        $this->assertStringContainsString('Ketepatan Gerakan', $html);
        $this->assertStringContainsString('LOBB — U13', $html);

        // Instruksi inti yang diminta: tutup QR dengan lembar 1.
        $this->assertStringContainsString('Simpan lembar ini di atas lembar QR', $html);
        $this->assertStringContainsString('Lembar 2 halaman berikutnya memuat QR akses', $html);

        $this->assertStringContainsString('Lembar 1 dari 2', $html);
        $this->assertStringContainsString('Pindai QR ini untuk membuka halaman penilaian', $html);
    }

    /**
     * Lembar 2 memuat QR yang menunjuk ke host entry, bukan host dashboard.
     * Harus PNG — dompdf tidak bisa menggambar SVG, dan QRCode tanpa opsi
     * menghasilkan SVG secara default.
     */
    public function test_lembar_qr_memuat_png_dan_url_host_entry()
    {
        $html = $this->renderCard();

        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringNotContainsString('svg', strtolower($html));
        $this->assertStringContainsString(judge_entry_url($this->judge->access_token), $html);
        $this->assertStringContainsString('entry.berbaris.test/juri/' . $this->judge->access_token, $html);
    }

    /** Juri milik event lain tidak boleh dibaca lewat {judge}. */
    public function test_juri_event_lain_ditolak()
    {
        $otherUser = User::factory()->eventner()->create(['is_active' => true]);
        $otherEventner = Eventner::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'approved',
        ]);
        $foreign = Judge::create([
            'eventner_id' => $otherEventner->id,
            'name' => 'Juri Asing',
        ]);

        $this->actingAs($this->user)
            ->get(route('eventner.judges.kartu-akses', $foreign->id))
            ->assertNotFound();
    }

    /** Eventner tanpa juri: tidak ada kartu untuk dicetak. */
    public function test_eventner_tanpa_juri_ditolak()
    {
        $emptyUser = User::factory()->eventner()->create(['is_active' => true]);
        Eventner::factory()->create([
            'user_id' => $emptyUser->id,
            'status' => 'approved',
        ]);

        $this->actingAs($emptyUser)
            ->get(route('eventner.judges.kartu-akses'))
            ->assertNotFound();
    }

    public function test_tamu_tidak_bisa_mengunduh_kartu()
    {
        $this->get(route('eventner.judges.kartu-akses'))->assertRedirect();
    }

    /** Halaman juri menyediakan tombol unduh kartu. */
    public function test_halaman_juri_menyediakan_unduh_kartu_akses()
    {
        $response = $this->actingAs($this->user)->get(route('eventner.judges.index'));

        $response->assertOk()
            ->assertSee('Kartu Akses Semua Juri')
            ->assertSee(route('eventner.judges.kartu-akses', $this->judge->id), false);
    }

    /** Render view kartu langsung — dompdf tidak perlu jalan untuk cek isi. */
    private function renderCard(): string
    {
        return view('eventner.judge.pdf_kartu_akses', [
            'eventner' => $this->eventner,
            'judges' => Judge::where('eventner_id', $this->eventner->id)
                ->with('assessmentCategories.competitionCategory.parent')
                ->get(),
        ])->render();
    }
}
