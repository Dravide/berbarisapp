<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Drawing\Index;
use App\Livewire\Eventner\Scoring\Index as ScoringIndex;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Nomor undian per babak.
 *
 * Dulu registrations.urutan_tampil adalah SATU nomor yang dipakai ulang di
 * semua babak, jadi peserta yang lolos final tetap membawa nomor undian fase
 * grupnya di final. Babak final kini punya undiannya sendiri di baris
 * competition_round_registrations — dan nomor itu TIDAK diambil dari nomor
 * undian grupnya.
 */
class DrawingPerRoundTest extends TestCase
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

        $user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'drawing_code' => 'UNDI-BABAK',
        ]);
        $this->actingAs($user);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $this->level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
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
            'name' => 'Final',
            'type' => CompetitionRound::TYPE_FINAL,
            'sort_order' => 2,
        ]);
    }

    private function peserta(string $school, CompetitionGroup $group, ?int $nomorGrup = null): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $group->id,
            'nama_sekolah' => $school,
            'urutan_tampil' => $nomorGrup,
        ]);
    }

    /** Catat peserta sebagai finalis babak final. */
    private function loloskan(Registration $reg, ?int $nomorFinal = null): CompetitionRoundRegistration
    {
        return CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $this->final->id,
            'registration_id' => $reg->id,
            'competition_group_id' => $reg->competition_group_id,
            'urutan_tampil' => $nomorFinal,
        ]);
    }

    /** Panel undian admin, siap di babak tertentu. */
    private function panel(string $roundId = '', string $groupId = '')
    {
        $component = Livewire::test(Index::class)->call('switchTab', $this->level->id);

        if ($roundId !== '') {
            $component->call('switchRound', $roundId);
        }

        if ($groupId !== '') {
            $component->call('switchGroup', $groupId);
        }

        return $component;
    }

    public function test_nomor_undian_final_tidak_diambil_dari_nomor_grup()
    {
        $reg = $this->peserta('SMPN 1', $this->groupA, nomorGrup: 7);
        $this->loloskan($reg);

        // Belum diundi di final → TIDAK ada nomor, bukan nomor grupnya.
        $this->assertNull($reg->fresh()->nomorUndian($this->final));
        // Nomor fase grupnya sendiri tetap utuh.
        $this->assertSame(7, $reg->fresh()->nomorUndian($this->penyisihan));
    }

    public function test_undi_final_menulis_ke_baris_babak_dan_tidak_menyentuh_nomor_grup()
    {
        $reg = $this->peserta('SMPN 1', $this->groupA, nomorGrup: 7);
        $this->loloskan($reg);

        $this->panel((string) $this->final->id)
            ->set('manualRegistrationId', $reg->id)
            ->set('manualUrutan', 1)
            ->call('assignManual')
            ->assertHasNoErrors();

        $this->assertSame(1, $reg->fresh()->nomorUndian($this->final));
        $this->assertSame(7, (int) $reg->fresh()->urutan_tampil, 'Nomor undian fase grup ikut berubah.');
    }

    public function test_nomor_final_boleh_sama_dengan_nomor_fase_grup()
    {
        // Nomor 1 dipakai Grup A di penyisihan; di final nomor itu harus tetap
        // boleh, karena yang dibandingkan adalah nomor undian BABAK INI.
        $a = $this->peserta('SMPN A', $this->groupA, nomorGrup: 1);
        $this->loloskan($a);

        $this->panel((string) $this->final->id)
            ->set('manualRegistrationId', $a->id)
            ->set('manualUrutan', 1)
            ->call('assignManual')
            ->assertHasNoErrors();

        $this->assertSame(1, $a->fresh()->nomorUndian($this->final));
    }

    /**
     * Pool final satu tingkat: finalis Grup A dan Grup B berbagi satu ruang
     * nomor, jadi nomor yang sama di dua grup asal BERBENTROK di final.
     */
    public function test_nomor_final_unik_se_tingkat_lintas_grup_asal()
    {
        $a = $this->peserta('SMPN A', $this->groupA);
        $b = $this->peserta('SMPN B', $this->groupB);
        $this->loloskan($a);
        $this->loloskan($b);

        $this->panel((string) $this->final->id)
            ->set('manualRegistrationId', $a->id)
            ->set('manualUrutan', 3)
            ->call('assignManual')
            ->assertHasNoErrors();

        // Grup asal tidak menyaring apa pun di final.
        $this->panel((string) $this->final->id, (string) $this->groupB->id)
            ->set('manualRegistrationId', $b->id)
            ->set('manualUrutan', 3)
            ->call('assignManual')
            ->assertHasErrors('manualUrutan');

        $this->assertNull(CompetitionRoundRegistration::where('registration_id', $b->id)->value('urutan_tampil'));
    }

    public function test_peserta_yang_tidak_lolos_tidak_bisa_diundi_di_final()
    {
        $gugur = $this->peserta('SMPN Gugur', $this->groupA);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        try {
            $this->panel((string) $this->final->id)
                ->set('manualRegistrationId', $gugur->id)
                ->set('manualUrutan', 1)
                ->call('assignManual');
        } finally {
            $this->assertNull($gugur->fresh()->urutan_tampil);
        }
    }

    public function test_reset_undian_final_tidak_menyentuh_nomor_fase_grup()
    {
        $a = $this->peserta('SMPN A', $this->groupA, nomorGrup: 5);
        $b = $this->peserta('SMPN B', $this->groupB, nomorGrup: 6);
        $this->loloskan($a, nomorFinal: 1);
        $this->loloskan($b, nomorFinal: 2);

        $this->panel((string) $this->final->id)->call('resetDrawing');

        $this->assertNull($a->fresh()->roundRegistrations->first()->urutan_tampil);
        $this->assertNull($b->fresh()->roundRegistrations->first()->urutan_tampil);
        $this->assertSame(5, (int) $a->fresh()->urutan_tampil);
        $this->assertSame(6, (int) $b->fresh()->urutan_tampil);
    }

    public function test_reset_undian_penyisihan_tidak_menyentuh_nomor_final()
    {
        $a = $this->peserta('SMPN A', $this->groupA, nomorGrup: 5);
        $this->loloskan($a, nomorFinal: 1);

        $this->panel()->call('resetDrawing');

        $this->assertNull($a->fresh()->urutan_tampil);
        $this->assertSame(1, (int) $a->fresh()->roundRegistrations->first()->urutan_tampil);
    }

    /** Tingkat tanpa babak: seluruh perilaku lama harus utuh. */
    public function test_tingkat_tanpa_babak_tetap_memakai_kolom_registrasi()
    {
        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->level->parent_id,
        ]);
        $reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $lain->id,
            'nama_sekolah' => 'SMPN Tanpa Babak',
        ]);

        Livewire::test(Index::class)
            ->call('switchTab', $lain->id)
            ->set('manualRegistrationId', $reg->id)
            ->set('manualUrutan', 2)
            ->call('assignManual')
            ->assertHasNoErrors();

        $this->assertSame(2, (int) $reg->fresh()->urutan_tampil);
        // Tanpa konteks babak, nomorUndian() membaca kolom registrasi.
        $this->assertSame(2, $reg->fresh()->nomorUndian(null));
    }

    /**
     * Panel nilai: di chip Final nomornya dibaca dari undian babak final.
     * Diperiksa lewat HTML yang benar-benar dirender — bukan lewat model —
     * supaya penyambungan selectedRound ke blade ikut terjaga.
     */
    public function test_panel_nilai_final_memakai_nomor_final()
    {
        $a = $this->peserta('SMPN A', $this->groupA, nomorGrup: 9);
        $this->loloskan($a, nomorFinal: 4);

        Livewire::test(ScoringIndex::class)
            ->set('selectedCategoryId', $this->level->id)
            ->call('selectScope', 'final')
            ->assertSet('selectedRoundId', $this->final->id)
            ->assertSee('4')
            ->assertDontSee('>9<');
    }

    public function test_panel_nilai_final_tidak_menampilkan_nomor_grup_saat_belum_diundi()
    {
        $a = $this->peserta('SMPN A', $this->groupA, nomorGrup: 9);
        $this->loloskan($a);

        // Finalis belum diundi → badge nomor kosong; nomor fase grupnya
        // (9) TIDAK dipakai sebagai gantinya.
        Livewire::test(ScoringIndex::class)
            ->set('selectedCategoryId', $this->level->id)
            ->call('selectScope', 'final')
            ->assertSet('selectedRoundId', $this->final->id)
            ->assertDontSee('>9<');
    }
}
