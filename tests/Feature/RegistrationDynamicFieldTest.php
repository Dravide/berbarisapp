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

    // ── Portal magic link ───────────────────────────────────────────────

    private function peserta(): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->category->id,
            'status_berkas' => 'confirmed',
            'magic_token' => 'TOKEN-DINAMIS',
        ]);
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
     */
    public function test_portal_memberi_mime_bukan_ekstensi_ke_filepond()
    {
        $reg = $this->peserta();

        $html = $this->get(route('magic.link', ['token' => $reg->magic_token]))
            ->assertOk()
            ->getContent();

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

        $html = $this->get(route('magic.link', ['token' => $reg->magic_token]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('accept="application/pdf,image/jpeg,image/png"', $html);
        $this->assertStringNotContainsString('accept=".pdf,.jpg,.jpeg,.png"', $html);
        $this->assertStringNotContainsString('accept=".pdf', $html);
    }

    /** Field gambar tetap pakai wildcard image/*. */
    public function test_portal_field_gambar_pakai_wildcard_mime()
    {
        $reg = $this->peserta();
        $this->buatFieldBaru('foto_ktp', 'Foto KTP', ['type' => 'image', 'is_required' => true]);

        $html = $this->get(route('magic.link', ['token' => $reg->magic_token]))
            ->assertOk()
            ->getContent();

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

        $html = $this->get(route('magic.link', ['token' => $reg->magic_token]))
            ->assertOk()
            ->getContent();

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
