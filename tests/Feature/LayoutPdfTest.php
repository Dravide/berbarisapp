<?php

namespace Tests\Feature;

use App\Models\CompetitionLayout;
use App\Models\Eventner;
use App\Models\LayoutZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PDF denah: berkasnya benar-benar jadi, dan kertasnya mengikuti papan.
 *
 * Orientasi bukan soal selera — denah 40 × 22 m di kertas potret jadi selebar
 * kolom koran dan namanya tak terbaca lagi.
 */
class LayoutPdfTest extends TestCase
{
    use RefreshDatabase;

    private function panitia(): array
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'plan' => 'paid',
            'trial_ends_at' => null,
        ]);

        $this->actingAs($user);

        return [$eventner];
    }

    public function test_pdf_denah_terunduh()
    {
        [$eventner] = $this->panitia();
        $denah = CompetitionLayout::factory()->create(['eventner_id' => $eventner->id]);
        LayoutZone::factory()->dp(1)->create(['competition_layout_id' => $denah->id]);

        $respons = $this->get(route('eventner.denah.pdf', $denah->id));

        $respons->assertOk();
        $respons->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith(
            '%PDF',
            $respons->getContent(),
            'Berkas yang diunduh harus benar-benar PDF.'
        );
    }

    /** Nama unduhannya menyandera nama denah supaya berkas dua kategori beda. */
    public function test_nama_berkas_pdf_memakai_nama_denah()
    {
        [$eventner] = $this->panitia();
        $denah = CompetitionLayout::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'Denah PBB SD',
        ]);
        LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $respons = $this->get(route('eventner.denah.pdf', $denah->id));

        $this->assertStringContainsString(
            'denah-Denah-PBB-SD.pdf',
            $respons->headers->get('content-disposition') ?? ''
        );
    }

    /**
     * Papan memanjang ke samping → kertas lanskap.
     *
     * Dipaksa potret, denah 40 × 22 m menyusut jadi sepertiga halaman.
     */
    public function test_papan_memanjang_ke_samping_memakai_lanskap()
    {
        $denah = CompetitionLayout::factory()->make(['ground_width' => 40, 'ground_height' => 22]);

        $this->assertSame(['a4', 'landscape'], $denah->kertasPdf);
    }

    /** Papan memanjang ke belakang → kertas potret. */
    public function test_papan_memanjang_ke_belakang_memakai_potret()
    {
        $denah = CompetitionLayout::factory()->make(['ground_width' => 22, 'ground_height' => 40]);

        $this->assertSame(['a4', 'portrait'], $denah->kertasPdf);
    }

    /** Nama zona ikut tercetak — PDF-nya dibaca panitia di lapangan, bukan dipandang. */
    public function test_nama_zona_ikut_tercetak()
    {
        [$eventner] = $this->panitia();
        $denah = CompetitionLayout::factory()->create(['eventner_id' => $eventner->id]);
        LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'type' => LayoutZone::TYPE_ARENA,
            'name' => 'Arena Utama',
        ]);

        $html = $this->viewPdf($denah);

        $this->assertStringContainsString('Arena Utama', $html);
    }

    /** Nama event ikut di kop supaya lembaran PDF yang tercetak tak tertukar. */
    public function test_nama_event_ikut_di_kop()
    {
        [$eventner] = $this->panitia();
        $denah = CompetitionLayout::factory()->create(['eventner_id' => $eventner->id]);
        LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $this->assertStringContainsString($eventner->nama_event, $this->viewPdf($denah));
    }

    /**
     * Tinggi kanvas dipatok dalam mm, bukan dibiarkan aspect-ratio.
     *
     * Dompdf mengabaikan aspect-ratio; tanpa patokan tinggi, denah di PDF
     * runtuh jadi garis tipis.
     */
    public function test_tinggi_kanvas_dipatok_dalam_mm()
    {
        [$eventner] = $this->panitia();
        $denah = CompetitionLayout::factory()->create(['eventner_id' => $eventner->id]);
        LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $this->assertMatchesRegularExpression(
            '/\.denah-cetak \.denah-kanvas\s*\{[^}]*height:\s*\d+mm/s',
            $this->viewPdf($denah)
        );
    }

    /** Denah tanpa zona tetap bisa dicetak — papan kosong, bukan error. */
    public function test_denah_tanpa_zona_tetap_bisa_dicetak()
    {
        [$eventner] = $this->panitia();
        $denah = CompetitionLayout::factory()->create(['eventner_id' => $eventner->id]);

        $this->get(route('eventner.denah.pdf', $denah->id))->assertOk();
    }

    /** Render blade PDF-nya langsung supaya isinya bisa diperiksa sebagai teks. */
    private function viewPdf(CompetitionLayout $denah): string
    {
        $denah->load('zones');

        return view('eventner.denah.pdf-cetak', [
            'denah' => $denah,
            'eventner' => $denah->eventner,
            'tinggiKanvas' => '120mm',
            'ukuranLabel' => '13px',
        ])->render();
    }
}
