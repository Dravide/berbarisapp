<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\RegistrationField;
use App\Models\RegistrationFieldValue;
use App\Models\User;
use App\Support\DataSekolah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
/**
 * Rekap sekolah + kartu magic link sekolah.
 *
 * Pengelompokannya ada di App\Support\DataSekolah (murni, tanpa DB) dan yang
 * diuji paling banyak di sini adalah kelas itu: satu baris per SEKOLAH, bukan
 * per pasukan, adalah inti fitur ini — kalau salah, rekapnya diam-diam
 * melebihkan jumlah pasukan tanpa ada yang sadar.
 */
class ParticipantDataSekolahTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Eventner $eventner;
    private CompetitionCategory $category;
    private CompetitionCategory $parent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);

        $this->parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'LOBB',
        ]);
        $this->category = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->parent->id,
            'name' => 'U13',
        ]);
    }

    private function makeRegistration(string $school, array $attrs = [], ?CompetitionCategory $category = null): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create(array_merge([
            'competition_category_id' => ($category ?? $this->category)->id,
            'nama_sekolah' => $school,
        ], $attrs));
    }

    /** Anggota pasukan. `Participant` tidak punya factory — dibuat langsung. */
    private function peserta(Registration $reg, int $jumlah): void
    {
        for ($i = 0; $i < $jumlah; $i++) {
            Participant::create([
                'registration_id' => $reg->id,
                'nama' => 'Anggota '.$reg->id.'-'.$i,
            ]);
        }
    }

    /** Koleksi registrasi siap-kelompokkan: eager-load yang sama dengan controller. */
    private function kumpulkan(): \Illuminate\Support\Collection
    {
        return Registration::with(['participants', 'fieldValues'])
            ->where('eventner_id', $this->eventner->id)
            ->where('status_berkas', '!=', 'dibatalkan')
            ->orderBy('nama_sekolah')
            ->orderBy('id')
            ->get();
    }

    // ── Kelas Support (murni) ────────────────────────────────────────────

    /** Inti fitur: 3 pasukan di 2 kategori = SATU baris, bukan tiga. */
    public function test_tiga_pasukan_dua_kategori_jadi_satu_baris()
    {
        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->parent->id,
            'name' => 'U16',
        ]);

        $a = $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111']);
        $b = $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111'], $lain);
        $c = $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111'], $lain);

        $this->peserta($a, 10);
        $this->peserta($b, 12);
        $this->peserta($c, 8);

        $hasil = DataSekolah::kelompokkan($this->kumpulkan());

        $this->assertCount(1, $hasil);
        $this->assertSame(3, $hasil[0]['jumlah_pasukan']);
        $this->assertSame(2, $hasil[0]['jumlah_kategori']);
        $this->assertSame(30, $hasil[0]['jumlah_anggota']);
        $this->assertSame($a->id, $hasil[0]['registrasi_induk']->id);
    }

    /** NPSN yang menentukan: nama sama, NPSN beda = dua sekolah. */
    public function test_dua_npsn_beda_dengan_nama_sama_tetap_dua_baris()
    {
        $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111']);
        $this->makeRegistration('SMP Negeri 1', ['npsn' => '22222222']);

        $hasil = DataSekolah::kelompokkan($this->kumpulkan());

        $this->assertCount(2, $hasil);
    }

    /** Tanpa NPSN, kuncinya nama sekolah — sekolah berbeda jangan menabrak jadi satu. */
    public function test_tanpa_npsn_sekolah_berbeda_tetap_terpisah()
    {
        $this->makeRegistration('SMP Negeri 1', ['npsn' => null]);
        $this->makeRegistration('SMP Negeri 2', ['npsn' => null]);
        $this->makeRegistration('SMP Negeri 3', ['npsn' => null]);

        $this->assertCount(3, DataSekolah::kelompokkan($this->kumpulkan()));
    }

    /** Tanpa NPSN dan nama sama: memang satu sekolah, jadi satu baris. */
    public function test_tanpa_npsn_dengan_nama_sama_jadi_satu_baris()
    {
        $this->makeRegistration('SMP Negeri 1', ['npsn' => null, 'label_pasukan' => 'A']);
        $this->makeRegistration('SMP Negeri 1', ['npsn' => null, 'label_pasukan' => 'B']);
        $this->makeRegistration('SMP Negeri 1', ['npsn' => null, 'label_pasukan' => 'C']);

        $hasil = DataSekolah::kelompokkan($this->kumpulkan());

        $this->assertCount(1, $hasil);
        $this->assertSame(3, $hasil[0]['jumlah_pasukan']);
    }

    /** NPSN kosong yang berbeda ejaan (" 111 " vs "111") tetap satu sekolah. */
    public function test_npsn_dengan_spasi_dianggap_sama()
    {
        $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111']);
        $this->makeRegistration('SMP Negeri 1', ['npsn' => ' 11111111 ']);

        $this->assertCount(1, DataSekolah::kelompokkan($this->kumpulkan()));
    }

    /** Baris yang digantikan tidak boleh menambah pasukan, anggota, atau status. */
    public function test_registrasi_dibatalkan_tidak_dihitung()
    {
        $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111']);
        $batal = $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111', 'status_berkas' => 'dibatalkan']);
        $this->peserta($batal, 5);

        $hasil = DataSekolah::kelompokkan($this->kumpulkan());

        $this->assertCount(1, $hasil);
        $this->assertSame(1, $hasil[0]['jumlah_pasukan']);
        $this->assertSame(0, $hasil[0]['jumlah_anggota']);
    }

    /** Satu kata untuk satu tabel: yang paling jauh menang. */
    public function test_status_mengambil_yang_paling_jauh()
    {
        $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111', 'status_berkas' => 'booking']);
        $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111', 'status_berkas' => 'draft']);
        $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111', 'status_berkas' => 'Terverifikasi']);

        $hasil = DataSekolah::kelompokkan($this->kumpulkan());

        $this->assertSame('Terverifikasi', $hasil[0]['status']);
        $this->assertSame('Terverifikasi', $hasil[0]['label_status']);
    }

    /** `confirmed` dan `Menunggu` sama-sama "Menunggu Verifikasi" bagi panitia. */
    public function test_label_status_menyamakan_menunggu_dan_confirmed()
    {
        $this->assertSame('Menunggu Verifikasi', DataSekolah::labelStatus('Menunggu'));
        $this->assertSame('Menunggu Verifikasi', DataSekolah::labelStatus('confirmed'));
    }

    /** Kontak diambil dari pasukan mana pun yang mengisinya, bukan baris induk saja. */
    public function test_kontak_diambil_dari_pasukan_yang_mengisinya()
    {
        $this->makeRegistration('SMP Negeri 1', [
            'npsn' => '11111111',
            'nama_pelatih' => 'Budi',
            'no_hp' => '',
            'school_email' => null,
        ]);
        $this->makeRegistration('SMP Negeri 1', [
            'npsn' => '11111111',
            'nama_pelatih' => '',
            'no_hp' => '08123456789',
            'school_email' => 'sekolah@example.com',
        ]);

        $hasil = DataSekolah::kelompokkan($this->kumpulkan());

        $this->assertSame('Budi', $hasil[0]['pelatih']);
        $this->assertSame('08123456789', $hasil[0]['no_hp']);
        $this->assertSame('sekolah@example.com', $hasil[0]['email']);
    }

    /** Field Kabupaten/Kota buatan panitia: dibaca dari registration_field_values. */
    public function test_kabupaten_dibaca_dari_field_buatan_panitia()
    {
        // Eventner::created sudah menanam field ini (RegistrationField::defaults),
        // jadi yang diambil baris yang ada — bukan dibuat baru.
        $field = RegistrationField::where('eventner_id', $this->eventner->id)
            ->where('field_key', 'asal_kabupaten')
            ->firstOrFail();

        $reg = $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111']);
        RegistrationFieldValue::create([
            'registration_id' => $reg->id,
            'registration_field_id' => $field->id,
            // Kode BPS era datawilayah — harus tampil sebagai namanya saja.
            'value' => '32 - JAWA BARAT / 32.04 - KAB. BANDUNG',
        ]);

        $fields = RegistrationField::forEventner($this->eventner);
        $hasil = DataSekolah::kelompokkan($this->kumpulkan(), DataSekolah::fieldKabupatenId($fields));

        // Provinsi ikut terbaca karena satu field menyimpan kedua tingkat;
        // itu memang isi fieldnya, bukan kesalahan tampil.
        $this->assertSame('JAWA BARAT, KAB. BANDUNG', $hasil[0]['kabupaten']);
    }

    /** Nilai wilayah era teks bebas tidak dipaksa jadi format kode. */
    public function test_kabupaten_teks_bebas_dikembalikan_apa_adanya()
    {
        $field = RegistrationField::where('eventner_id', $this->eventner->id)
            ->where('field_key', 'asal_kabupaten')
            ->firstOrFail();

        $reg = $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111']);
        RegistrationFieldValue::create([
            'registration_id' => $reg->id,
            'registration_field_id' => $field->id,
            'value' => 'Cianjur',
        ]);

        $fields = RegistrationField::forEventner($this->eventner);
        $hasil = DataSekolah::kelompokkan($this->kumpulkan(), DataSekolah::fieldKabupatenId($fields));

        $this->assertSame('Cianjur', $hasil[0]['kabupaten']);
    }

    /** Fieldnya dihapus panitia: kolomnya kosong, bukan error. */
    public function test_field_kabupaten_hilang_tidak_error()
    {
        RegistrationField::where('eventner_id', $this->eventner->id)
            ->where('field_key', 'asal_kabupaten')
            ->delete();

        $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111']);

        $fields = RegistrationField::forEventner($this->eventner);
        $fieldId = DataSekolah::fieldKabupatenId($fields);

        $this->assertNull($fieldId);

        $hasil = DataSekolah::kelompokkan($this->kumpulkan(), $fieldId);

        $this->assertSame('', $hasil[0]['kabupaten']);
    }

    /** Field nonaktif sama saja dengan tidak ada — jangan dibaca diam-diam. */
    public function test_field_kabupaten_nonaktif_diabaikan()
    {
        RegistrationField::where('eventner_id', $this->eventner->id)
            ->where('field_key', 'asal_kabupaten')
            ->update(['is_active' => false]);

        $this->assertNull(DataSekolah::fieldKabupatenId(RegistrationField::forEventner($this->eventner)));
    }

    /** Hasil akhir tidak membocorkan penanda kerja ke pemanggil. */
    public function test_hasil_tidak_memuat_kunci_internal()
    {
        $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111']);

        $hasil = DataSekolah::kelompokkan($this->kumpulkan());

        $this->assertArrayNotHasKey('registrasi', $hasil[0]);
        $this->assertArrayNotHasKey('kategori', $hasil[0]);
    }

    // ── Rute & PDF ───────────────────────────────────────────────────────

    public function test_unduh_tabel_data_sekolah()
    {
        $this->makeRegistration('SMP Negeri 1');

        $response = $this->actingAs($this->user)
            ->get(route('eventner.participants.data-sekolah'));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('Data_Sekolah', $response->headers->get('content-disposition'));
    }

    public function test_unduh_kartu_sekolah()
    {
        $this->makeRegistration('SMP Negeri 1');

        $this->actingAs($this->user)
            ->get(route('eventner.participants.kartu-sekolah'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    /** Rekap tidak ikut kategori yang sedang dibuka — semua kategori event. */
    public function test_tabel_data_sekolah_memuat_semua_kategori()
    {
        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->parent->id,
            'name' => 'U16',
        ]);

        $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111']);
        $this->makeRegistration('SMP Negeri 2', ['npsn' => '22222222'], $lain);

        $ini = DataSekolah::kelompokkan($this->kumpulkan());

        $this->assertCount(2, $ini);
    }

    /** ?registrasi= membatasi kartu ke satu sekolah saja. */
    public function test_kartu_bisa_dibatasi_satu_sekolah()
    {
        $satu = $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111']);
        $this->makeRegistration('SMP Negeri 2', ['npsn' => '22222222']);

        $response = $this->actingAs($this->user)
            ->get(route('eventner.participants.kartu-sekolah', ['registrasi' => $satu->id]));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        // Nama berkas menyebut sekolahnya, jadi panitia tahu kartu siapa ini.
        $this->assertStringContainsString('SMP_Negeri_1', $response->headers->get('content-disposition'));
    }

    /** Id registrasi event lain tidak boleh terbaca. */
    public function test_kartu_menolak_registrasi_event_lain()
    {
        $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111']);

        $otherUser = User::factory()->eventner()->create(['is_active' => true]);
        $otherEventner = Eventner::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'approved',
        ]);
        $otherParent = CompetitionCategory::factory()->create([
            'eventner_id' => $otherEventner->id,
            'parent_id' => null,
        ]);
        $otherCategory = CompetitionCategory::factory()->create([
            'eventner_id' => $otherEventner->id,
            'parent_id' => $otherParent->id,
        ]);
        $foreign = Registration::factory()->for($otherEventner, 'eventner')->create([
            'competition_category_id' => $otherCategory->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('eventner.participants.kartu-sekolah', ['registrasi' => $foreign->id]))
            ->assertNotFound();
    }

    /** Tanpa pendaftar: 404, bukan PDF kosong yang membingungkan. */
    public function test_tanpa_pendaftar_menghasilkan_404()
    {
        $this->actingAs($this->user)
            ->get(route('eventner.participants.data-sekolah'))
            ->assertNotFound();

        $this->actingAs($this->user)
            ->get(route('eventner.participants.kartu-sekolah'))
            ->assertNotFound();
    }

    /** Registrasi dibatalkan saja pun tidak cukup untuk dianggap "ada pendaftar". */
    public function test_hanya_registrasi_dibatalkan_tetap_404()
    {
        $this->makeRegistration('SMP Negeri 1', ['status_berkas' => 'dibatalkan']);

        $this->actingAs($this->user)
            ->get(route('eventner.participants.data-sekolah'))
            ->assertNotFound();
    }

    public function test_tamu_tidak_bisa_mengunduh_data_sekolah()
    {
        $this->makeRegistration('SMP Negeri 1');

        $this->get(route('eventner.participants.data-sekolah'))->assertRedirect();
        $this->get(route('eventner.participants.kartu-sekolah'))->assertRedirect();
    }

    public function test_halaman_peserta_menyediakan_menu_data_sekolah()
    {
        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Eventner\Participant\Index::class)
            ->assertSee('Data Sekolah')
            ->assertSee('Semua Sekolah dalam Satu Tabel')
            ->assertSee('Kartu Magic Link per Sekolah');
    }
}
