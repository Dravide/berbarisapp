<?php

namespace Tests\Feature;

use App\Livewire\Public\MagicLink\Registration as MagicLink;
use App\Livewire\Public\Registration\Create as RegistrationCreate;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\RegistrationField;
use App\Models\RegistrationFieldValue;
use App\Services\WilayahService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Field wilayah: dropdown berjenjang dari api.datawilayah.com.
 *
 * Yang dijaga di sini bukan cuma "dropdownnya muncul", tapi dua hal yang
 * gampang rusak tanpa disadari:
 *
 *  - Kedalaman dropdown mengikuti tingkat_perlombaan event (nasional → provinsi,
 *    tingkat provinsi → provinsi + kabupaten). Pemetaannya pencocokan teks,
 *    karena kolomnya memang teks bebas.
 *  - Nilai lama dari era teks bebas tetap bisa disimpan. Kalau rule validasinya
 *    terlalu ketat, pendaftar yang datanya sudah masuk tidak bisa menyimpan
 *    formulirnya sama sekali — kerusakan yang jauh lebih mahal daripada field
 *    yang dobel ejaan.
 *
 * API-nya di-fake per endpoint, jadi tes tidak menyentuh jaringan.
 */
class WilayahFieldTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $category;

    /** Apakah stub HTTP sudah dipasang untuk tes ini. */
    private bool $httpSiap = false;

    /** Tes yang ingin API-nya tumbang menyetel ini sebelum menyentuh form. */
    private bool $wilayahMati = false;

    protected function setUp(): void
    {
        parent::setUp();

        WilayahService::lupakanMemo();

        $this->eventner = Eventner::factory()->create([
            'status' => 'approved',
            'slug' => 'wilayah',
            'subdomain' => null,
            'tingkat_perlombaan' => 'Nasional',
            'tanggal_pendaftaran' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $parent = CompetitionCategory::factory()->for($this->eventner, 'eventner')->create();
        $this->category = CompetitionCategory::factory()->child($parent)
            ->for($this->eventner, 'eventner')
            ->create();

        RegistrationField::ensureDefaults($this->eventner);
    }

    /**
     * Pasang stub HTTP, sekali saja.
     *
     * Tidak dipasang di setUp() karena `Http::fake()` MENUMPUK stub, bukan
     * menggantinya: fixture yang dipasang lebih dulu selalu menang, sehingga
     * tes "API tumbang" tidak akan pernah benar-benar menguji apa pun.
     */
    private function siapkanHttp(): void
    {
        if ($this->httpSiap) {
            return;
        }

        $this->fakeWilayah($this->wilayahMati);
        $this->httpSiap = true;
    }

    /**
     * Fixture kecil: 3 provinsi, 2 kabupaten di Jawa Barat, 2 kecamatan di Bogor.
     *
     * `$mati` dipakai tes "API tumbang". Tidak bisa dilakukan dengan memanggil
     * Http::fake() kedua kalinya: stub Laravel menumpuk (bukan mengganti), jadi
     * fixture pertama tetap menang dan tesnya tidak menguji apa pun.
     */
    private function fakeWilayah(bool $mati = false): void
    {
        if ($mati) {
            Http::fake(['*' => fn () => Http::response('', 500)]);

            return;
        }

        Http::fake([
            '*/api/provinsi.json' => Http::response([
                'status' => 'success',
                'total' => 3,
                'data' => [
                    ['nama_wilayah' => 'JAWA BARAT', 'kode_wilayah' => '32', 'level_wilayah' => 'provinsi'],
                    ['nama_wilayah' => 'JAWA TENGAH', 'kode_wilayah' => '33', 'level_wilayah' => 'provinsi'],
                    ['nama_wilayah' => 'LAMPUNG', 'kode_wilayah' => '18', 'level_wilayah' => 'provinsi'],
                ],
            ], 200),

            '*/api/kabupaten_kota/32.json' => Http::response([
                'status' => 'success',
                'total' => 2,
                'data' => [
                    [
                        'nama_wilayah' => 'KAB. BOGOR', 'kode_wilayah' => '32.01',
                        'level_wilayah' => 'kabupaten_kota', 'kode_provinsi' => '32',
                    ],
                    [
                        'nama_wilayah' => 'KOTA BANDUNG', 'kode_wilayah' => '32.73',
                        'level_wilayah' => 'kabupaten_kota', 'kode_provinsi' => '32',
                    ],
                ],
            ], 200),

            '*/api/kecamatan/32.01.json' => Http::response([
                'status' => 'success',
                'total' => 2,
                'data' => [
                    [
                        'nama_wilayah' => 'Cibinong', 'kode_wilayah' => '32.01.01',
                        'level_wilayah' => 'kecamatan', 'kode_kabupaten_kota' => '32.01',
                    ],
                    [
                        'nama_wilayah' => 'Gunung Putri', 'kode_wilayah' => '32.01.02',
                        'level_wilayah' => 'kecamatan', 'kode_kabupaten_kota' => '32.01',
                    ],
                ],
            ], 200),
        ]);
    }

    private function field(string $key = 'asal_kabupaten'): RegistrationField
    {
        return RegistrationField::where('eventner_id', $this->eventner->id)
            ->where('field_key', $key)
            ->firstOrFail();
    }

    private function form(array $fieldValues = [])
    {
        $this->siapkanHttp();

        $komponen = Livewire::test(RegistrationCreate::class, ['slug' => $this->eventner->slug])
            ->set('step', 2);

        foreach ($fieldValues as $key => $nilai) {
            $komponen->set('fieldValues.' . $key, $nilai);
        }

        return $komponen;
    }

    private function daftar(array $fieldValues = [])
    {
        $this->siapkanHttp();

        $komponen = Livewire::test(RegistrationCreate::class, ['slug' => $this->eventner->slug])
            ->set('selectedCategories', [$this->category->id])
            ->set('npsn', '12345678')
            ->set('nama_sekolah', 'SMP Negeri 1 Uji')
            ->set('nama_pelatih', 'Pelatih Uji')
            ->set('no_hp', '08123456789')
            ->set('school_email', 'uji@example.test');

        foreach ($fieldValues as $key => $nilai) {
            $komponen->set('fieldValues.' . $key, $nilai);
        }

        return $komponen->call('submit');
    }

    // ── Bentuk field bawaan ─────────────────────────────────────────────

    /**
     * Field `asal_kabupaten` yang sudah ada di DB tiap event harus ikut jadi
     * tipe wilayah. Backfill-nya ada di migrasi; ensureDefaults() tidak
     * menimpa baris lama, jadi tanpa backfill tidak ada field wilayah sama sekali.
     */
    public function test_field_asal_kabupaten_bawaan_bertipe_wilayah()
    {
        $field = $this->field();

        $this->assertSame('wilayah', $field->type);
        $this->assertSame('auto', $field->wilayah_level);
    }

    // ── Kedalaman mengikuti tingkat lomba ───────────────────────────────

    /**
     * Tingkat provinsi → provinsi + kabupaten. Karena penyelenggara menulis
     * singkatan ("Jabar"), alias-nya harus dikenali.
     */
    public function test_tingkat_jabar_menampilkan_kabupaten()
    {
        $this->eventner->update(['tingkat_perlombaan' => 'Jabar']);

        $komponen = $this->form();

        $this->assertSame(2, $komponen->instance()->kedalamanWilayah($this->field()));

        $komponen->set('wilayah.asal_kabupaten.provinsi', '32');

        $komponen->assertSee('KAB. BOGOR');
        $komponen->assertSee('KOTA BANDUNG');
    }

    /** Tingkat nasional → provinsi saja, dan tidak ada panggilan API kabupaten. */
    public function test_tingkat_nasional_hanya_provinsi()
    {
        $komponen = $this->form();

        $this->assertSame(1, $komponen->instance()->kedalamanWilayah($this->field()));

        $komponen->set('wilayah.asal_kabupaten.provinsi', '32');

        $komponen->assertSee('JAWA BARAT');
        $komponen->assertDontSee('KAB. BOGOR');
    }

    /**
     * Teks tingkat yang tidak dikenal tidak boleh mematikan form — jatuh ke
     * provinsi saja, karena kolomnya teks bebas tanpa daftar tetap.
     */
    public function test_tingkat_tak_dikenal_hanya_provinsi()
    {
        $this->eventner->update(['tingkat_perlombaan' => 'Se-Jabodetabek']);

        $this->assertSame(1, $this->form()->instance()->kedalamanWilayah($this->field()));
    }

    /**
     * Kata "nasional" menang atas nama provinsi: "Tingkat Nasional" tidak boleh
     * terbaca sebagai provinsi apa pun dan membuka kabupaten.
     */
    public function test_teks_nasional_menang_atas_kata_lain()
    {
        $this->eventner->update(['tingkat_perlombaan' => 'Tingkat Nasional']);

        $this->assertSame(1, $this->form()->instance()->kedalamanWilayah($this->field()));
    }

    /** Wilayah dinamis: tingkat dipatok panitia, bukan ikut event. */
    public function test_field_wilayah_dinamis_tiga_tingkat()
    {
        $this->field()->update(['wilayah_level' => 'kecamatan']);

        $komponen = $this->form();

        $this->assertSame(3, $komponen->instance()->kedalamanWilayah($this->field()));

        $komponen->set('wilayah.asal_kabupaten.provinsi', '32')
            ->set('wilayah.asal_kabupaten.kabupaten', '32.01');

        $komponen->assertSee('Cibinong');
        $komponen->assertSee('Gunung Putri');
    }

    // ── Perilaku berjenjang ─────────────────────────────────────────────

    /** Ganti provinsi → kabupaten dari provinsi lama tidak boleh tertinggal. */
    public function test_ganti_provinsi_mengosongkan_kabupaten()
    {
        $this->field()->update(['wilayah_level' => 'kabupaten']);

        $komponen = $this->form()
            ->set('wilayah.asal_kabupaten.provinsi', '32')
            ->set('wilayah.asal_kabupaten.kabupaten', '32.01');

        $komponen->set('wilayah.asal_kabupaten.provinsi', '33');

        $this->assertSame('', $komponen->get('wilayah')['asal_kabupaten']['kabupaten']);
    }

    /**
     * Nilai simpan = kode + nama, digabung per tingkat. Inilah alasan tidak ada
     * satu pun pembaca lama (PDF, invoice, sertifikat, API) yang perlu diubah:
     * yang tercetak adalah kalimat yang sama seperti era teks bebas.
     */
    public function test_nilai_tersimpan_berisi_kode_dan_nama()
    {
        $this->field()->update(['wilayah_level' => 'kabupaten']);

        $this->form()
            ->set('wilayah.asal_kabupaten.provinsi', '32')
            ->set('wilayah.asal_kabupaten.kabupaten', '32.01');

        $this->assertSame(
            '32 - JAWA BARAT / 32.01 - KAB. BOGOR',
            Livewire::test(RegistrationCreate::class, ['slug' => $this->eventner->slug])
                ->set('step', 2)
                ->set('wilayah.asal_kabupaten.provinsi', '32')
                ->set('wilayah.asal_kabupaten.kabupaten', '32.01')
                ->get('fieldValues')['asal_kabupaten']
        );
    }

    /** Pilihan tersimpan disimpan ke registration_field_values saat submit. */
    public function test_submit_menyimpan_pilihan_wilayah()
    {
        $this->field()->update(['wilayah_level' => 'kabupaten']);

        $komponen = Livewire::test(RegistrationCreate::class, ['slug' => $this->eventner->slug])
            ->set('selectedCategories', [$this->category->id])
            ->set('npsn', '12345678')
            ->set('nama_sekolah', 'SMP Negeri 1 Uji')
            ->set('nama_pelatih', 'Pelatih Uji')
            ->set('no_hp', '08123456789')
            ->set('school_email', 'uji@example.test')
            ->set('wilayah.asal_kabupaten.provinsi', '32')
            ->set('wilayah.asal_kabupaten.kabupaten', '32.01');

        $komponen->call('submit')->assertHasNoErrors();

        $reg = Registration::where('eventner_id', $this->eventner->id)->firstOrFail();

        $this->assertSame(
            '32 - JAWA BARAT / 32.01 - KAB. BOGOR',
            $reg->getFieldValue('asal_kabupaten')
        );
    }

    // ── Validasi ────────────────────────────────────────────────────────

    /**
     * Kode karangan yang tidak nyambung induk-anaknya ditolak. Dropdown adalah
     * satu-satunya yang menjamin namanya resmi, jadi nilai harus benar-benar
     * berasal dari sana.
     */
    public function test_kode_tidak_berurutan_ditolak()
    {
        $this->field()->update(['wilayah_level' => 'kabupaten']);

        $this->daftar(['asal_kabupaten' => '32 - JAWA BARAT / 33.01 - KAB. BOGOR'])
            ->assertHasErrors(['fieldValues.asal_kabupaten']);
    }

    /** Melebihi tingkat yang diminta event juga ditolak. */
    public function test_kode_melebihi_kedalaman_ditolak()
    {
        // Tingkat Nasional → provinsi saja.
        $this->daftar(['asal_kabupaten' => '32 - JAWA BARAT / 32.01 - KAB. BOGOR'])
            ->assertHasErrors(['fieldValues.asal_kabupaten']);
    }

    /**
     * Nilai lama dari era teks bebas tetap lolos. Ini jaring pengaman paling
     * penting di sini: tanpa ini, pendaftar yang datanya sudah masuk tidak bisa
     * menyimpan formulirnya lagi.
     */
    public function test_nilai_lama_teks_bebas_masih_lolos()
    {
        $this->daftar(['asal_kabupaten' => 'Bandar Lampung'])->assertHasNoErrors();
    }

    // ── Portal magic link ───────────────────────────────────────────────

    /** Portal memuat ulang pilihan yang tersimpan, bukan dropdown kosong. */
    public function test_magic_link_menampilkan_pilihan_tersimpan()
    {
        $this->siapkanHttp();
        $this->field()->update(['wilayah_level' => 'kabupaten']);

        $reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->category->id,
            'magic_token' => 'tokenwilayah12345',
        ]);

        RegistrationFieldValue::create([
            'registration_id' => $reg->id,
            'registration_field_id' => $this->field()->id,
            'value' => '32 - JAWA BARAT / 32.01 - KAB. BOGOR',
        ]);

        $komponen = Livewire::test(MagicLink::class, ['token' => 'tokenwilayah12345']);
        $this->assertSame('32', $komponen->get('wilayah')['asal_kabupaten']['provinsi']);
        $this->assertSame('32.01', $komponen->get('wilayah')['asal_kabupaten']['kabupaten']);
    }

    // ── API mati ────────────────────────────────────────────────────────

    /**
     * API tumbang tidak boleh membuat pendaftaran mustahil. Dropdown turun jadi
     * input teks, dan nilainya tetap sah karena kolomnya teks bebas.
     */
    public function test_api_mati_turun_ke_teks_biasa()
    {
        $this->wilayahMati = true;

        $komponen = $this->form();

        $komponen->assertSee('Daftar wilayah sedang tidak bisa dimuat');
        $komponen->assertDontSee('— Pilih Provinsi —', false);

        $this->assertSame([], $komponen->instance()->opsiWilayah('asal_kabupaten', 'provinsi'));

        // Nilai teks tetap bisa masuk.
        $this->daftar(['asal_kabupaten' => 'Bandar Lampung'])->assertHasNoErrors();
    }
}
