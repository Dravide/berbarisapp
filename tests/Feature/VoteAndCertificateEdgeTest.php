<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Certificate\Templates;
use App\Livewire\Eventner\VoteComment\Index as VoteCommentIndex;
use App\Models\CertificateTemplate;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\User;
use App\Models\VoteTransaction;
use App\Support\FormatNilaiImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Temuan audit #39, #40, #41, #46, #47 — tier, sertifikat, impor, vote.
 *
 * #39 Penyaring tier memakai "votes_earned >= TIERS[tier]", jadi band yang
 *      lebih rendah ikut membawa komentar tier di atasnya.
 * #40 saveTemplate() menghapus berkas lama sebelum store() yang baru.
 * #41 normalizeRows() menghitung nomor baris dengan offset header yang
 *      di-hardcode, padahal array-nya datang sudah tanpa header.
 * #46 submitVote() hanya memeriksa vote_active, bukan jadwal vote.
 * #47 halaman sukses mencetak voteCount, bukan votes_earned yang tersimpan.
 */
class VoteAndCertificateEdgeTest extends TestCase
{
    use RefreshDatabase;

    private function eventner(array $atribut = []): Eventner
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        return Eventner::factory()->create(array_merge([
            'user_id' => $user->id,
            'status' => 'approved',
        ], $atribut));
    }

    // ────────────────────────────────────────────────
    // #39 — tier itu band, bukan ambang bawah
    // ────────────────────────────────────────────────

    public function test_penyaring_tier_tidak_membawa_tier_di_atasnya()
    {
        $this->assertSame([50, 99], VoteCommentIndex::bandOf('hot'));
        $this->assertSame([500, 999], VoteCommentIndex::bandOf('legend'));
        $this->assertSame([1000, null], VoteCommentIndex::bandOf('mvp'));
        $this->assertSame([10, 49], VoteCommentIndex::bandOf('populer'));
    }

    public function test_daftar_komentar_menyaring_satu_band_saja()
    {
        $eventner = $this->eventner();
        $kategori = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => CompetitionCategory::factory()->create(['eventner_id' => $eventner->id])->id,
        ]);
        $reg = Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $kategori->id,
        ]);

        foreach ([30 => 'Populer', 70 => 'Hot', 800 => 'Legend'] as $votes => $nama) {
            VoteTransaction::create([
                'eventner_id' => $eventner->id,
                'registration_id' => $reg->id,
                'autogopay_transaction_id' => 'AGP-' . $votes,
                'qr_url' => 'https://example.com/qr.png',
                'amount' => $votes * 1000,
                'votes_earned' => $votes,
                'voter_name' => $nama,
                'voter_email' => strtolower($nama) . '@mail.com',
                'comment' => 'Komentar ' . $nama,
                'status' => 'PAID',
                'paid_at' => now(),
            ]);
        }

        Livewire::actingAs($eventner->user)
            ->test(VoteCommentIndex::class)
            ->set('filterTier', 'hot')
            ->assertSee('Komentar Hot')
            ->assertDontSee('Komentar Populer')
            ->assertDontSee('Komentar Legend', false);
    }

    // ────────────────────────────────────────────────
    // #40 — template aktif bisa dipilih operator
    // ────────────────────────────────────────────────

    public function test_template_kedua_tidak_ikut_aktif()
    {
        $eventner = $this->eventner();

        $pertama = CertificateTemplate::create([
            'eventner_id' => $eventner->id, 'name' => 'Lama',
            'width' => 297, 'height' => 210, 'file_path' => 'certificate-templates/a.png',
        ]);

        $kedua = CertificateTemplate::create([
            'eventner_id' => $eventner->id, 'name' => 'Baru',
            'width' => 297, 'height' => 210, 'file_path' => 'certificate-templates/b.png',
            'is_active' => false,
        ]);

        Livewire::actingAs($eventner->user)->test(Templates::class)
            ->call('setActiveTemplate', $kedua->id);

        $this->assertTrue($kedua->fresh()->is_active, 'Template yang dipilih harus jadi aktif.');
        $this->assertFalse($pertama->fresh()->is_active, 'Hanya satu template boleh aktif per event.');
    }

    // ────────────────────────────────────────────────
    // #41 — nomor baris di pesan error cocok dengan Excel
    // ────────────────────────────────────────────────

    public function test_nomor_baris_error_mengikuti_nomor_baris_excel()
    {
        // Header = baris 1. Array di bawah mewakili baris 2 dan 3.
        $rows = [
            ['Rubrik', '', 'Sub A', '', '', '', ''],   // baris 2 — Kategori kosong
            ['Rubrik', 'Kategori', 'Sub', 'Kriteria', '10', '', ''], // baris 3 — valid
        ];

        $hasil = FormatNilaiImport::normalizeRows($rows, 1);

        $this->assertNotEmpty($hasil['errors']);
        $this->assertSame(2, $hasil['errors'][0]['row'], 'Baris pertama data ada di baris 2 Excel.');
    }

    /** Tanpa header, baris pertama data memang baris 1. */
    public function test_tanpa_header_nomor_baris_mulai_dari_satu()
    {
        $rows = [['Rubrik', '', 'Sub', '', '', '', '']];

        $hasil = FormatNilaiImport::normalizeRows($rows, 0);

        $this->assertSame(1, $hasil['errors'][0]['row']);
    }

    // ────────────────────────────────────────────────
    // #46 — jadwal vote ikut dijaga saat bayar
    // ────────────────────────────────────────────────

    public function test_bayar_vote_ditolak_saat_voting_sudah_ditutup()
    {
        Http::fake(['*' => Http::response(['success' => true, 'data' => []], 200)]);

        $eventner = $this->eventner([
            'vote_active' => true,
            'vote_end' => now()->subDay(),
        ]);
        $kategori = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => CompetitionCategory::factory()->create(['eventner_id' => $eventner->id])->id,
        ]);
        $reg = Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $kategori->id,
        ]);

        Livewire::test(\App\Livewire\Public\EventVote::class, ['slug' => $eventner->slug])
            ->call('selectCategory', $kategori->id)
            ->call('selectTeam', $reg->id)
            ->set('voterName', 'Rina')
            ->set('voterEmail', 'rina@mail.com')
            ->call('submitVote');

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'qris'));
        $this->assertSame(0, VoteTransaction::count(), 'Voting tertutup tidak boleh membuat transaksi.');
    }

    public function test_bayar_vote_ditolak_sebelum_voting_dibuka()
    {
        Http::fake(['*' => Http::response(['success' => true, 'data' => []], 200)]);

        $eventner = $this->eventner([
            'vote_active' => true,
            'vote_start' => now()->addDay(),
        ]);
        $kategori = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => CompetitionCategory::factory()->create(['eventner_id' => $eventner->id])->id,
        ]);
        $reg = Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $kategori->id,
        ]);

        Livewire::test(\App\Livewire\Public\EventVote::class, ['slug' => $eventner->slug])
            ->call('selectCategory', $kategori->id)
            ->call('selectTeam', $reg->id)
            ->set('voterName', 'Rina')
            ->set('voterEmail', 'rina@mail.com')
            ->call('submitVote');

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'qris'));
        $this->assertSame(0, VoteTransaction::count());
    }

    // ────────────────────────────────────────────────
    // #47 — halaman sukses menyebut vote yang benar-benar masuk
    // ────────────────────────────────────────────────

    public function test_jumlah_vote_di_halaman_sukses_termasuk_booster()
    {
        $eventner = $this->eventner(['vote_active' => true]);
        $kategori = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => CompetitionCategory::factory()->create(['eventner_id' => $eventner->id])->id,
        ]);
        $reg = Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $kategori->id,
        ]);

        $tx = VoteTransaction::create([
            'eventner_id' => $eventner->id,
            'registration_id' => $reg->id,
            'autogopay_transaction_id' => 'AGP-BOOST',
            'qr_url' => 'https://example.com/qr.png',
            'amount' => 50000,
            'votes_earned' => 50, // 5 vote x booster 10
            'voter_name' => 'Rina',
            'voter_email' => 'rina@mail.com',
            'status' => 'PENDING',
        ]);

        Livewire::test(\App\Livewire\Public\EventVote::class, ['slug' => $eventner->slug])
            ->set('voteCount', 5)
            ->set('currentTransactionId', $tx->id)
            ->assertViewHas('totalEventVotes')
            ->assertSet('creditedVotes', 50);
    }
}
