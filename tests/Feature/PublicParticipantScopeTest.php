<?php

namespace Tests\Feature;

use App\Livewire\Public\EventParticipant;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
use App\Models\Eventner;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Halaman publik /event/{slug}/participant pada event bergrup dan berbabak.
 *
 * Halaman ini sebelumnya menyajikan satu daftar rata per tingkat. Begitu
 * tingkat itu dibelah jadi Grup A/B dan menambah babak Final, daftar rata
 * menyembunyikan justru informasi yang dicari pelatih: sekolah ini masuk grup
 * mana, dan siapa saja yang lolos final.
 *
 * Satu hal yang sengaja TIDAK diubah: sekolah finalis tetap tampil di bagian
 * grupnya. Ia lolos DARI grupnya, bukan keluar dari grupnya — menghapusnya
 * dari daftar grup membuat grup itu tampak kehilangan peserta.
 */
class PublicParticipantScopeTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionGroup $groupA;

    private CompetitionGroup $groupB;

    private CompetitionRound $penyisihan;

    private CompetitionRound $final;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventner = Eventner::factory()->create([
            'status' => 'approved',
            'slug' => 'lomba-pbb',
            'nama_event' => 'Lomba PBB 2026',
        ]);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'PBB Putra',
        ]);
        $this->level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
            'name' => 'Regu Inti',
        ]);

        $this->groupA = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup A',
            'sort_order' => 1,
        ]);
        $this->groupB = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup B',
            'sort_order' => 2,
        ]);
        $this->penyisihan = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Fase Grup',
            'type' => CompetitionRound::TYPE_PRELIMINARY,
            'sort_order' => 1,
        ]);
        $this->final = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Final Stage',
            'type' => CompetitionRound::TYPE_FINAL,
            'sort_order' => 2,
        ]);
    }

    private function peserta(string $sekolah, ?CompetitionGroup $group): Registration
    {
        return Registration::factory()->create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $group?->id,
            'nama_sekolah' => $sekolah,
            'status_berkas' => 'confirmed',
        ]);
    }

    private function loloskan(Registration $reg, CompetitionRound $round, ?CompetitionGroup $group): void
    {
        CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $round->id,
            'registration_id' => $reg->id,
            'competition_group_id' => $group?->id,
        ]);
    }

    /** Kartu tingkat yang dirender, apa adanya. */
    private function kartu(?string $scopeKey = null): array
    {
        $component = Livewire::test(EventParticipant::class, ['slug' => 'lomba-pbb']);

        if ($scopeKey !== null) {
            $component->call('selectScope', $scopeKey);
        }

        return $component->viewData('cards');
    }

    /** Nama bagian pada kartu tingkat ini, urut tampil. */
    private function bagian(?string $scopeKey = null, int $index = 0): array
    {
        $kartu = $this->kartu($scopeKey);

        return collect($kartu[$index]['sections'] ?? [])->pluck('label')->all();
    }

    private function sekolahPadaBagian(string $label, ?string $scopeKey = null): array
    {
        $kartu = $this->kartu($scopeKey);

        $section = collect($kartu[0]['sections'] ?? [])->firstWhere('label', $label);

        return $section ? $section['registrations']->pluck('nama_sekolah')->sort()->values()->all() : [];
    }

    public function test_tanpa_lingkup_daftar_dipisah_per_grup()
    {
        $this->peserta('SMPN 1', $this->groupA);
        $this->peserta('SMPN 2', $this->groupB);

        $this->assertSame(['Grup A', 'Grup B'], $this->bagian());
    }

    /** Nama sekolah tidak pernah dikarang ulang — dipakai apa adanya. */
    public function test_anggota_tiap_grup_benar()
    {
        $this->peserta('SMPN 1', $this->groupA);
        $this->peserta('SMPN 3', $this->groupA);
        $this->peserta('SMPN 2', $this->groupB);

        $this->assertSame(['SMPN 1', 'SMPN 3'], $this->sekolahPadaBagian('Grup A'));
        $this->assertSame(['SMPN 2'], $this->sekolahPadaBagian('Grup B'));
    }

    /** Peserta yang belum dibagi tetap tampil — bukan hilang dari halaman. */
    public function test_peserta_belum_bergrup_punya_bagiannya_sendiri()
    {
        $this->peserta('SMPN 1', $this->groupA);
        $this->peserta('SMPN 9', null);

        $this->assertSame(['Grup A', 'Belum Bergrup'], $this->bagian());
        $this->assertSame(['SMPN 9'], $this->sekolahPadaBagian('Belum Bergrup'));
    }

    /**
     * Finalis tampil sebagai bagian tersendiri, DAN tetap di grupnya.
     *
     * Ini keputusan yang paling gampang salah: menghapus finalis dari bagian
     * grupnya membuat Grup A tampak kehilangan peserta yang justru menang.
     */
    public function test_finalis_punya_bagian_sendiri_dan_tetap_di_grupnya()
    {
        $a1 = $this->peserta('SMPN 1', $this->groupA);
        $b1 = $this->peserta('SMPN 2', $this->groupB);
        $this->loloskan($a1, $this->final, $this->groupA);
        $this->loloskan($b1, $this->final, $this->groupB);

        $this->assertSame(
            ['Grup A', 'Grup B', 'Final Stage (Finalis)'],
            $this->bagian()
        );
        $this->assertSame(['SMPN 1'], $this->sekolahPadaBagian('Grup A'));
        $this->assertSame(['SMPN 1', 'SMPN 2'], $this->sekolahPadaBagian('Final Stage (Finalis)'));
    }

    /** Babak penyisihan bukan bagian tersendiri: lingkupnya sudah diwakili grup. */
    public function test_babak_penyisihan_tidak_jadi_bagian()
    {
        $this->peserta('SMPN 1', $this->groupA);

        $this->assertNotContains('Fase Grup (Finalis)', $this->bagian());
    }

    /** Babak final tanpa finalis tidak menambah bagian kosong. */
    public function test_babak_final_tanpa_finalis_tidak_menambah_bagian()
    {
        $this->peserta('SMPN 1', $this->groupA);

        $this->assertSame(['Grup A'], $this->bagian());
    }

    public function test_lingkup_grup_menyaring_seluruh_halaman()
    {
        $this->peserta('SMPN 1', $this->groupA);
        $this->peserta('SMPN 2', $this->groupB);

        $kartu = $this->kartu('grup-'.$this->groupA->id);

        $this->assertSame(
            ['SMPN 1'],
            $kartu[0]['sections'][0]['registrations']->pluck('nama_sekolah')->all()
        );
    }

    /** Lingkup grup tampil sebagai satu bagian tanpa judul — sudah jelas dari dropdown. */
    public function test_lingkup_grup_tanpa_judul_bagian()
    {
        $this->peserta('SMPN 1', $this->groupA);

        $this->assertSame([null], $this->bagian('grup-'.$this->groupA->id));
    }

    public function test_lingkup_final_hanya_finalis()
    {
        $a1 = $this->peserta('SMPN 1', $this->groupA);
        $this->peserta('SMPN 2', $this->groupB);
        $this->loloskan($a1, $this->final, $this->groupA);

        $kartu = $this->kartu('babak-'.$this->final->id);
        $sections = $kartu[0]['sections'];

        $this->assertCount(1, $sections);
        $this->assertTrue($sections[0]['is_final']);
        $this->assertSame(['SMPN 1'], $sections[0]['registrations']->pluck('nama_sekolah')->all());
    }

    /** Lingkup terpilih memuat satu tingkat saja — tingkat lain turun dari halaman. */
    public function test_lingkup_hanya_menyisakan_tingkat_bersangkutan()
    {
        $parent = $this->level->parent;
        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
            'name' => 'Regu Cadangan',
        ]);
        Registration::factory()->create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $lain->id,
            'nama_sekolah' => 'SMPN 5',
            'status_berkas' => 'confirmed',
        ]);
        $this->peserta('SMPN 1', $this->groupA);

        $this->assertCount(2, $this->kartu());
        $this->assertCount(1, $this->kartu('grup-'.$this->groupA->id));
    }

    public function test_daftar_lingkup_berisi_grup_dan_babak_final_berfinalis()
    {
        $a1 = $this->peserta('SMPN 1', $this->groupA);
        $this->loloskan($a1, $this->final, $this->groupA);

        $scopes = Livewire::test(EventParticipant::class, ['slug' => 'lomba-pbb'])
            ->viewData('scopes');

        $this->assertSame(
            ['Grup A', 'Grup B', 'Final Stage (Finalis)'],
            $scopes->pluck('label')->all()
        );
    }

    /** Babak final tanpa finalis tidak jadi pilihan: memilihnya cuma halaman kosong. */
    public function test_babak_final_tanpa_finalis_tidak_jadi_pilihan()
    {
        $this->peserta('SMPN 1', $this->groupA);

        $scopes = Livewire::test(EventParticipant::class, ['slug' => 'lomba-pbb'])
            ->viewData('scopes');

        $this->assertSame(['Grup A', 'Grup B'], $scopes->pluck('label')->all());
    }

    /** Lingkup tingkat lain ditolak — kuncinya divalidasi, bukan dipercaya. */
    public function test_lingkup_event_lain_ditolak()
    {
        $lain = Eventner::factory()->create(['status' => 'approved']);
        $tingkatLain = CompetitionCategory::factory()->create(['eventner_id' => $lain->id]);
        $grupLain = CompetitionGroup::create([
            'eventner_id' => $lain->id,
            'competition_category_id' => $tingkatLain->id,
            'name' => 'Grup A',
        ]);

        Livewire::test(EventParticipant::class, ['slug' => 'lomba-pbb'])
            ->call('selectScope', 'grup-'.$grupLain->id)
            ->assertSet('selectedScopeId', '');
    }

    /**
     * Nama grup dipisah per tingkat hanya saat lebih dari satu tingkat punya
     * grup. Tanpa itu, "Grup A" muncul dua kali di dropdown dengan arti
     * berbeda dan tidak ada cara membedakannya.
     */
    public function test_nama_grup_disambiguasi_saat_lebih_dari_satu_tingkat_bergrup()
    {
        $parent = $this->level->parent;
        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
            'name' => 'Regu Cadangan',
        ]);
        CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $lain->id,
            'name' => 'Grup A',
            'sort_order' => 1,
        ]);

        $scopes = Livewire::test(EventParticipant::class, ['slug' => 'lomba-pbb'])
            ->viewData('scopes');

        $this->assertContains('Grup A — Regu Inti', $scopes->pluck('label')->all());
        $this->assertContains('Grup A — Regu Cadangan', $scopes->pluck('label')->all());
    }

    /** Tingkat tanpa grup tampil sebagai satu daftar rata, seperti sebelum fitur grup. */
    public function test_tingkat_tanpa_grup_tampil_tanpa_judul_bagian()
    {
        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $polos = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
        ]);
        Registration::factory()->create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $polos->id,
            'nama_sekolah' => 'SMPN 7',
            'status_berkas' => 'confirmed',
        ]);

        $kartu = $this->kartu();
        $kartuPolos = collect($kartu)->firstWhere(fn ($k) => $k['category']->id === $polos->id);

        $this->assertFalse($kartuPolos['bersection']);
        $this->assertSame([null], collect($kartuPolos['sections'])->pluck('label')->all());
    }

    /** Tingkat bergrup yang cuma punya satu grup tetap tanpa judul bagian. */
    public function test_satu_grup_saja_tidak_memunculkan_judul_bagian()
    {
        $this->peserta('SMPN 1', $this->groupA);
        $this->peserta('SMPN 3', $this->groupA);

        $kartu = $this->kartu();

        $this->assertFalse($kartu[0]['bersection']);
    }

    /** Halaman tetap 200 dengan penyaringan baru. */
    public function test_halaman_tetap_terbuka()
    {
        $this->peserta('SMPN 1', $this->groupA);

        $this->get('/event/lomba-pbb/participant')
            ->assertOk()
            ->assertSee('SMPN 1')
            ->assertSee('Grup A');
    }

    /** Angka di kepala kartu menghitung peserta, bukan baris — finalis dobel. */
    public function test_jumlah_kontingen_tidak_menghitung_finalis_dua_kali()
    {
        $a1 = $this->peserta('SMPN 1', $this->groupA);
        $this->loloskan($a1, $this->final, $this->groupA);

        $this->assertSame(1, $this->kartu()[0]['jumlah']);
    }
}
