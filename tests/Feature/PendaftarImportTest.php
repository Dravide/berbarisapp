<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Participant\Import;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\RegistrationField;
use App\Models\User;
use App\Support\PendaftarImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Import pendaftar massal dari Excel.
 *
 * Yang dijaga di sini bukan cuma "barisnya masuk", tapi janji utama fiturnya:
 * berkas dibaca dan divalidasi TANPA menulis apa pun ke `registrations`, dan
 * penulisan baru terjadi setelah panitia menekan Simpan.
 */
class PendaftarImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Bangun .xlsx sungguhan — parser membaca berkas nyata, jadi fixture palsu
     * tidak menguji jalur PhpSpreadsheet sama sekali.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function makeXlsx(array $rows, ?array $headers = null): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headers ??= ['Nama Sekolah', 'NPSN', 'Nama Pasukan', 'Nama Pelatih', 'No. HP', 'Email Sekolah'];

        foreach ($headers as $i => $header) {
            $sheet->setCellValue(chr(65 + $i).'1', $header);
        }
        $sheet->fromArray($rows, null, 'A2');

        $tmp = tempnam(sys_get_temp_dir(), 'pst_import_test');
        (new Xlsx($spreadsheet))->save($tmp);

        return UploadedFile::fake()->createWithContent('import.xlsx', file_get_contents($tmp));
    }

    /** Satu baris data valid; $overrides menimpa kolom per indeks. */
    private function baris(string $sekolah = 'SMP Negeri 1 Uji', string $npsn = '12345678', string $pasukan = 'Pasukan A', array $overrides = []): array
    {
        $row = [$sekolah, $npsn, $pasukan, 'Pelatih Uji', '08123456789', 'uji@example.test'];

        foreach ($overrides as $i => $nilai) {
            $row[$i] = $nilai;
        }

        return $row;
    }

    private function setUpEventner(): array
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create(['user_id' => $user->id, 'status' => 'approved']);

        $parent = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id]);
        $child = CompetitionCategory::factory()->child($parent)->create(['eventner_id' => $eventner->id]);

        // Daftar field imporable dibaca dari tabel ini — tanpa ensureDefaults
        // tidak ada satu pun kolom yang dikenali.
        RegistrationField::ensureDefaults($eventner);

        return [$user, $eventner, $parent, $child];
    }

    private function komponen(User $user, int $childId, UploadedFile $file)
    {
        return Livewire::actingAs($user)
            ->test(Import::class, ['activeTab' => (string) $childId])
            ->set('file', $file)
            ->call('uploadExcel');
    }

    // ── Janji utama: pratinjau dulu ─────────────────────────────────────

    public function test_unggah_membangun_pratinjau_tanpa_menulis_db()
    {
        [$user, , , $child] = $this->setUpEventner();

        $komponen = $this->komponen($user, $child->id, $this->makeXlsx([
            $this->baris(),
            $this->baris('SMP Negeri 2 Uji', '87654321', 'Pasukan B'),
            $this->baris('SMP Negeri 3 Uji', '11112222', ''),
        ]));

        $komponen->assertSet('showImportModal', true);
        $komponen->assertCount('previewData', 3);
        $komponen->assertSet('previewMeta.baru', 3);

        // Inti fiturnya: belum ada satu baris pun yang ditulis.
        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_konfirmasi_menulis_registrasi()
    {
        [$user, $eventner, , $child] = $this->setUpEventner();

        $this->komponen($user, $child->id, $this->makeXlsx([$this->baris()]))
            ->call('confirmImport')
            ->assertSet('showImportModal', false);

        $this->assertDatabaseHas('registrations', [
            'eventner_id' => $eventner->id,
            'competition_category_id' => $child->id,
            'nama_sekolah' => 'SMP Negeri 1 Uji',
            'npsn' => '12345678',
            'label_pasukan' => 'Pasukan A',
            'status_berkas' => 'Menunggu',
        ]);

        // magic_token diisi hook creating model, bukan komponen.
        $reg = Registration::firstOrFail();
        $this->assertNotEmpty($reg->magic_token);
        $this->assertNotEmpty($reg->qr_token);
        $this->assertFalse((bool) $reg->is_finalized);
    }

    // ── Duplikat ───────────────────────────────────────────────────────

    public function test_duplikat_terhadap_db_dilewati()
    {
        [$user, $eventner, , $child] = $this->setUpEventner();

        Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $child->id,
            'nama_sekolah' => 'SMP Negeri 1 Uji',
            'npsn' => '12345678',
            'label_pasukan' => 'Pasukan A',
        ]);

        $this->komponen($user, $child->id, $this->makeXlsx([$this->baris()]))
            ->assertSet('previewMeta.duplikat', 1)
            ->assertSet('previewMeta.baru', 0);

        $this->assertDatabaseCount('registrations', 1);
    }

    public function test_duplikat_di_dalam_satu_file_dilewati()
    {
        [$user, , , $child] = $this->setUpEventner();

        $this->komponen($user, $child->id, $this->makeXlsx([$this->baris(), $this->baris()]))
            ->assertSet('previewMeta.baru', 1)
            ->assertSet('previewMeta.duplikat', 1)
            ->call('confirmImport');

        $this->assertDatabaseCount('registrations', 1);
    }

    /** Pembanding duplikat menormalkan huruf besar/kecil. */
    public function test_duplikat_tidak_peduli_huruf_besar_kecil()
    {
        [$user, $eventner, , $child] = $this->setUpEventner();

        Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $child->id,
            'nama_sekolah' => 'SMP Negeri 1 Uji',
            'npsn' => '12345678',
            'label_pasukan' => 'PASUKAN A',
        ]);

        $this->komponen($user, $child->id, $this->makeXlsx([$this->baris()]))
            ->assertSet('previewMeta.duplikat', 1);
    }

    /**
     * Dua sekolah berbeda yang sama-sama tanpa NPSN tidak boleh saling dianggap
     * duplikat — pembandingnya jatuh ke nama sekolah.
     */
    public function test_npsn_kosong_tidak_menjadikan_semua_baris_duplikat()
    {
        [$user, , , $child] = $this->setUpEventner();

        $this->komponen($user, $child->id, $this->makeXlsx([
            $this->baris('SMP Negeri 1 Uji', '', 'Pasukan A'),
            $this->baris('SMP Negeri 2 Uji', '', 'Pasukan A'),
        ]))
            ->assertSet('previewMeta.baru', 2)
            ->assertSet('previewMeta.duplikat', 0);
    }

    // ── Validasi ───────────────────────────────────────────────────────

    public function test_baris_kurang_data_masuk_row_errors()
    {
        [$user, , , $child] = $this->setUpEventner();

        // Baris Excel 3 = urutan ketiga dalam file (baris 1 header, baris 2 data).
        $komponen = $this->komponen($user, $child->id, $this->makeXlsx([
            $this->baris(),
            $this->baris('SMP Negeri 2 Uji', '87654321', 'Pasukan B', [0 => '']),
        ]));

        $komponen->assertSet('previewMeta.error', 1);

        $errors = $komponen->get('rowErrors');
        $this->assertCount(1, $errors);
        $this->assertSame(3, $errors[0]['row'], 'Nomor baris harus cocok dengan nomor baris di Excel.');
    }

    public function test_label_pasukan_kepanjangan_ditolak()
    {
        [$user, , , $child] = $this->setUpEventner();

        $this->komponen($user, $child->id, $this->makeXlsx([
            $this->baris('SMP Negeri 1 Uji', '12345678', 'Pasukan Amat Panjang'),
        ]))
            ->assertSet('previewMeta.error', 1)
            ->assertSet('previewMeta.baru', 0);
    }

    public function test_kolom_tak_dikenal_diabaikan()
    {
        [$user, , , $child] = $this->setUpEventner();

        $file = $this->makeXlsx(
            [$this->baris()],
            ['Nama Sekolah', 'NPSN', 'Nama Pasukan', 'Nama Pelatih', 'No. HP', 'Email Sekolah', 'Catatan Panitia']
        );

        $this->komponen($user, $child->id, $file)
            ->assertSet('previewMeta.baru', 1)
            ->assertSee('Catatan Panitia');
    }

    public function test_unggah_menolak_ekstensi_lain()
    {
        [$user, , , $child] = $this->setUpEventner();

        Livewire::actingAs($user)
            ->test(Import::class, ['activeTab' => (string) $child->id])
            ->set('file', UploadedFile::fake()->create('data.pdf', 10))
            ->call('uploadExcel')
            ->assertHasErrors('file');
    }

    public function test_unggah_menolak_file_tanpa_header()
    {
        [$user, , , $child] = $this->setUpEventner();

        // Tanpa baris judul: baris pertama adalah data, dan tidak ada satu pun
        // sel yang dikenali sebagai nama kolom.
        $file = $this->makeXlsx([['SMP Negeri 1 Uji', '12345678']], ['aaa', 'bbb', 'ccc', 'ddd', 'eee', 'fff']);

        $komponen = $this->komponen($user, $child->id, $file);

        // Modal tetap terbuka supaya pesannya terbaca — bukan ditutup diam-diam.
        $komponen->assertSet('showImportModal', true);
        $this->assertStringContainsString('header', $komponen->get('pesanError'));
        $komponen->assertCount('previewData', 0);

        $this->assertDatabaseCount('registrations', 0);
    }

    // ── NPSN kosong ────────────────────────────────────────────────────

    public function test_npsn_kosong_tetap_tersimpan()
    {
        [$user, , , $child] = $this->setUpEventner();

        $this->komponen($user, $child->id, $this->makeXlsx([
            $this->baris('SMP Negeri 1 Uji', '', 'Pasukan A'),
        ]))->call('confirmImport');

        $this->assertDatabaseHas('registrations', [
            'nama_sekolah' => 'SMP Negeri 1 Uji',
            'npsn' => null,
            'label_pasukan' => 'Pasukan A',
        ]);
    }

    // ── Penjagaan kategori ─────────────────────────────────────────────

    public function test_konfirmasi_menolak_kategori_induk()
    {
        [$user, , $parent, $child] = $this->setUpEventner();

        $komponen = $this->komponen($user, $child->id, $this->makeXlsx([$this->baris()]));

        // Tab dipaksa ke induk setelah pratinjau dibuat.
        $komponen->set('activeTab', (string) $parent->id)->call('confirmImport');

        $this->assertStringContainsString('Kategori', $komponen->get('pesanError'));
        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_konfirmasi_menolak_kategori_event_lain()
    {
        [$user, , , $child] = $this->setUpEventner();

        $lain = Eventner::factory()->create(['status' => 'approved']);
        $kategoriLain = CompetitionCategory::factory()->create(['eventner_id' => $lain->id]);

        $komponen = $this->komponen($user, $child->id, $this->makeXlsx([$this->baris()]));

        $komponen->set('activeTab', (string) $kategoriLain->id)->call('confirmImport');

        $this->assertStringContainsString('Kategori', $komponen->get('pesanError'));
        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_konfirmasi_tanpa_sesi_pratinjau()
    {
        [$user, , , $child] = $this->setUpEventner();

        $komponen = $this->komponen($user, $child->id, $this->makeXlsx([$this->baris()]));

        // Session pratinjau hilang (kedaluwarsa / tab lain menimpanya).
        session()->forget($komponen->get('previewSessionKey'));

        $komponen->call('confirmImport');

        $this->assertStringContainsString('upload ulang', $komponen->get('pesanError'));
        $this->assertDatabaseCount('registrations', 0);
    }

    /**
     * Jendela pendaftaran yang sudah tertutup TIDAK menghalangi import — sama
     * seperti tombol Tambah Pendaftar di halaman ini. Import adalah alat
     * panitia, bukan pendaftaran publik.
     */
    public function test_import_tidak_terpengaruh_jendela_pendaftaran()
    {
        [$user, $eventner, , $child] = $this->setUpEventner();

        $eventner->update(['tanggal_pendaftaran' => now()->subMonth()->format('Y-m-d')]);

        $this->komponen($user, $child->id, $this->makeXlsx([$this->baris()]))
            ->call('confirmImport');

        $this->assertDatabaseCount('registrations', 1);
    }

    // ── Template & halaman ─────────────────────────────────────────────

    public function test_template_terunduh()
    {
        [$user] = $this->setUpEventner();

        $response = $this->actingAs($user)->get(route('eventner.participants.template'));

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml',
            $response->headers->get('content-type'),
            'Rute template harus mengembalikan berkas xlsx — sekaligus mengunci urutannya sebelum participants/{registration}.'
        );
    }

    public function test_tamu_diarahkan_ke_login()
    {
        $this->get(route('eventner.participants.template'))->assertRedirect('/login');
    }

    /**
     * Tombol pemicunya dirender komponen import, jadi diuji lewat halaman
     * sungguhan — bukan `Livewire::test(ParticipantIndex::class)`, yang tidak
     * merender komponen nested sehingga tombolnya memang tak akan terlihat.
     */
    public function test_halaman_merender_tombol_import()
    {
        [$user] = $this->setUpEventner();

        $this->actingAs($user)
            ->get(route('eventner.participants.index'))
            ->assertOk()
            ->assertSee('Import Pendaftar')
            ->assertSee('Tambah Pendaftar');
    }

    /** Tautan template baru muncul setelah modal dibuka — diuji di modalnya. */
    public function test_modal_menyediakan_tautan_template()
    {
        [$user, , , $child] = $this->setUpEventner();

        Livewire::actingAs($user)
            ->test(Import::class, ['activeTab' => (string) $child->id])
            ->call('openImportModal')
            ->assertSet('showImportModal', true)
            ->assertSee('Download Template')
            ->assertSee(route('eventner.participants.template'));
    }

    // ── Kelas Support ──────────────────────────────────────────────────

    /** KOLOM_DIDUKUNG dan HEADER harus sejajar posisinya. */
    public function test_kolom_didukung_sejajar_dengan_header()
    {
        $this->assertSame(
            array_values(PendaftarImport::HEADER),
            array_map(fn ($s) => PendaftarImport::HEADER[array_search($s, PendaftarImport::KOLOM_DIDUKUNG, true)] ?? null, PendaftarImport::KOLOM_DIDUKUNG),
            'Setiap kolom di KOLOM_DIDUKUNG harus punya pasangan di HEADER pada indeks yang sama.'
        );
    }

    /** Header dikenali lewat label builder, bukan cuma alias bawaan. */
    public function test_header_mengikuti_label_builder()
    {
        [$user, $eventner, , $child] = $this->setUpEventner();

        // Panitia mengganti label Nama Sekolah di builder.
        RegistrationField::where('eventner_id', $eventner->id)
            ->where('builtin_source', 'nama_sekolah')
            ->update(['label' => 'Sekolah Kontingen']);

        $this->komponen($user, $child->id, $this->makeXlsx(
            [$this->baris()],
            ['Sekolah Kontingen', 'NPSN', 'Nama Pasukan', 'Nama Pelatih', 'No. HP', 'Email Sekolah']
        ))->assertSet('previewMeta.baru', 1);
    }
}
