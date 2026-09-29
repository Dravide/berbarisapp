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

    /** Koleksi registrasi siap-kelompokkan: eager-load & urutan yang sama dengan controller. */
    private function kumpulkan(): \Illuminate\Support\Collection
    {
        return Registration::with(['participants', 'fieldValues'])
            ->where('eventner_id', $this->eventner->id)
            ->where('status_berkas', '!=', 'dibatalkan')
            ->orderByRaw('COALESCE(urutan_tampil, 999999)')
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

    /** Tautan menambah kunci `url` tanpa merusak hasil pengelompokan. */
    public function test_dengan_tautan_menambah_url_per_sekolah()
    {
        $reg = $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111']);

        $hasil = DataSekolah::denganTautan(DataSekolah::kelompokkan($this->kumpulkan()));

        $this->assertSame(route('magic.link', $reg->magic_token), $hasil[0]['url']);
        $this->assertSame(1, $hasil[0]['jumlah_pasukan']);
    }

    /**
     * Token kosong: `url` jadi string kosong, bukan route tanpa token.
     *
     * Registrasi dibuat dengan `make()` — `create()` melewati event `creating`
     * yang selalu mengisi magic_token, jadi data tanpa token hanya bisa
     * ditiru lewat instance yang belum disimpan (baris lama memang bisa begitu).
     */
    public function test_dengan_tautan_dengan_token_kosong()
    {
        $reg = Registration::factory()->make(['magic_token' => null]);

        $hasil = DataSekolah::denganTautan(collect([[
            'nama_sekolah' => 'SMP Negeri 1',
            'registrasi_induk' => $reg,
        ]]));

        $this->assertSame('', $hasil[0]['url']);
    }

    /** `denganQr` tahan terhadap url kosong — jangan render QR dari string kosong. */
    public function test_dengan_qr_melewati_url_kosong()
    {
        $hasil = DataSekolah::denganQr(collect([['url' => '']]));

        $this->assertNull($hasil[0]['qr']);
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

    /** Tabel rekap memuat magic link tiap sekolah, bukan hanya kartunya. */
    public function test_tabel_data_sekolah_memuat_magic_link()
    {
        $reg = $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111']);

        $html = $this->renderRekap();

        $this->assertStringContainsString('Tautan Portal Sekolah', $html);
        $this->assertStringContainsString(route('magic.link', $reg->magic_token), $html);
    }

    /** Satu tautan per sekolah — tiga pasukan tidak mencetak tiga tautan berbeda. */
    public function test_tabel_data_sekolah_menautkan_satu_tautan_per_sekolah()
    {
        $a = $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111', 'label_pasukan' => 'A']);
        $b = $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111', 'label_pasukan' => 'B']);
        $c = $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111', 'label_pasukan' => 'C']);

        $html = $this->renderRekap();

        // Baris induk = id terkecil; tautan pasukan lain tidak ikut tampil.
        $this->assertStringContainsString(route('magic.link', $a->magic_token), $html);
        $this->assertStringNotContainsString(route('magic.link', $b->magic_token), $html);
        $this->assertStringNotContainsString(route('magic.link', $c->magic_token), $html);
    }

    /** Magic link kosong (data lama) tetap tercetak, dengan keterangan. */
    public function test_tabel_data_sekolah_tanpa_magic_link_tetap_tercetak()
    {
        // Koleksi sekolah disusun langsung, bukan lewat DB: `Registration::create()`
        // selalu mengisi magic_token, jadi baris tanpa tautan hanya bisa muncul
        // dari data lama — dan yang diuji di sini justru perilaku view terhadapnya.
        $perKategori = collect([[
            'kategori' => $this->category,
            'sekolah' => collect([[
                'kunci' => 'SEKOLAH:SMP NEGERI 1',
                'npsn' => null,
                'nama_sekolah' => 'SMP Negeri 1',
                'kabupaten' => '',
                'jumlah_pasukan' => 1,
                'jumlah_kategori' => 1,
                'jumlah_anggota' => 0,
                'status' => 'Menunggu',
                'label_status' => 'Menunggu Verifikasi',
                'pelatih' => '',
                'no_hp' => '',
                'email' => '',
                'url' => '',
            ]]),
        ]]);

        $html = view('eventner.participant.pdf_data_sekolah', [
            'eventner' => $this->eventner,
            'perKategori' => $perKategori,
            'rekap' => DataSekolah::rekapitulasi($perKategori),
        ])->render();

        $this->assertStringContainsString('SMP Negeri 1', $html);
        $this->assertStringContainsString('Belum ada tautan', $html);
    }

    /** Render view rekap langsung — dompdf tidak perlu jalan untuk cek isinya. */
    private function renderRekap(): string
    {
        $eventner = $this->eventner;

        $perKategori = DataSekolah::denganTautanPerKategori(DataSekolah::perKategori(
            $this->kumpulkan(),
            $eventner->competitionCategories()->selectable()
                ->orderBy('sort_order')->orderBy('name')->get(),
            DataSekolah::fieldKabupatenId(RegistrationField::forEventner($eventner))
        ));

        return view('eventner.participant.pdf_data_sekolah', [
            'eventner' => $eventner,
            'perKategori' => $perKategori,
            'rekap' => DataSekolah::rekapitulasi($perKategori),
        ])->render();
    }

    // ── Pengelompokan per kategori ───────────────────────────────────────

    /** Inti permintaan: satu bagian tabel per kategori lomba, bukan satu tabel besar. */
    public function test_rekap_memecah_tabel_per_kategori()
    {
        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->parent->id,
            'name' => 'U16',
        ]);

        $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111']);
        $this->makeRegistration('SMP Negeri 2', ['npsn' => '22222222'], $lain);

        $bagian = DataSekolah::perKategori(
            $this->kumpulkan(),
            $this->eventner->competitionCategories()->selectable()->orderBy('sort_order')->get()
        );

        $this->assertCount(2, $bagian);

        // Urutan mengikuti sort_order (acak di factory), jadi bagiannya dicari
        // lewat nama — yang diuji di sini "terpisah per kategori", bukan urutannya.
        $perNama = $bagian->keyBy(fn ($b) => $b['kategori']->name);

        $this->assertSame('SMP Negeri 1', $perNama['U13']['sekolah'][0]['nama_sekolah']);
        $this->assertSame('SMP Negeri 2', $perNama['U16']['sekolah'][0]['nama_sekolah']);

        // View mencetak nama kategori sebagai judul tiap bagian.
        $html = $this->renderRekap();
        $this->assertStringContainsString('U13', $html);
        $this->assertStringContainsString('U16', $html);
    }

    /**
     * Sekolah yang mendaftar di dua kategori muncul di dua bagian.
     *
     * Sengaja TIDAK diringkas ke kategori pertama: angka "pasukan" per bagian
     * harus cocok dengan isi bagian itu sendiri, dan panitia mencetak halaman
     * ini per meja pendaftaran ulang.
     */
    public function test_sekolah_lintas_kategori_muncul_di_tiap_bagian()
    {
        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->parent->id,
            'name' => 'U16',
        ]);

        $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111']);
        $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111'], $lain);

        $bagian = DataSekolah::perKategori(
            $this->kumpulkan(),
            $this->eventner->competitionCategories()->selectable()->orderBy('sort_order')->get()
        );

        $this->assertCount(2, $bagian);
        foreach ($bagian as $b) {
            $this->assertCount(1, $b['sekolah']);
            $this->assertSame(1, $b['sekolah']->first()['jumlah_pasukan']);
        }
    }

    /** Kategori tanpa pendaftar tidak dibuatkan bagiannya — tidak ada tabel kosong. */
    public function test_kategori_tanpa_pendaftar_tidak_dibuatkan_bagian()
    {
        CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->parent->id,
            'name' => 'U16',
        ]);

        $this->makeRegistration('SMP Negeri 1');

        $bagian = DataSekolah::perKategori(
            $this->kumpulkan(),
            $this->eventner->competitionCategories()->selectable()->orderBy('sort_order')->get()
        );

        $this->assertCount(1, $bagian);
        $this->assertSame('U13', $bagian[0]['kategori']->name);
    }

    /** Dinamis: kategori buatan panitia ikut jadi bagian, tanpa daftar tetap di kode. */
    public function test_kategori_buatan_panitia_ikut_jadi_bagian()
    {
        $baru = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->parent->id,
            'name' => 'Campuran',
        ]);

        $this->makeRegistration('SMP Negeri 3', ['npsn' => '33333333'], $baru);

        $bagian = DataSekolah::perKategori(
            $this->kumpulkan(),
            $this->eventner->competitionCategories()->selectable()->orderBy('sort_order')->get()
        );

        $this->assertCount(1, $bagian);
        $this->assertSame('Campuran', $bagian[0]['kategori']->name);
    }

    /** Tautan tetap ada setelah dipecah per kategori. */
    public function test_bagian_per_kategori_membawa_tautan_sekolah()
    {
        $reg = $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111']);

        $perKategori = DataSekolah::denganTautanPerKategori(DataSekolah::perKategori(
            $this->kumpulkan(),
            $this->eventner->competitionCategories()->selectable()->get()
        ));

        $this->assertSame(
            route('magic.link', $reg->magic_token),
            $perKategori[0]['sekolah'][0]['url']
        );
    }

    // ── Catatan rekapan (kaki dokumen) ────────────────────────────────────

    /**
     * Sekolah lintas kategori: "baris tabel" lebih besar dari "sekolah unik".
     *
     * Ini inti catatannya — tanpa pembedaan itu jumlah sekolah terlihat
     * berlebih dibanding daftar sekolah yang sebenarnya.
     */
    public function test_rekapitulasi_membedakan_baris_tabel_dari_sekolah_unik()
    {
        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->parent->id,
            'name' => 'U16',
        ]);

        $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111']);
        $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111'], $lain);
        $this->makeRegistration('SMP Negeri 2', ['npsn' => '22222222']);

        $rekap = DataSekolah::rekapitulasi(DataSekolah::perKategori(
            $this->kumpulkan(),
            $this->eventner->competitionCategories()->selectable()->get()
        ));

        $this->assertSame(2, $rekap['kategori']);
        $this->assertSame(3, $rekap['baris']);      // 2 di U13 + 1 di U16
        $this->assertSame(2, $rekap['sekolah_unik']); // SMP 1 & SMP 2
        $this->assertSame(1, $rekap['lintas_kategori']); // hanya SMP 1
        $this->assertSame(3, $rekap['pasukan']);
    }

    /** Tanpa sekolah lintas kategori, "baris" sama dengan "sekolah unik". */
    public function test_rekapitulasi_tanpa_lintas_kategori_angkanya_sama()
    {
        $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111']);
        $this->makeRegistration('SMP Negeri 2', ['npsn' => '22222222']);

        $rekap = DataSekolah::rekapitulasi(DataSekolah::perKategori(
            $this->kumpulkan(),
            $this->eventner->competitionCategories()->selectable()->get()
        ));

        $this->assertSame($rekap['sekolah_unik'], $rekap['baris']);
        $this->assertSame(0, $rekap['lintas_kategori']);
    }

    /** Sekolah lintas kategori berstatus berbeda dihitung SEKALI, ambil yang paling jauh. */
    public function test_rekapitulasi_menghitung_status_sekali_per_sekolah()
    {
        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->parent->id,
            'name' => 'U16',
        ]);

        $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111', 'status_berkas' => 'confirmed']);
        $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111', 'status_berkas' => 'Terverifikasi'], $lain);

        $rekap = DataSekolah::rekapitulasi(DataSekolah::perKategori(
            $this->kumpulkan(),
            $this->eventner->competitionCategories()->selectable()->get()
        ));

        $this->assertSame(['Terverifikasi' => 1], $rekap['status']);
    }

    /** Catatan rekapan ikut tercetak di kaki dokumen, dengan angkanya. */
    public function test_rekap_mencetak_catatan_rekapan()
    {
        $this->makeRegistration('SMP Negeri 1', ['npsn' => '11111111']);

        $html = $this->renderRekap();

        $this->assertStringContainsString('Rekapitulasi', $html);
        $this->assertStringContainsString('Sekolah unik', $html);
        $this->assertStringContainsString('Baris tabel', $html);
        $this->assertStringContainsString('Rata-rata anggota per sekolah', $html);
        $this->assertStringContainsString('Status seluruh sekolah', $html);
        $this->assertStringContainsString('pendaftaran pasukan dari', $html);
    }

    /** Tanpa pendaftar: catatannya tidak dibagi nol. */
    public function test_rekapitulasi_tanpa_data_tidak_membagi_nol()
    {
        $rekap = DataSekolah::rekapitulasi(collect());

        $this->assertSame(0, $rekap['kategori']);
        $this->assertSame(0, $rekap['sekolah_unik']);
        $this->assertSame([], $rekap['status']);
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

    /**
     * Baris rekap mengikuti nomor undian, bukan abjad nama sekolah.
     *
     * Halaman ini dicetak dan dibaca meja demi meja mengikuti urutan tampil
     * pasukan, jadi urutan abjad memaksa panitia mencari nama di daftar.
     */
    public function test_baris_rekap_urut_nomor_undian()
    {
        $this->makeRegistration('Zeta', ['npsn' => '11111111', 'urutan_tampil' => 1]);
        $this->makeRegistration('Alfa', ['npsn' => '22222222', 'urutan_tampil' => 2]);
        $this->makeRegistration('Beta', ['npsn' => '33333333', 'urutan_tampil' => 3]);

        $nama = DataSekolah::kelompokkan($this->kumpulkan())->pluck('nama_sekolah')->all();

        $this->assertSame(['Zeta', 'Alfa', 'Beta'], $nama);
    }

    /** Pasukan yang belum diundi jatuh ke bawah, tetap urut nama sekolah. */
    public function test_peserta_belum_diundi_diletakkan_di_bawah()
    {
        $this->makeRegistration('Zeta', ['npsn' => '11111111', 'urutan_tampil' => 1]);
        $this->makeRegistration('Alfa', ['npsn' => '22222222', 'urutan_tampil' => null]);
        $this->makeRegistration('Beta', ['npsn' => '33333333', 'urutan_tampil' => null]);

        $nama = DataSekolah::kelompokkan($this->kumpulkan())->pluck('nama_sekolah')->all();

        $this->assertSame(['Zeta', 'Alfa', 'Beta'], $nama);
    }

    /**
     * Sekolah berpasukan banyak duduk di posisi undian TERKECIL di antara
     * pasukannya — pengelompokan tidak mengembalikan urutannya ke abjad.
     */
    public function test_sekolah_berpasukan_banyak_mengikuti_undian_terkecil()
    {
        $this->makeRegistration('Zeta', ['npsn' => '11111111', 'urutan_tampil' => 5, 'label_pasukan' => 'A']);
        $this->makeRegistration('Zeta', ['npsn' => '11111111', 'urutan_tampil' => 1, 'label_pasukan' => 'B']);
        $this->makeRegistration('Alfa', ['npsn' => '22222222', 'urutan_tampil' => 3]);

        $hasil = DataSekolah::kelompokkan($this->kumpulkan());

        $this->assertSame(['Zeta', 'Alfa'], $hasil->pluck('nama_sekolah')->all());
        $this->assertSame(2, $hasil[0]['jumlah_pasukan']);
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
            ->assertSee('Rekap Sekolah per Kategori')
            ->assertSee('Kartu Magic Link per Sekolah');
    }
}
