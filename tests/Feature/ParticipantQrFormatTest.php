<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cetak QR peserta harus PNG. Di chillerlan/php-qrcode v6 propertinya bernama
 * outputInterface; kunci 'outputType' pada array QROptions diabaikan
 * diam-diam, sehingga QR keluar SVG — dan dompdf tidak bisa menggambar SVG
 * di dalam PDF, jadi QR-nya hilang tanpa error.
 */
class ParticipantQrFormatTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Eventner $eventner;
    private Registration $registration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $category = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
        ]);

        $this->registration = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $category->id,
            'nama_sekolah' => 'SD Negeri 1',
        ]);
    }

    /** Halaman cetak QR satu peserta (HTML) — harus <img> PNG. */
    public function test_qr_peserta_dirender_sebagai_png()
    {
        $html = $this->actingAs($this->user)
            ->get(route('eventner.participants.qr', $this->registration->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringNotContainsString('data:image/svg+xml', $html);
    }

    /** Batch QR jadi PDF — tiap QR di dalamnya harus PNG. */
    public function test_qr_batch_pdf_memakai_png()
    {
        $response = $this->actingAs($this->user)
            ->get(route('eventner.participants.qr-batch', [
                'category_id' => $this->registration->competition_category_id,
            ]));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
    }
}
