<?php

namespace Tests\Feature;

use App\Livewire\Public\Registration\Create as RegistrationCreate;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Kategori induk tidak boleh jadi tujuan pendaftaran.
 *
 * Daftar kategori di formulir sudah menyaring induk yang punya anak, tapi id
 * kategori datang dari DOM — dikirim Livewire, halaman basi, atau muatan
 * buatan. Sebelum ini toggleCategory() dan submit() menerima id induk apa
 * adanya, jadi pendaftaran bisa mendarat di tingkat induk, bukan tingkat lomba.
 */
class RegistrationCategorySelectableTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;
    private CompetitionCategory $induk;
    private CompetitionCategory $anak;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->eventner()->create(['is_active' => true]);

        $this->eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'slug' => 'selectable-uji',
            'status' => 'approved',
            'subdomain' => null,
            // Tanpa deadline, computeRegistrationStatus() menjawab 'closed'
            // dan blade merender cabang "Pendaftaran Ditutup" — bukan form.
            'tanggal_pendaftaran' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $this->induk = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'PBB Putra',
        ]);
        $this->anak = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->induk->id,
            'name' => 'Regu Inti',
            'max_registrations_per_school' => 3,
        ]);
    }

    private function komponen()
    {
        return Livewire::test(RegistrationCreate::class, ['slug' => $this->eventner->slug]);
    }

    /** Induk yang punya anak tidak masuk daftar pilihan. */
    public function test_induk_beranak_tidak_tampil_di_daftar_kategori()
    {
        $ids = collect($this->komponen()->viewData('categories'))->pluck('id')->all();

        $this->assertContains($this->anak->id, $ids);
        $this->assertNotContains($this->induk->id, $ids);
    }

    /**
     * Induk yang PUNYA anak ditolak walau id-nya dikirim langsung.
     *
     * Menyaring di view saja tidak menutup ini: id dari DOM tetap dipakai
     * toggleCategory() tanpa cek hierarki.
     */
    public function test_toggle_menolak_induk_yang_punya_anak()
    {
        $komponen = $this->komponen()->call('toggleCategory', $this->induk->id);

        $this->assertSame([], $komponen->get('selectedCategories'));
    }

    /** Gerbang terakhir: walau keranjang diisi paksa, induk tidak dibuatkan pendaftaran. */
    public function test_submit_tidak_membuat_pendaftaran_untuk_induk()
    {
        $this->komponen()
            ->set('selectedCategories', [$this->induk->id])
            ->set('npsn', '12345678')
            ->set('nama_sekolah', 'SMP Negeri Uji')
            ->set('nama_pelatih', 'Pelatih Uji')
            ->set('no_hp', '08123456789')
            ->set('school_email', 'uji@example.test')
            ->call('submit');

        $this->assertSame(0, Registration::where('competition_category_id', $this->induk->id)->count());
    }

    /**
     * Data flat lama tetap didukung: induk yang TIDAK punya anak masih bisa
     * dipilih. Scope selectable() hanya menyaring induk ber-anak.
     */
    public function test_induk_tanpa_anak_masih_bisa_dipilih()
    {
        $flat = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'Kategori Lama',
            'max_registrations_per_school' => 2,
        ]);

        $ids = collect($this->komponen()->viewData('categories'))->pluck('id')->all();
        $this->assertContains($flat->id, $ids);

        $komponen = $this->komponen()->call('toggleCategory', $flat->id);
        $this->assertSame([$flat->id], $komponen->get('selectedCategories'));
    }

    /** Tingkat (anak) tetap bisa dipilih seperti sebelumnya. */
    public function test_anak_masih_bisa_dipilih_dan_didaftarkan()
    {
        $komponen = $this->komponen()->call('toggleCategory', $this->anak->id);
        $this->assertSame([$this->anak->id], $komponen->get('selectedCategories'));

        $komponen->set('npsn', '12345678')
            ->set('nama_sekolah', 'SMP Negeri Uji')
            ->set('nama_pelatih', 'Pelatih Uji')
            ->set('no_hp', '08123456789')
            ->set('school_email', 'uji@example.test')
            ->call('submit');

        $this->assertSame(1, Registration::where('competition_category_id', $this->anak->id)->count());
    }

    /** Kategori event lain tetap ditolak (regresi guard tenant yang lama). */
    public function test_kategori_event_lain_tetap_ditolak()
    {
        $userLain = User::factory()->eventner()->create(['is_active' => true]);
        $eventLain = Eventner::factory()->create([
            'user_id' => $userLain->id,
            'slug' => 'selectable-lain',
            'status' => 'approved',
            'subdomain' => null,
        ]);
        $kategoriLain = CompetitionCategory::factory()->child()->create([
            'eventner_id' => $eventLain->id,
        ]);

        $komponen = $this->komponen()->call('toggleCategory', $kategoriLain->id);

        $this->assertSame([], $komponen->get('selectedCategories'));
    }

    /**
     * Grup bersifat pool INTERNAL: peserta mendaftar ke mata lomba seperti
     * biasa, dan panitia yang membelah mereka setelah pendaftaran. Nama grup
     * tidak boleh muncul di form pendaftaran, dan tidak ada kolom grup yang
     * bisa disetel dari DOM.
     */
    public function test_grup_tidak_muncul_di_formulir_pendaftaran()
    {
        CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->anak->id,
            'name' => 'Grup A',
            'sort_order' => 1,
        ]);
        CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->anak->id,
            'name' => 'Grup B',
            'sort_order' => 2,
        ]);

        $this->komponen()
            ->assertDontSee('Grup A')
            ->assertDontSee('Grup B')
            ->assertSee('Regu Inti');
    }

    /** Pendaftaran tidak pernah menetapkan grup, sekalipun tingkatnya bergrup. */
    public function test_pendaftaran_tidak_mengisi_grup()
    {
        CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->anak->id,
            'name' => 'Grup A',
        ]);

        $komponen = $this->komponen()
            ->call('toggleCategory', $this->anak->id)
            ->set('npsn', '12345678')
            ->set('nama_sekolah', 'SMP Negeri Uji')
            ->set('nama_pelatih', 'Pelatih Uji')
            ->set('no_hp', '08123456789')
            ->set('school_email', 'uji@example.test');

        // Tak ada jalur dari DOM untuk menetapkan grup: komponennya sendiri
        // tidak punya properti itu, jadi tak ada wire:model yang bisa dipalsukan.
        $this->assertStringNotContainsString('competition_group_id', $komponen->html());

        $komponen->call('submit');

        $reg = Registration::where('competition_category_id', $this->anak->id)->first();
        $this->assertNotNull($reg);
        $this->assertNull($reg->competition_group_id, 'Pendaftaran publik menetapkan grup.');
    }

    /**
     * Tingkat yang punya grup tetap muncul di dropdown pendaftaran — grupnya
     * yang tidak muncul, bukan tingkatnya.
     */
    public function test_tingkat_bergrup_tetap_bisa_dipilih()
    {
        CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->anak->id,
            'name' => 'Grup A',
        ]);

        $komponen = $this->komponen()->call('toggleCategory', $this->anak->id);

        $this->assertSame([$this->anak->id], $komponen->get('selectedCategories'));
    }
}
