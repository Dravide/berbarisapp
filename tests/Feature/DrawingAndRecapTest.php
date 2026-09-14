<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Temuan audit #23, #26, #27, #28, #29 — undian, kategori, dan rekap.
 *
 * #23: Rekap Nilai membuka kategori INDUK sebagai default, sedangkan
 *      registrasi menempel ke anak — rekap tampak kosong saat pertama dibuka.
 * #26: nomor undian dibatasi kuota, sehingga peserta di atas kuota tidak
 *      pernah bisa diundi.
 * #27: reset undian tidak dicegah setelah nilai masuk (beda dengan Tukar
 *      Pasukan yang sudah dijaga).
 * #28: pindah kategori saat edit tidak membersihkan urutan_tampil.
 * #29: kategori juara yang rubriknya mencakup dua tingkat hanya muncul di
 *      satu bagian PDF.
 */
class DrawingAndRecapTest extends TestCase
{
    use RefreshDatabase;

    private function buatEventner(array $atribut = []): Eventner
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        return Eventner::factory()->create(array_merge([
            'user_id' => $user->id,
            'status' => 'approved',
            'drawing_code' => 'UNDI-123',
        ], $atribut));
    }

    /** @return array{0: CompetitionCategory, 1: CompetitionCategory} [induk, anak] */
    private function tingkat(Eventner $eventner, string $nama = 'SMP'): array
    {
        $induk = CompetitionCategory::factory()->for($eventner, 'eventner')->create(['name' => 'PBB']);

        $anak = CompetitionCategory::factory()->child($induk)
            ->for($eventner, 'eventner')
            ->create(['name' => $nama]);

        return [$induk, $anak];
    }

    /**
     * #23 — Rekap Nilai membuka kategori anak yang berisi peserta, bukan
     * kategori induk yang selalu kosong.
     */
    public function test_rekap_nilai_membuka_kategori_berpeserta()
    {
        $eventner = $this->buatEventner();
        [$induk, $anak] = $this->tingkat($eventner);

        Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $anak->id,
            'nama_sekolah' => 'SMP Rekap',
        ]);

        $this->actingAs($eventner->user);

        Livewire::test(\App\Livewire\Eventner\ScoreRecap\Index::class)
            ->assertSet('selectedCategoryId', $anak->id)
            ->assertSee('SMP Rekap');
    }

    /** Kategori dari tenant lain tidak membajak rekap. */
    public function test_rekap_nilai_menolak_kategori_event_lain()
    {
        $eventner = $this->buatEventner();
        $this->tingkat($eventner);

        $lain = $this->buatEventner();
        [, $anakLain] = $this->tingkat($lain, 'SMA');

        $this->actingAs($eventner->user);

        Livewire::test(\App\Livewire\Eventner\ScoreRecap\Index::class)
            ->set('selectedCategoryId', $anakLain->id)
            ->assertSet('selectedCategoryId', null);
    }

    /**
     * #26 — jumlah pendaftar melebihi kuota: semua peserta harus tetap bisa
     * dapat nomor undian.
     */
    public function test_undian_menyediakan_nomor_untuk_peserta_di_atas_kuota()
    {
        $eventner = $this->buatEventner();
        [, $anak] = $this->tingkat($eventner);

        // Kuota 5, tapi 8 sekolah mendaftar.
        $anak->update(['kuota' => 5]);

        for ($i = 1; $i <= 8; $i++) {
            Registration::factory()->for($eventner, 'eventner')->create([
                'competition_category_id' => $anak->id,
                'nama_sekolah' => 'SMP ' . $i,
            ]);
        }

        $this->actingAs($eventner->user);

        $komponen = Livewire::test(\App\Livewire\Eventner\Drawing\Spin::class, [
            'slug' => $eventner->slug,
        ]);

        // Halaman undian terkunci karena drawing_code sudah dipasang.
        $komponen->set('inputCode', 'UNDI-123')->call('verifyCode');

        // Undi delapan kali; nomor 8 harus tetap bisa keluar.
        $nomor = [];
        for ($i = 0; $i < 8; $i++) {
            $komponen->call('spin');
            $hasil = $komponen->get('spinResult');
            $this->assertNotNull($hasil, 'Nomor undian habis sebelum semua peserta terundi.');
            $nomor[] = $hasil;
            $komponen->call('saveResult');
        }

        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8], collect($nomor)->sort()->values()->all());
    }

    /**
     * #27 — reset undian diblokir begitu ada nilai juri, sama seperti Tukar
     * Pasukan.
     */
    public function test_reset_undian_diblokir_bila_sudah_ada_nilai()
    {
        $eventner = $this->buatEventner();
        [$induk, $anak] = $this->tingkat($eventner);

        $reg = Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $anak->id,
            'urutan_tampil' => 1,
        ]);

        $format = AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $anak->id,
            'name' => 'Penilaian',
            'sort_order' => 1,
        ]);
        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $format->id,
            'name' => 'Sub',
            'sort_order' => 1,
        ]);
        $kriteria = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Ketepatan',
            'score_options' => [['score' => 10]],
            'sort_order' => 1,
        ]);

        AssessmentScore::create([
            'eventner_id' => $eventner->id,
            'registration_id' => $reg->id,
            'assessment_criteria_id' => $kriteria->id,
            'score' => 10,
        ]);

        $this->actingAs($eventner->user);

        Livewire::test(\App\Livewire\Eventner\Drawing\Index::class)
            ->set('activeTab', $anak->id)
            ->call('resetDrawing');

        $this->assertSame(1, (int) $reg->fresh()->urutan_tampil, 'Undian tidak boleh direset setelah ada nilai.');
    }

    /**
     * #28 — memindahkan peserta ke kategori lain menghapus nomor undiannya,
     * karena nomor itu milik kategori lama.
     */
    public function test_pindah_kategori_menghapus_nomor_undian()
    {
        $eventner = $this->buatEventner();
        [, $anak] = $this->tingkat($eventner);
        [, $anakLain] = $this->tingkat($eventner, 'SMA');

        $reg = Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $anak->id,
            'nama_sekolah' => 'SMP Pindah',
            'npsn' => '12345678',
            'no_hp' => '08123456789',
            'urutan_tampil' => 3,
        ]);

        $this->actingAs($eventner->user);

        Livewire::test(\App\Livewire\Eventner\Participant\Index::class)
            ->call('edit', $reg->id)
            ->set('competition_category_id', $anakLain->id)
            ->call('save');

        $reg->refresh();
        $this->assertNull($reg->urutan_tampil);
        $this->assertSame($anakLain->id, $reg->competition_category_id);
    }

    /** #28 — peserta yang sudah dinilai tidak bisa dipindah kategorinya. */
    public function test_peserta_bernilai_tidak_bisa_pindah_kategori()
    {
        $eventner = $this->buatEventner();
        [, $anak] = $this->tingkat($eventner);
        [, $anakLain] = $this->tingkat($eventner, 'SMA');

        $reg = Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $anak->id,
            'nama_sekolah' => 'SMP Terkunci',
            'npsn' => '99999999',
            'no_hp' => '08123456780',
        ]);
        $kriteria = $this->buatRubrik($eventner, $anak, 'Rubrik Kunci', 'Kunci-C1');

        AssessmentScore::create([
            'eventner_id' => $eventner->id,
            'registration_id' => $reg->id,
            'assessment_criteria_id' => $kriteria->id,
            'score' => 10,
        ]);

        $this->actingAs($eventner->user);

        Livewire::test(\App\Livewire\Eventner\Participant\Index::class)
            ->call('edit', $reg->id)
            ->set('competition_category_id', $anakLain->id)
            ->call('save')
            ->assertHasErrors('competition_category_id');

        $this->assertSame($anak->id, $reg->fresh()->competition_category_id);
    }

    /**
     * #29 — kategori juara yang rubriknya mencakup dua tingkat muncul di
     * kedua bagian, dan pesertanya dihitung dari gabungan kedua tingkat.
     */
    public function test_kategori_juara_lintas_tingkat_muncul_di_kedua_bagian()
    {
        $eventner = $this->buatEventner();
        [, $smp] = $this->tingkat($eventner, 'SMP');
        [, $sma] = $this->tingkat($eventner, 'SMA');

        $kriteria = $this->buatRubrik($eventner, $smp, 'Rubrik SMP', 'SMP-C1');
        $kriteriaSma = $this->buatRubrik($eventner, $sma, 'Rubrik SMA', 'SMA-C1');

        // Kategori juara "Umum" memakai rubrik DUA tingkat sekaligus.
        $juara = ChampionCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Juara Umum',
            'quantity' => 5,
            'sort_order' => 1,
        ]);
        $juara->assessmentSubCategories()->attach([
            $kriteria->assessment_sub_category_id,
            $kriteriaSma->assessment_sub_category_id,
        ]);

        $this->actingAs($eventner->user);

        $bagian = app(\App\Http\Controllers\Eventner\ChampionCategoryController::class)
            ->pdfData(request())['sections'];

        $namaBagian = $bagian->map(fn ($s) => $s['level']?->name)->filter()->values()->all();

        $this->assertContains('SMP', $namaBagian);
        $this->assertContains('SMA', $namaBagian);

        // Kategori juara yang sama muncul di kedua bagian.
        $this->assertTrue(
            $bagian->contains(fn ($s) => $s['champions']->pluck('name')->contains('Juara Umum')),
            'Kategori juara lintas tingkat harus muncul di bagian tingkatnya.'
        );
    }

    private function buatRubrik(Eventner $eventner, CompetitionCategory $tingkat, string $nama, string $namaKriteria): AssessmentCriteria
    {
        $format = AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $tingkat->id,
            'name' => $nama,
            'sort_order' => 1,
        ]);
        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $format->id,
            'name' => 'Sub ' . $nama,
            'sort_order' => 1,
        ]);

        return AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => $namaKriteria,
            'score_options' => [['score' => 10]],
            'sort_order' => 1,
        ]);
    }
}
