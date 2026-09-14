<?php

namespace Tests\Feature;

use App\Livewire\Eventner\CompetitionCategory\Index as CategoryIndex;
use App\Livewire\Eventner\Participant\Index as ParticipantIndex;
use App\Livewire\Public\EventVote;
use App\Livewire\Public\Registration\Create as RegistrationCreate;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\User;
use App\Models\VoteTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Temuan #4-#9 di docs/audit-bug-eventner-2026-09-11.md.
 *
 * Semuanya satu pola: id datang dari DOM, lalu dipakai tanpa dicek
 * kepemilikannya — atau baris dihapus padahal ada data lain yang
 * bergantung padanya.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    /** Subdomain di-null-kan: factory memakai Str::random() yang ada huruf besar. */
    private function buatEventner(string $slug, ?string $kode = null): Eventner
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        return Eventner::factory()->create([
            'user_id' => $user->id,
            'slug' => $slug,
            'status' => 'approved',
            'subdomain' => null,
            'drawing_code' => $kode,
        ]);
    }

    private function buatKategori(Eventner $eventner): CompetitionCategory
    {
        return CompetitionCategory::factory()->child()->create([
            'eventner_id' => $eventner->id,
        ]);
    }

    // ── #4 Vote lintas event ────────────────────────────────────────────

    public function test_vote_menolak_peserta_event_lain(): void
    {
        $eventA = $this->buatEventner('vote-a');
        $eventB = $this->buatEventner('vote-b');
        $kategoriB = $this->buatKategori($eventB);
        $pesertaB = Registration::factory()->create([
            'eventner_id' => $eventB->id,
            'competition_category_id' => $kategoriB->id,
        ]);

        Livewire::test(EventVote::class, ['slug' => $eventA->slug])
            ->call('selectTeam', $pesertaB->id)
            ->assertSet('selectedRegistrationId', null)
            ->assertSee('Peserta tidak ditemukan pada event ini.');
    }

    public function test_vote_menerima_peserta_event_sendiri(): void
    {
        $eventA = $this->buatEventner('vote-c');
        $kategoriA = $this->buatKategori($eventA);
        $pesertaA = Registration::factory()->create([
            'eventner_id' => $eventA->id,
            'competition_category_id' => $kategoriA->id,
        ]);

        Livewire::test(EventVote::class, ['slug' => $eventA->slug])
            ->call('selectTeam', $pesertaA->id)
            ->assertSet('selectedRegistrationId', $pesertaA->id);
    }

    // ── #5 Pendaftaran lintas event ─────────────────────────────────────

    public function test_daftar_menolak_kategori_event_lain(): void
    {
        $eventA = $this->buatEventner('daftar-a');
        $eventB = $this->buatEventner('daftar-b');
        $kategoriB = $this->buatKategori($eventB);

        $komponen = Livewire::test(RegistrationCreate::class, ['slug' => $eventA->slug])
            ->call('toggleCategory', $kategoriB->id);

        $this->assertSame([], $komponen->get('selectedCategories'));
    }

    public function test_submit_hanya_membuat_pendaftaran_untuk_kategori_event_sendiri(): void
    {
        $eventA = $this->buatEventner('submit-a');
        $eventB = $this->buatEventner('submit-b');
        $kategoriA = $this->buatKategori($eventA);
        $kategoriB = $this->buatKategori($eventB);

        $komponen = Livewire::test(RegistrationCreate::class, ['slug' => $eventA->slug])
            ->set('selectedCategories', [$kategoriA->id, $kategoriB->id])
            ->set('npsn', '12345678')
            ->set('nama_sekolah', 'SMP Negeri 1 Uji')
            ->set('nama_pelatih', 'Pelatih Uji')
            ->set('no_hp', '08123456789')
            ->set('school_email', 'uji@example.test')
            ->call('submit');

        // Kategori event lain harus dibuang, bukan ikut terdaftar.
        $this->assertSame(0, Registration::where('competition_category_id', $kategoriB->id)->count());
        $this->assertSame(1, Registration::where('competition_category_id', $kategoriA->id)->count());
    }

    // ── #6 Hierarki & juri lintas tenant ────────────────────────────────

    public function test_kategori_menolak_induk_dari_tenant_lain(): void
    {
        $eventA = $this->buatEventner('kat-a');
        $eventB = $this->buatEventner('kat-b');
        $indukB = CompetitionCategory::factory()->create(['eventner_id' => $eventB->id]);

        Livewire::actingAs($eventA->user)
            ->test(CategoryIndex::class)
            ->set('name', 'Tingkat Titipan')
            ->set('parentId', $indukB->id)
            ->call('save')
            ->assertHasErrors('parentId');

        $this->assertSame(0, CompetitionCategory::where('name', 'Tingkat Titipan')->count());
    }

    // ── #7 Peserta lintas tenant ────────────────────────────────────────

    public function test_tambah_peserta_menolak_kategori_tenant_lain(): void
    {
        $eventA = $this->buatEventner('pes-a');
        $eventB = $this->buatEventner('pes-b');
        $kategoriB = $this->buatKategori($eventB);

        Livewire::actingAs($eventA->user)
            ->test(ParticipantIndex::class)
            ->set('competition_category_id', $kategoriB->id)
            ->set('npsn', '87654321')
            ->set('nama_sekolah', 'SMP Negeri 2 Uji')
            ->set('no_hp', '08123456780')
            ->set('jumlah_pasukan', 1)
            ->call('save')
            ->assertHasErrors('competition_category_id');

        $this->assertSame(0, Registration::where('npsn', '87654321')->count());
    }

    // ── #8 Hapus tingkat lomba cascade ke nilai ─────────────────────────

    public function test_hapus_tingkat_diblokir_saat_masih_ada_pendaftar(): void
    {
        $eventner = $this->buatEventner('hapus-kat');
        $kategori = $this->buatKategori($eventner);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $kategori->id,
        ]);

        Livewire::actingAs($eventner->user)
            ->test(CategoryIndex::class)
            ->call('delete', $kategori->id);

        $this->assertDatabaseHas('competition_categories', ['id' => $kategori->id]);
    }

    public function test_hapus_tingkat_kosong_tetap_jalan(): void
    {
        $eventner = $this->buatEventner('hapus-kat-kosong');
        $kategori = $this->buatKategori($eventner);

        Livewire::actingAs($eventner->user)
            ->test(CategoryIndex::class)
            ->call('delete', $kategori->id);

        $this->assertDatabaseMissing('competition_categories', ['id' => $kategori->id]);
    }

    // ── #9 Hapus pendaftar membuang transaksi vote PAID ─────────────────

    public function test_hapus_pendaftar_diblokir_saat_ada_vote_berbayar(): void
    {
        $eventner = $this->buatEventner('hapus-pes');
        $kategori = $this->buatKategori($eventner);
        $peserta = Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $kategori->id,
        ]);

        VoteTransaction::create([
            'eventner_id' => $eventner->id,
            'registration_id' => $peserta->id,
            'autogopay_transaction_id' => 'TX-UJI-1',
            'qr_url' => 'https://example.test/qr',
            'amount' => 10000,
            'votes_earned' => 10,
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        Livewire::actingAs($eventner->user)
            ->test(ParticipantIndex::class)
            ->call('delete', $peserta->id);

        $this->assertDatabaseHas('registrations', ['id' => $peserta->id]);
        $this->assertDatabaseHas('vote_transactions', ['registration_id' => $peserta->id]);
    }

    public function test_hapus_pendaftar_tanpa_vote_berbayar_tetap_jalan(): void
    {
        $eventner = $this->buatEventner('hapus-pes-bersih');
        $kategori = $this->buatKategori($eventner);
        $peserta = Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $kategori->id,
        ]);

        Livewire::actingAs($eventner->user)
            ->test(ParticipantIndex::class)
            ->call('delete', $peserta->id);

        $this->assertDatabaseMissing('registrations', ['id' => $peserta->id]);
    }
}
