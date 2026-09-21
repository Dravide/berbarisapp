<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Participant\Index as ParticipantIndex;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Panitia tidak boleh menambahkan pendaftar ke kategori induk.
 *
 * Halaman peserta panitia menyaring tab ke kategori tingkat, tapi
 * openModal($categoryId) dan switchTab($categoryId) menerima id dari DOM apa
 * adanya, dan save() hanya memvalidasi kepemilikan event. Akibatnya panitia
 * bisa membuat pendaftaran yang mendarat di tingkat induk — bukan tingkat lomba.
 *
 * Catatan: id induk SAH dipakai di halaman lain (cetak QR, daftar ulang) untuk
 * berarti "semua tingkat di bawahnya". Yang ditolak di sini hanya pemakaiannya
 * sebagai tujuan pendaftaran.
 */
class ParticipantCategoryScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Eventner $eventner;
    private CompetitionCategory $induk;
    private CompetitionCategory $anak;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->eventner()->create(['is_active' => true]);

        $this->eventner = Eventner::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);
        $this->user->update(['eventner_id' => $this->eventner->id]);

        $this->induk = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'LOBB',
        ]);
        $this->anak = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->induk->id,
            'name' => 'U13 - SD / MI',
        ]);
    }

    private function komponen()
    {
        return Livewire::actingAs($this->user)->test(ParticipantIndex::class);
    }

    private function isian(array $override = []): array
    {
        return array_merge([
            'nama_sekolah' => 'SMP Negeri Uji',
            'npsn' => '12345678',
            'nama_pelatih' => 'Pelatih Uji',
            'no_hp' => '08123456789',
            'school_email' => 'uji@example.test',
            'jumlah_pasukan' => 1,
        ], $override);
    }

    /** Tab hanya berisi kategori tingkat; induk ber-anak tidak muncul. */
    public function test_tab_tidak_memuat_induk_yang_punya_anak()
    {
        $ids = collect($this->komponen()->get('categories'))->pluck('id')->all();

        $this->assertContains($this->anak->id, $ids);
        $this->assertNotContains($this->induk->id, $ids);
    }

    /** Tab induk yang dikirim langsung tidak dipakai. */
    public function test_switch_tab_menolak_induk_yang_punya_anak()
    {
        $komponen = $this->komponen()->call('switchTab', $this->induk->id);

        $this->assertSame($this->anak->id, $komponen->get('activeTab'));
    }

    /** openModal(induk) tidak boleh mengisi kategori tujuan dengan id induk. */
    public function test_open_modal_tidak_mengisi_kategori_dengan_induk()
    {
        $this->komponen()
            ->call('openModal', $this->induk->id)
            ->assertSet('competition_category_id', '');
    }

    public function test_open_modal_mengisi_kategori_dengan_anak()
    {
        $this->komponen()
            ->call('openModal', $this->anak->id)
            ->assertSet('competition_category_id', (string) $this->anak->id);
    }

    /** Walau id induk di-set paksa, save() harus menolaknya. */
    public function test_save_menolak_kategori_induk()
    {
        $this->komponen()
            ->set($this->isian(['competition_category_id' => $this->induk->id]))
            ->call('save')
            ->assertHasErrors('competition_category_id');

        $this->assertSame(0, Registration::where('competition_category_id', $this->induk->id)->count());
    }

    public function test_save_menerima_kategori_tingkat()
    {
        $this->komponen()
            ->set($this->isian(['competition_category_id' => $this->anak->id]))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Registration::where('competition_category_id', $this->anak->id)->count());
    }

    /** Kategori event lain tetap ditolak (regresi guard tenant yang lama). */
    public function test_save_menolak_kategori_event_lain()
    {
        $userLain = User::factory()->eventner()->create(['is_active' => true]);
        $eventLain = Eventner::factory()->create([
            'user_id' => $userLain->id,
            'status' => 'approved',
        ]);
        $kategoriLain = CompetitionCategory::factory()->child()->create([
            'eventner_id' => $eventLain->id,
        ]);

        $this->komponen()
            ->set($this->isian(['competition_category_id' => $kategoriLain->id]))
            ->call('save')
            ->assertHasErrors('competition_category_id');

        $this->assertSame(0, Registration::where('competition_category_id', $kategoriLain->id)->count());
    }

    /**
     * Event dengan kategori flat lama tetap bisa dikelola: sebelumnya tab
     * menyaring `parent_id` saja, sehingga event begini tampil tanpa tab
     * walau pendaftarannya ada.
     */
    public function test_kategori_flat_lama_tetap_bisa_dipakai()
    {
        $userFlat = User::factory()->eventner()->create(['is_active' => true]);
        $eventFlat = Eventner::factory()->create([
            'user_id' => $userFlat->id,
            'status' => 'approved',
        ]);
        $userFlat->update(['eventner_id' => $eventFlat->id]);

        $flat = CompetitionCategory::factory()->create([
            'eventner_id' => $eventFlat->id,
            'parent_id' => null,
            'name' => 'Kategori Lama',
        ]);

        $komponen = Livewire::actingAs($userFlat)->test(ParticipantIndex::class);

        $ids = collect($komponen->get('categories'))->pluck('id')->all();
        $this->assertContains($flat->id, $ids, 'Kategori flat lama harus tetap muncul sebagai tab.');
        $this->assertSame($flat->id, $komponen->get('activeTab'));

        $komponen->set($this->isian(['competition_category_id' => $flat->id]))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Registration::where('competition_category_id', $flat->id)->count());
    }
}
