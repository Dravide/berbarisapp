<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Participant\Index as ParticipantIndex;
use App\Livewire\Public\MagicLink\Registration as MagicLink;
use App\Livewire\Public\Registration\Create as RegistrationCreate;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\RegistrationField;
use App\Models\RegistrationFieldValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Field builder dipakai ujung ke ujung: form pendaftaran publik, portal magic
 * link (teks + unggahan berkas), dan tampilan baca di panel panitia.
 *
 * Yang dijaga: isian field baru tersimpan ke registration_field_values, field
 * bawaan tetap menulis ke kolomnya sendiri, field nonaktif tidak muncul, dan
 * field wajib menahan submit.
 */
class RegistrationDynamicFieldTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;
    private CompetitionCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Deadline pendaftaran wajib diisi: accessor registration_status
        // menghitung ulang dari tanggal, dan deadline kosong = ditutup.
        $this->eventner = Eventner::factory()->create([
            'status' => 'approved',
            'slug' => 'dinamis',
            'subdomain' => null,
            'tanggal_pendaftaran' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $parent = CompetitionCategory::factory()->for($this->eventner, 'eventner')->create();
        $this->category = CompetitionCategory::factory()->child($parent)
            ->for($this->eventner, 'eventner')
            ->create();

        RegistrationField::ensureDefaults($this->eventner);
    }

    private function field(string $key): RegistrationField
    {
        return RegistrationField::where('eventner_id', $this->eventner->id)
            ->where('field_key', $key)
            ->firstOrFail();
    }

    /** Field baru yang tidak punya kolom di tabel registrations. */
    private function buatFieldBaru(string $key, string $label, array $ganti = []): RegistrationField
    {
        return RegistrationField::create(array_merge([
            'eventner_id' => $this->eventner->id,
            'field_key' => $key,
            'label' => $label,
            'type' => 'text',
            'is_required' => true,
            'is_active' => true,
            'is_builtin' => false,
            'sort_order' => 99,
        ], $ganti));
    }

    private function daftar(array $fieldValues = [], array $ganti = [])
    {
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

        return $komponen->set($ganti)->call('submit');
    }

    // ── Form publik ─────────────────────────────────────────────────────

    public function test_field_baru_tampil_di_form_publik()
    {
        $this->buatFieldBaru('kabupaten_asal', 'Zona Wilayah Uji');

        Livewire::test(RegistrationCreate::class, ['slug' => $this->eventner->slug])
            ->set('step', 2)
            ->assertSee('Zona Wilayah Uji');
    }

    public function test_field_nonaktif_tidak_tampil_di_form_publik()
    {
        $this->buatFieldBaru('kabupaten_asal', 'Zona Wilayah Uji', ['is_active' => false]);

        Livewire::test(RegistrationCreate::class, ['slug' => $this->eventner->slug])
            ->set('step', 2)
            ->assertDontSee('Zona Wilayah Uji');
    }

    public function test_submit_menyimpan_field_baru_ke_tabel_nilai()
    {
        $field = $this->buatFieldBaru('kabupaten_asal', 'Asal Kabupaten / Kota');

        $this->daftar(['kabupaten_asal' => 'Bandar Lampung']);

        $reg = Registration::where('eventner_id', $this->eventner->id)->firstOrFail();

        $this->assertSame(
            'Bandar Lampung',
            RegistrationFieldValue::where('registration_id', $reg->id)
                ->where('registration_field_id', $field->id)
                ->value('value')
        );
    }

    public function test_submit_menyimpan_field_bawaan_ke_kolomnya()
    {
        $this->daftar(['nama_sekolah' => 'SMP Negeri 1 Uji']);

        $reg = Registration::where('eventner_id', $this->eventner->id)->firstOrFail();

        $this->assertSame('SMP Negeri 1 Uji', $reg->nama_sekolah);
        $this->assertSame('12345678', $reg->npsn);
        // Field bawaan tidak boleh dobel di tabel nilai.
        $this->assertSame(0, RegistrationFieldValue::where('registration_id', $reg->id)->count());
    }

    public function test_field_wajib_kosong_menahan_submit()
    {
        $this->buatFieldBaru('kabupaten_asal', 'Asal Kabupaten / Kota');

        $this->daftar()->assertHasErrors(['fieldValues.kabupaten_asal' => 'required']);

        $this->assertSame(0, Registration::where('eventner_id', $this->eventner->id)->count());
    }

    public function test_label_pasukan_dari_builder_dipakai_sebagai_nama_pasukan()
    {
        $this->daftar(['label_pasukan' => 'Pleton Garuda']);

        $reg = Registration::where('eventner_id', $this->eventner->id)->firstOrFail();

        $this->assertSame('Pleton Garuda', $reg->label_pasukan);
    }

    /**
     * Nama pasukan sepanjang yang diloloskan validator harus benar-benar bisa
     * ditulis. Kolomnya dulu varchar(10) sementara form mengizinkan teks biasa,
     * jadi isian seperti "MTS ASSASSI" gagal dengan "Data too long for column
     * 'label_pasukan'" — 500 di langkah terakhir, setelah semua diisi.
     */
    public function test_nama_pasukan_panjang_tersimpan_utuh()
    {
        $nama = 'MTS ASSALAFIYYAH ASSIROJIYYAH PASUKAN GARUDA';

        $this->daftar(['label_pasukan' => $nama]);

        $this->assertSame($nama, Registration::where('eventner_id', $this->eventner->id)->firstOrFail()->label_pasukan);
    }

    public function test_nama_pasukan_di_atas_batas_ditolak()
    {
        $this->daftar(['label_pasukan' => str_repeat('A', RegistrationField::MAX_LABEL_PASUKAN + 1)]);

        $this->assertSame(0, Registration::where('eventner_id', $this->eventner->id)->count());
    }

    /**
     * Satu sekolah, dua pasukan: nama yang diisi pendaftar masih diberi sufiks
     * " A"/" B", jadi batas isiannya harus menyisakan ruang untuk sufiks itu.
     */
    public function test_nama_pasukan_sepanjang_batas_masih_muat_setelah_disufiks()
    {
        $this->category->update(['max_registrations_per_school' => 2]);

        $komponen = Livewire::test(RegistrationCreate::class, ['slug' => $this->eventner->slug])
            ->set('selectedCategories', [$this->category->id])
            ->set('teamCounts', [$this->category->id => 2])
            ->set('npsn', '12345678')
            ->set('nama_sekolah', 'SMP Negeri 1 Uji')
            ->set('nama_pelatih', 'Pelatih Uji')
            ->set('no_hp', '08123456789')
            ->set('school_email', 'uji@example.test')
            ->set('fieldValues.label_pasukan', str_repeat('A', RegistrationField::MAX_LABEL_PASUKAN));

        $komponen->call('submit');

        $tersimpan = Registration::where('eventner_id', $this->eventner->id)
            ->orderBy('label_pasukan')
            ->pluck('label_pasukan')
            ->all();

        $this->assertCount(2, $tersimpan);
        $this->assertSame(
            str_repeat('A', RegistrationField::MAX_LABEL_PASUKAN) . ' A',
            $tersimpan[0]
        );
    }

    // ── Portal magic link ───────────────────────────────────────────────

    private function peserta(): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->category->id,
            'status_berkas' => 'confirmed',
            'magic_token' => 'TOKEN-DINAMIS',
        ]);
    }

    /**
     * HTML portal dengan modal section `umum` (Data Pendaftaran) terbuka.
     *
     * Kotak unggah berkas builder tinggal di dalam modal section, sama seperti
     * Foto Pelatih di modal Pelatih — jadi markup FilePond-nya baru ada setelah
     * modalnya dibuka, bukan di HTML awal halaman.
     */
    private function htmlModalUmum(Registration $reg): string
    {
        return Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->call('bukaModalSeksi', 'umum')
            ->html();
    }

    public function test_portal_menyimpan_field_teks()
    {
        $reg = $this->peserta();
        $field = $this->buatFieldBaru('kabupaten_asal', 'Asal Kabupaten / Kota');

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->set('fieldValues.nama_pelatih', 'Pelatih Uji')
            ->set('dantonNama', 'Danton Uji')
            ->set('dantonNisn', '')
            ->set('participants', [['nama' => 'Anggota Uji', 'nisn' => '', 'foto' => null, 'existing_foto' => null]])
            ->set('fieldValues.kabupaten_asal', 'Lampung Selatan')
            ->call('submit', false)
            ->assertHasNoErrors();

        $this->assertSame(
            'Lampung Selatan',
            RegistrationFieldValue::where('registration_id', $reg->id)
                ->where('registration_field_id', $field->id)
                ->value('value')
        );
    }

    public function test_portal_menahan_finalisasi_saat_berkas_wajib_belum_ada()
    {
        $reg = $this->peserta();
        $this->buatFieldBaru('foto_ktp', 'Foto KTP', ['type' => 'image', 'is_required' => true]);

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->set('fieldValues.nama_pelatih', 'Pelatih Uji')
            ->set('dantonNama', 'Danton Uji')
            ->set('dantonNisn', '')
            ->set('participants', [['nama' => 'Anggota Uji', 'nisn' => '', 'foto' => null, 'existing_foto' => null]])
            ->call('submit', true)
            ->assertSet('registration.is_finalized', false);
    }

    /** Unggahan berkas lewat endpoint HTTP (properti Livewire tidak bisa dinamis). */
    public function test_unggahan_berkas_field_baru_tersimpan()
    {
        Storage::fake('public');

        $reg = $this->peserta();
        $field = $this->buatFieldBaru('foto_ktp', 'Foto KTP', ['type' => 'image', 'is_required' => true]);

        $this->post(route('magic.link.field.upload', [
            'token' => $reg->magic_token,
            'fieldId' => $field->id,
        ]), ['file' => UploadedFile::fake()->image('ktp.jpg')])
            ->assertOk();

        $path = RegistrationFieldValue::where('registration_id', $reg->id)
            ->where('registration_field_id', $field->id)
            ->value('value');

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_berkas_field_bawaan_menulis_ke_kolom_lama()
    {
        Storage::fake('public');

        $reg = $this->peserta();
        $field = $this->field('surat_tugas');

        $this->post(route('magic.link.field.upload', [
            'token' => $reg->magic_token,
            'fieldId' => $field->id,
        ]), ['file' => UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf')])
            ->assertOk();

        $this->assertNotNull($reg->fresh()->surat_tugas);
        $this->assertSame(0, RegistrationFieldValue::where('registration_id', $reg->id)->count());
    }

    public function test_unggahan_field_event_lain_ditolak()
    {
        Storage::fake('public');

        $lain = Eventner::factory()->create(['status' => 'approved']);
        $fieldLain = RegistrationField::create([
            'eventner_id' => $lain->id,
            'field_key' => 'foto_ktp',
            'label' => 'Foto KTP',
            'type' => 'image',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $reg = $this->peserta();

        $this->post(route('magic.link.field.upload', [
            'token' => $reg->magic_token,
            'fieldId' => $fieldLain->id,
        ]), ['file' => UploadedFile::fake()->image('ktp.jpg')])
            ->assertNotFound();
    }

    // ── Tampilan baca ───────────────────────────────────────────────────

    public function test_field_values_for_display_menggabungkan_bawaan_dan_baru()
    {
        $reg = $this->peserta();
        $field = $this->buatFieldBaru('kabupaten_asal', 'Asal Kabupaten / Kota');

        RegistrationFieldValue::create([
            'registration_id' => $reg->id,
            'registration_field_id' => $field->id,
            'value' => 'Bandar Lampung',
        ]);

        $tampil = $reg->fresh(['fieldValues'])->fieldValuesForDisplay();

        $this->assertSame('Bandar Lampung', $tampil->firstWhere('key', 'kabupaten_asal')['value']);
        // Field bawaan ikut terbaca dari kolomnya sendiri.
        $this->assertSame($reg->nama_sekolah, $tampil->firstWhere('key', 'nama_sekolah')['value']);
        $this->assertTrue($tampil->firstWhere('key', 'nama_sekolah')['is_required']);
    }

    public function test_get_field_value_membaca_field_baru_dan_bawaan()
    {
        $reg = $this->peserta();
        $field = $this->buatFieldBaru('kabupaten_asal', 'Asal Kabupaten / Kota');

        RegistrationFieldValue::create([
            'registration_id' => $reg->id,
            'registration_field_id' => $field->id,
            'value' => 'Bandar Lampung',
        ]);

        $reg = $reg->fresh(['fieldValues']);

        $this->assertSame('Bandar Lampung', $reg->getFieldValue('kabupaten_asal'));
        $this->assertSame($reg->nama_sekolah, $reg->getFieldValue('nama_sekolah'));
        $this->assertNull($reg->getFieldValue('field_yang_tidak_ada'));
    }

    public function test_sertifikat_bisa_memakai_field_builder()
    {
        $reg = $this->peserta();
        $this->buatFieldBaru('kabupaten_asal', 'Asal Kabupaten / Kota');

        $reg = $reg->fresh(['fieldValues']);
        $field = $this->field('kabupaten_asal');

        RegistrationFieldValue::create([
            'registration_id' => $reg->id,
            'registration_field_id' => $field->id,
            'value' => 'Bandar Lampung',
        ]);

        $this->assertSame(
            'Bandar Lampung',
            $reg->fresh(['fieldValues'])->resolveCertificateField('kabupaten_asal')
        );
    }

    public function test_template_sertifikat_menawarkan_field_builder()
    {
        $this->buatFieldBaru('kabupaten_asal', 'Asal Kabupaten / Kota');

        $template = new \App\Models\CertificateTemplate(['eventner_id' => $this->eventner->id]);
        $template->setRelation('eventner', $this->eventner);

        $daftar = $template->availableFieldsForEvent();

        $this->assertSame('Asal Kabupaten / Kota', $daftar['kabupaten_asal']);
        // Field bawaan tetap ada.
        $this->assertArrayHasKey('nama_sekolah', $daftar);
    }

    /**
     * Label hasil suntingan panitia menang atas peta bawaan — termasuk untuk
     * field bawaan seperti nama_sekolah, yang dulu tertutup selamanya karena
     * sudah ada di peta hardcode.
     */
    public function test_label_builder_menang_di_placeholder_sertifikat()
    {
        $this->field('nama_sekolah')->update(['label' => 'Sekolah Peserta']);

        $template = new \App\Models\CertificateTemplate(['eventner_id' => $this->eventner->id]);
        $template->setRelation('eventner', $this->eventner);

        $this->assertSame('Sekolah Peserta', $template->availableFieldsForEvent()['nama_sekolah']);
    }

    // ── Label & aturan mengalir ke permukaan lain ───────────────────────

    public function test_label_builder_dipakai_portal_dan_cetakan_pdf()
    {
        $reg = $this->peserta();
        $this->field('nama_pelatih')->update(['label' => 'Pembina Pasukan']);

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->assertSee('Pembina Pasukan');

        $html = view('eventner.participant.pdf_formulir', [
            'eventner' => $reg->eventner,
            'registration' => $reg->fresh(['participants', 'fieldValues', 'competitionCategory']),
            'participants' => $reg->participants,
        ])->render();

        $this->assertStringContainsString('Pembina Pasukan', $html);
    }

    public function test_field_bawaan_tidak_wajib_lolos_submit_portal()
    {
        $reg = $this->peserta();
        $this->field('nama_pelatih')->update(['is_required' => false]);
        $this->field('danton_nama')->update(['is_required' => false]);

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->set('fieldValues.nama_pelatih', '')
            ->set('dantonNama', '')
            ->set('participants', [['nama' => 'Anggota Uji', 'nisn' => '', 'foto' => null, 'existing_foto' => null]])
            ->call('submit', false)
            ->assertHasNoErrors();

        // Yang wajib tetap tidak boleh ikut kosong.
        $this->assertSame('', (string) $reg->fresh()->nama_pelatih);
    }

    public function test_danton_nonaktif_hilang_dari_portal_dan_validasi()
    {
        $reg = $this->peserta();

        foreach (['danton_nama', 'danton_nisn', 'danton_foto'] as $key) {
            $this->field($key)->update(['is_active' => false]);
        }

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->assertDontSee('Nama Danton')
            ->set('fieldValues.nama_pelatih', 'Pelatih Uji')
            ->set('dantonNama', '')
            ->set('participants', [['nama' => 'Anggota Uji', 'nisn' => '', 'foto' => null, 'existing_foto' => null]])
            ->call('submit', false)
            ->assertHasNoErrors();
    }

    /**
     * Menyimpan draft saat field danton dimatikan tidak boleh mengosongkan
     * isian lama — nilai lama tetap utuh karena field-nya tidak ditulis.
     */
    public function test_danton_nonaktif_tidak_menghapus_isian_lama()
    {
        $reg = $this->peserta();
        $reg->update(['danton_nama' => 'Danton Lama']);

        $this->field('danton_nama')->update(['is_active' => false]);

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->set('fieldValues.nama_pelatih', 'Pelatih Uji')
            ->set('dantonNama', '')
            ->set('participants', [['nama' => 'Anggota Uji', 'nisn' => '', 'foto' => null, 'existing_foto' => null]])
            ->call('submit', false)
            ->assertHasNoErrors();

        $this->assertSame('Danton Lama', $reg->fresh()->danton_nama);
    }

    // ── Modal section (Data Pelatih & Danton) ───────────────────────────

    /**
     * Modal section memvalidasi section-nya sendiri.
     *
     * Inilah alasan modal ini ada: salah isi di Data Pelatih tidak lagi
     * memunculkan error Danton sekaligus, dan sebaliknya.
     */
    public function test_modal_seksi_memvalidasi_seksinya_sendiri()
    {
        $reg = $this->peserta();

        $komponen = Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->set('fieldValues.nama_pelatih', '')
            ->set('dantonNama', '')
            ->call('bukaModalSeksi', 'pelatih');

        $this->assertSame('pelatih', $komponen->get('modalSeksi'));

        $errors = $komponen->call('simpanSeksi')->errors()->getMessages();

        $this->assertArrayHasKey('fieldValues.nama_pelatih', $errors);
        $this->assertArrayNotHasKey('dantonNama', $errors);
    }

    /** Modal ditutup setelah isinya sah, dan nilainya langsung masuk DB. */
    public function test_modal_seksi_tersimpan_dan_menutup_diri()
    {
        $reg = $this->peserta();

        $komponen = Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->call('bukaModalSeksi', 'danton')
            ->set('dantonNama', 'Danton Uji')
            ->set('dantonNisn', '')
            ->call('simpanSeksi')
            ->assertHasNoErrors();

        $this->assertNull($komponen->get('modalSeksi'));

        // Langsung ke DB: menyegarkan halaman tidak boleh membuang isian ini.
        $this->assertSame('Danton Uji', $reg->fresh()->danton_nama);
    }

    /** Pelatih memakai jalur simpan yang sama dengan Danton. */
    public function test_modal_seksi_pelatih_langsung_menulis_ke_db()
    {
        $reg = $this->peserta();

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->call('bukaModalSeksi', 'pelatih')
            ->set('fieldValues.nama_pelatih', 'Pelatih Uji')
            ->call('simpanSeksi')
            ->assertHasNoErrors();

        $this->assertSame('Pelatih Uji', $reg->fresh()->nama_pelatih);
    }

    /** Section bermodal tidak menulis saat pendaftaran masih booking. */
    public function test_modal_seksi_tidak_menulis_saat_booking()
    {
        // Deadline masih jauh, TM belum lewat → status event = booking.
        $this->eventner->update(['technical_meeting' => now()->addDays(3)]);

        $reg = $this->peserta();

        $this->assertSame('booking', $reg->eventner->fresh()->registration_status);

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->call('bukaModalSeksi', 'danton')
            ->set('dantonNama', 'Danton Uji')
            ->call('simpanSeksi');

        $this->assertNull($reg->fresh()->danton_nama);
    }

    /** Membuka modal section membersihkan error section lain punya sendiri. */
    public function test_buka_modal_seksi_hanya_menghapus_error_seksinya()
    {
        $reg = $this->peserta();

        $this->field('nama_sekolah')->update(['is_required' => true]);
        $this->field('nama_pelatih')->update(['is_required' => true]);

        $komponen = Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->set('fieldValues.nama_sekolah', '')
            ->set('fieldValues.nama_pelatih', '')
            ->call('submit', false);

        $this->assertNotEmpty($komponen->errors()->get('fieldValues.nama_sekolah'));
        $this->assertNotEmpty($komponen->errors()->get('fieldValues.nama_pelatih'));

        $komponen->call('bukaModalSeksi', 'pelatih');

        // Error umum tetap ada, error pelatih hilang karena sedang diubah.
        $this->assertNotEmpty($komponen->errors()->get('fieldValues.nama_sekolah'));
        $this->assertEmpty($komponen->errors()->get('fieldValues.nama_pelatih'));
    }

    /** Section tanpa field aktif tidak punya modal untuk dibuka. */
    public function test_modal_seksi_danton_nonaktif_tidak_bisa_dibuka()
    {
        $reg = $this->peserta();

        foreach (['danton_nama', 'danton_nisn', 'danton_foto'] as $key) {
            $this->field($key)->update(['is_active' => false]);
        }

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->call('bukaModalSeksi', 'danton')
            ->assertSet('modalSeksi', null);
    }

    /** Portal yang sudah terkunci tidak membuka modal, sama seperti formnya. */
    public function test_modal_seksi_tidak_dibuka_saat_terkunci()
    {
        $reg = $this->peserta();
        $reg->update(['is_finalized' => true, 'status_berkas' => 'Terverifikasi']);

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->call('bukaModalSeksi', 'pelatih')
            ->assertSet('modalSeksi', null);
    }

    /**
     * Pindah tab pasukan menutup modal section.
     *
     * Isinya terikat ke properti form, jadi modal yang tertinggal akan
     * mengedit pasukan yang baru dibuka, bukan yang tadi.
     */
    public function test_pindah_tab_menutup_modal_seksi()
    {
        $reg = $this->peserta();

        $lain = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->category->id,
            'status_berkas' => 'confirmed',
            'npsn' => $reg->npsn,
            'magic_token' => 'TOKEN-LAIN',
        ]);

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->call('bukaModalSeksi', 'pelatih')
            ->assertSet('modalSeksi', 'pelatih')
            ->call('switchRegistration', $lain->id)
            ->assertSet('modalSeksi', null);
    }

    // ── Modal anggota (langsung ke DB) ──────────────────────────────────

    /**
     * Menekan "Simpan Anggota" langsung menulis barisnya ke tabel participants.
     *
     * Inilah alasan jalur ini ada: pendaftar yang menyegarkan halaman tidak
     * boleh kehilangan anggota yang baru saja ia isi — termasuk fotonya.
     */
    public function test_simpan_anggota_langsung_menulis_ke_db()
    {
        $reg = $this->peserta();

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->call('bukaModalAnggota')
            ->set('memberDraft.nama', 'Anggota Uji')
            ->set('memberDraft.nisn', '1234')
            ->call('simpanAnggota')
            ->assertHasNoErrors();

        $anggota = $reg->fresh()->participants;

        $this->assertCount(1, $anggota);
        $this->assertSame('Anggota Uji', $anggota->first()->nama);
        $this->assertSame('1234', $anggota->first()->nisn);
    }

    /** Hapus anggota juga langsung ke DB, bukan menunggu Simpan Draft. */
    public function test_hapus_anggota_langsung_menghapus_di_db()
    {
        $reg = $this->peserta();
        $reg->participants()->createMany([
            ['nama' => 'Anggota Satu', 'nisn' => null, 'foto' => null],
            ['nama' => 'Anggota Dua', 'nisn' => null, 'foto' => null],
        ]);

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->call('mintaHapusAnggota', 0)
            ->call('hapusAnggota', 0);

        $this->assertSame(['Anggota Dua'], $reg->fresh()->participants->pluck('nama')->all());
    }

    /**
     * Simpan Draft tidak boleh menghapus apa yang sudah ditulis modal.
     *
     * Dulu `saveParticipants()` menghapus semua baris lalu membuat ulang; kalau
     * jalan itu masih dipakai, id anggota berubah setiap kali simpan dan baris
     * yang baru ditulis bisa hilang bila daftarnya belum sinkron.
     */
    public function test_simpan_draft_tidak_menghapus_anggota_yang_baru_ditulis()
    {
        $reg = $this->peserta();

        $komponen = Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->call('bukaModalAnggota')
            ->set('memberDraft.nama', 'Anggota Uji')
            ->set('memberDraft.nisn', '')
            ->call('simpanAnggota');

        $idAwal = $reg->fresh()->participants->first()->id;

        $komponen->set('fieldValues.nama_pelatih', 'Pelatih Uji')
            ->set('dantonNama', 'Danton Uji')
            ->call('submit', false)
            ->assertHasNoErrors();

        $anggota = $reg->fresh()->participants;

        $this->assertCount(1, $anggota);
        $this->assertSame('Anggota Uji', $anggota->first()->nama);
        $this->assertSame($idAwal, $anggota->first()->id);
    }

    /** Mengubah nama tidak menghapus pas foto yang sudah tersimpan. */
    public function test_ubah_anggota_menyimpan_foto_lama()
    {
        Storage::fake('public');

        $reg = $this->peserta();
        $anggota = $reg->participants()->create(['nama' => 'Nama Lama', 'nisn' => null, 'foto' => 'registrations/peserta/lama.jpg']);

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->call('bukaModalAnggota', 0)
            ->set('memberDraft.nama', 'Nama Baru')
            ->call('simpanAnggota')
            ->assertHasNoErrors();

        $segar = $anggota->fresh();

        $this->assertSame('Nama Baru', $segar->nama);
        $this->assertSame('registrations/peserta/lama.jpg', $segar->foto);
    }

    /** Fotos anggota yang diunggah lewat modal ikut tersimpan pada saat itu. */
    public function test_simpan_anggota_menyimpan_foto_baru()
    {
        Storage::fake('public');

        $reg = $this->peserta();

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->call('bukaModalAnggota')
            ->set('memberDraft.nama', 'Anggota Uji')
            ->set('memberDraft.foto', UploadedFile::fake()->image('anggota.jpg'))
            ->call('simpanAnggota')
            ->assertHasNoErrors();

        $foto = $reg->fresh()->participants->first()->foto;

        $this->assertNotNull($foto);
        Storage::disk('public')->assertExists($foto);
    }

    /** Portal terkunci tidak menulis anggota, sama seperti formnya. */
    public function test_simpan_anggota_tidak_menulis_saat_terkunci()
    {
        $reg = $this->peserta();
        $reg->update(['is_finalized' => true, 'status_berkas' => 'Terverifikasi']);

        Livewire::test(MagicLink::class, ['token' => $reg->magic_token])
            ->call('bukaModalAnggota')
            ->set('memberDraft.nama', 'Anggota Uji')
            ->call('simpanAnggota');

        $this->assertCount(0, $reg->fresh()->participants);
    }

    // ── Modal panitia ───────────────────────────────────────────────────

    private function panitia(): User
    {
        return User::findOrFail($this->eventner->user_id);
    }

    public function test_modal_peserta_memakai_label_dari_builder()
    {
        $this->field('nama_pelatih')->update(['label' => 'Pembina Pasukan']);

        Livewire::actingAs($this->panitia())
            ->test(ParticipantIndex::class)
            ->call('openModal', $this->category->id)
            ->assertSee('Pembina Pasukan')
            ->assertDontSee('Nama Pelatih');
    }

    public function test_modal_peserta_memakai_aturan_wajib_dari_builder()
    {
        $this->field('no_hp')->update(['is_required' => false]);
        $this->field('nama_pelatih')->update(['label' => 'Pembina Pasukan']);

        $komponen = Livewire::actingAs($this->panitia())
            ->test(ParticipantIndex::class)
            ->call('openModal', $this->category->id)
            ->set('competition_category_id', $this->category->id)
            ->set('nama_sekolah', 'SMP Negeri 1 Uji')
            ->set('nama_pelatih', '')
            ->set('no_hp', '');

        // Modal tertutup lagi oleh save() yang gagal validasi, jadi pesannya
        // diperiksa dari kantong error komponen, bukan dari HTML.
        $errors = $komponen->call('save')->errors()->getMessages();

        $this->assertArrayHasKey('nama_pelatih', $errors);
        $this->assertSame('Pembina Pasukan wajib diisi.', $errors['nama_pelatih'][0]);
        $this->assertArrayNotHasKey('no_hp', $errors);
    }

    public function test_modal_peserta_field_nonaktif_tidak_ditulis()
    {
        $reg = $this->peserta();
        $reg->update(['nama_pelatih' => 'Pelatih Lama']);
        $this->field('nama_pelatih')->update(['is_active' => false]);

        Livewire::actingAs($this->panitia())
            ->test(ParticipantIndex::class)
            ->call('edit', $reg->id)
            ->set('nama_sekolah', 'SMP Negeri 1 Uji')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Pelatih Lama', $reg->fresh()->nama_pelatih);
    }

    /**
     * Nama pasukan hanya bisa disunting, bukan ditambahkan.
     *
     * Di modal Tambah penanda pasukan datang dari "Jumlah Pasukan" (sufiks
     * " (A)", " (B)" pada nama sekolah). Satu isian nama di sana akan memberi
     * tiga pasukan nama yang sama, menghapus pembeda yang justru dibutuhkan
     * undian dan papan skor.
     */
    public function test_modal_edit_bisa_mengubah_nama_pasukan()
    {
        $reg = $this->peserta();
        $reg->update(['label_pasukan' => 'Pleton 2']);

        Livewire::actingAs($this->panitia())
            ->test(ParticipantIndex::class)
            ->call('edit', $reg->id)
            ->assertSee('Nama Pasukan')
            ->assertSet('label_pasukan', 'Pleton 2')
            ->set('label_pasukan', 'Pleton 3')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Pleton 3', $reg->fresh()->label_pasukan);
    }

    /** Dikosongkan berarti kembali tanpa nama pasukan, bukan string kosong. */
    public function test_modal_edit_boleh_mengosongkan_nama_pasukan()
    {
        $reg = $this->peserta();
        $reg->update(['label_pasukan' => 'Pleton 2']);

        Livewire::actingAs($this->panitia())
            ->test(ParticipantIndex::class)
            ->call('edit', $reg->id)
            ->set('label_pasukan', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($reg->fresh()->label_pasukan);
        $this->assertSame($reg->nama_sekolah, $reg->fresh()->display_name);
    }

    public function test_modal_tambah_tidak_menampilkan_nama_pasukan()
    {
        Livewire::actingAs($this->panitia())
            ->test(ParticipantIndex::class)
            ->call('openModal', $this->category->id)
            ->assertSee('Jumlah Pasukan')
            ->assertDontSee('Nama Pasukan');
    }

    /**
     * Autofill NPSN dipicu lewat fieldValues (satu jalur form), bukan
     * wire:blur="updatedNpsn" — Livewire 3 melempar
     * DirectlyCallingLifecycleHooksNotAllowedException bila hook lifecycle
     * dipanggil langsung dari view.
     */
    public function test_autofill_npsn_lewat_field_values()
    {
        \App\Models\School::create([
            'npsn' => '99887766',
            'nama_sekolah' => 'SMP Autofill Uji',
            'no_hp' => '081200000000',
            'school_email' => 'autofill@example.test',
        ]);

        $komponen = Livewire::test(RegistrationCreate::class, ['slug' => $this->eventner->slug])
            ->set('fieldValues.npsn', '99887766');

        $this->assertSame('SMP Autofill Uji', $komponen->get('nama_sekolah'));
        $this->assertSame('081200000000', $komponen->get('no_hp'));
        $this->assertSame('autofill@example.test', $komponen->get('school_email'));
        $this->assertSame('SMP Autofill Uji', $komponen->get('fieldValues.nama_sekolah'));
    }

    /** NPSN tak dikenal tidak mengosongkan isian yang sudah ada. */
    public function test_autofill_npsn_tak_dikenal_tidak_menimpa()
    {
        Livewire::test(RegistrationCreate::class, ['slug' => $this->eventner->slug])
            ->set('nama_sekolah', 'Nama Manual')
            ->set('fieldValues.npsn', '00000000')
            ->assertSet('nama_sekolah', 'Nama Manual');
    }

    /**
     * FilePondPluginFileValidateType hanya mengerti MIME type. Daftar ekstensi
     * ("…jpg,jpeg…") tidak pernah cocok dengan `file.type` (application/pdf),
     * jadi berkas dengan ekstensi yang benar pun ditolak "File is of invalid
     * type" dan tak pernah sampai ke server.
     *
     * Kotak unggahnya berada di modal section `umum` — sama seperti Foto
     * Pelatih di modal Pelatih — jadi HTML-nya baru ada setelah modal dibuka.
     */
    public function test_portal_memberi_mime_bukan_ekstensi_ke_filepond()
    {
        $reg = $this->peserta();

        $html = $this->htmlModalUmum($reg);

        $this->assertStringContainsString(
            "acceptedFileTypes: ['application/pdf', 'image/jpeg', 'image/png']",
            $html,
            'FilePond harus menerima MIME type, bukan ekstensi.'
        );
        $this->assertStringNotContainsString("acceptedFileTypes: ['.pdf'", $html);
    }

    /**
     * Atribut `accept` harus MIME dan sama dengan acceptedFileTypes.
     *
     * FilePondPluginFileValidateType memetakan `accept` -> `acceptedFileTypes`
     * (SET_ATTRIBUTE_TO_OPTION_MAP) dan core merge atribut SETELAH opsi JS,
     * jadi `accept` menimpa array di FilePond.create(). Selama accept masih
     * berisi ekstensi, acceptedFileTypes yang benar tidak pernah terpakai —
     * berkas yang sah ditolak "File is of invalid type" tanpa sampai ke server.
     *
     * Wildcard image/* tidak terpengaruh karena kedua sisi kebetulan sama,
     * itulah sebabnya Logo Sekolah aman sementara Surat Tugas gagal.
     */
    public function test_portal_atribut_accept_sama_dengan_accepted_file_types()
    {
        $reg = $this->peserta();

        $html = $this->htmlModalUmum($reg);

        $this->assertStringContainsString('accept="application/pdf,image/jpeg,image/png"', $html);
        $this->assertStringNotContainsString('accept=".pdf,.jpg,.jpeg,.png"', $html);
        $this->assertStringNotContainsString('accept=".pdf', $html);
    }

    /** Field gambar tetap pakai wildcard image/*. */
    public function test_portal_field_gambar_pakai_wildcard_mime()
    {
        $reg = $this->peserta();
        $this->buatFieldBaru('foto_ktp', 'Foto KTP', ['type' => 'image', 'is_required' => true]);

        $html = $this->htmlModalUmum($reg);

        $this->assertStringContainsString("acceptedFileTypes: ['image/*']", $html);
    }

    /**
     * Label penolakan harus versi Indonesia, bukan bawaan plugin.
     *
     * Label bawaan plugin ("File is of invalid type") hanya muncul kalau opsi
     * `labelFileTypeNotAllowed` tidak sampai ke plugin — persis gejala yang
     * terlihat di lapangan: labelIdle ikut terpasang, label ini tidak.
     * Kehadirannya di HTML menandakan opsi kustom portal hilang.
     */
    public function test_portal_memberi_label_penolakan_sendiri_ke_filepond()
    {
        $reg = $this->peserta();

        $html = $this->htmlModalUmum($reg);

        $this->assertStringContainsString('labelFileTypeNotAllowed:', $html);
        $this->assertStringNotContainsString('File is of invalid type', $html);
    }

    /**
     * .jpeg harus diterima server.
     *
     * Nama berkas dari WhatsApp berakhiran .jpeg, dan `mimes:pdf,jpg,jpeg,png`
     * memetakan ekstensi itu ke image/jpeg — jadi tidak ada alasan berkas yang
     * benar ditolak. Uji ini mengunci sisi server; sisi klien dikunci oleh
     * test_portal_memberi_mime_bukan_ekstensi_ke_filepond.
     */
    public function test_unggahan_jpeg_diterima_server()
    {
        $reg = $this->peserta();
        $field = $this->field('surat_tugas');

        $this->post(route('magic.link.field.upload', [
            'token' => $reg->magic_token,
            'fieldId' => $field->id,
        ]), [
            'file' => UploadedFile::fake()->image('WhatsApp Image 2026 at 05.23.14.jpeg'),
        ])->assertOk()->assertJsonStructure(['path', 'url']);

        $this->assertNotNull($reg->fresh()->surat_tugas);
    }
}
