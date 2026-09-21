<?php

namespace App\Livewire\Eventner\Participant;

use App\Models\Registration;
use App\Models\RegistrationField;
use App\Support\PendaftarImport;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Import massal pendaftar dari Excel, dengan langkah pratinjau wajib.
 *
 * Alur menyalin Import Format Penilaian (`FormatNilai\Import`): file dibaca dan
 * divalidasi lebih dulu, hasilnya diparkir di session, lalu ditampilkan sebagai
 * tabel. Satu baris pun belum ditulis ke `registrations` sampai panitia menekan
 * Simpan — penting karena tiap baris punya magic_token, QR, dan rekap sendiri,
 * jadi salah kolom yang lolos mahal dibersihkan.
 *
 * Komponen ini nested di halaman peserta, jadi tidak punya rute sendiri. Target
 * kategorinya mengikuti tab yang sedang terbuka di halaman induk.
 */
class Import extends Component
{
    use WithFileUploads;

    /** Target = tab kategori yang sedang terbuka di halaman induk. */
    public string $activeTab = '';

    public bool $showImportModal = false;

    public $file;

    /** Baris ringkas untuk tabel pratinjau. */
    public array $previewData = [];

    /** Ringkasan + nama kategori tujuan + kolom yang tidak terbaca. */
    public array $previewMeta = [];

    /** Baris yang tidak ikut disimpan, beserta alasannya. */
    public array $rowErrors = [];

    /** Kunci session tempat hasil normalisasi diparkir. */
    public string $previewSessionKey = '';

    public string $fileName = '';

    /**
     * Alasan berkas ditolak, bila ada.
     *
     * Disimpan sebagai properti, bukan hanya flash session: modal membacanya
     * untuk ditampilkan, dan properti membuatnya bisa diperiksa langsung tanpa
     * bergantung pada siklus flash.
     */
    public string $pesanError = '';

    public function mount(string $activeTab = '')
    {
        $this->activeTab = $this->normalizeTab($activeTab);
    }

    /** Ikuti perubahan kategori di halaman induk (dipancarkan Index::updatedActiveTab). */
    #[On('pesan:ganti-kategori')]
    public function setActiveTab($id)
    {
        $this->activeTab = $this->normalizeTab($id);
    }

    /**
     * Normalisasi id kategori dari DOM menjadi id yang boleh dipakai.
     *
     * Hasilnya ditulis ke `registrations.competition_category_id`, dan nilainya
     * berasal dari klien — tanpa pemeriksaan ini pendaftar hasil import bisa
     * mendarat di kategori event lain, atau di kategori induk yang bukan tujuan
     * pendaftaran. Kembalikan '' bila tidak sah.
     */
    private function normalizeTab($id): string
    {
        $ada = PendaftarImport::kategoriUntukPendaftaran(auth()->user()?->eventner, $id);

        return $ada ? (string) $ada->id : '';
    }

    /**
     * Buka modal import.
     *
     * Kategori wajib sudah dipilih — beda dengan Import Format Penilaian, di
     * sana '' berarti "semua tingkat", di sini '' tidak mungkin:
     * `registrations.competition_category_id` NOT NULL.
     */
    public function openImportModal($categoryId = null)
    {
        $this->resetPreview();

        $this->activeTab = $this->normalizeTab($categoryId ?? $this->activeTab);

        if ($this->activeTab === '') {
            return $this->tolak('Pilih kategori lomba terlebih dahulu sebelum mengimpor. Data pendaftar harus punya kategori.');
        }

        $this->showImportModal = true;
        $this->dispatch('import:modal-open');
    }

    public function closeImportModal()
    {
        $this->resetPreview();
        $this->showImportModal = false;
        $this->file = null;
    }

    /**
     * Field yang boleh diimpor: baris builder aktif yang punya kolom nyata.
     *
     * Berbeda dari modal manual yang menyaring dengan `property_exists()`
     * (Livewire menolak properti yang tidak ada), di sini nilainya dipetakan
     * dari kolom Excel — jadi patokannya daftar kolom yang didukung.
     */
    private function fieldsImport()
    {
        return RegistrationField::forEventner(auth()->user()->eventner)
            ->reject(fn ($f) => $f->isFile() || $f->isGroup())
            ->filter(fn ($f) => in_array($f->builtin_source, PendaftarImport::KOLOM_DIDUKUNG, true))
            ->values();
    }

    /**
     * Baca file → pratinjau. Tidak menulis apa pun ke DB.
     */
    public function uploadExcel()
    {
        $this->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls', 'max:'.PendaftarImport::MAX_FILE_KB],
        ], [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.extensions' => 'File harus berformat .xlsx atau .xls.',
            'file.max' => 'Ukuran file maksimal '.PendaftarImport::MAX_FILE_KB.' KB.',
        ]);

        $this->pesanError = '';

        $eventner = auth()->user()?->eventner;
        if (! $eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        // Tab bisa sudah berganti sejak modal dibuka, jadi diperiksa ulang.
        $kategori = PendaftarImport::kategoriUntukPendaftaran($eventner, $this->activeTab);
        if (! $kategori) {
            return $this->tolak('Kategori lomba tidak valid. Pilih ulang kategori di halaman ini.');
        }

        try {
            $reader = IOFactory::createReaderForFile($this->file->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($this->file->getRealPath());
            $rows = $spreadsheet->getActiveSheet()->toArray();
            $spreadsheet->disconnectWorksheets();
        } catch (\Throwable $e) {
            return $this->tolak('Gagal membaca file Excel: '.$e->getMessage());
        }

        if (empty($rows)) {
            return $this->tolak('File Excel kosong.');
        }

        $fields = $this->fieldsImport();
        $headerRow = $rows[0];

        // Baris header wajib ada: tanpa itu, baris pertama diperlakukan sebagai
        // data dan panitia disuguhi puluhan error baris yang membingungkan.
        if (! PendaftarImport::isHeaderRow($headerRow, $fields)) {
            return $this->tolak('Baris header tidak ditemukan. Gunakan tombol Download Template supaya nama kolomnya cocok.');
        }

        array_shift($rows);

        $normalized = PendaftarImport::normalizeRows(
            $rows,
            $headerRow,
            $fields,
            $this->kunciTersimpan($eventner->id, $kategori->id),
            $kategori->id,
            rowOffset: 1,
            // Longgarkan NPSN + tegakkan kolom NOT NULL — lihat PendaftarImport::aturanDari.
            untukImport: true
        );

        // Ringkasan selalu diisi — walau tidak ada baris baru, panitia perlu
        // melihat kolom mana yang tidak terbaca dan baris mana yang bermasalah.
        $this->previewMeta = [
            'targetName' => $kategori->full_name ?? $kategori->name,
            'baru' => $normalized['ringkasan']['baru'],
            'duplikat' => $normalized['ringkasan']['duplikat'],
            'error' => $normalized['ringkasan']['error'],
            'kolomDiabaikan' => $normalized['kolomDiabaikan'],
        ];
        $this->rowErrors = $normalized['errors'];

        if ($normalized['ringkasan']['baru'] === 0) {
            return $this->tolak('Tidak ada baris baru yang bisa diimpor. Semua baris duplikat atau bermasalah.');
        }

        $this->previewSessionKey = 'import_pendaftar_'.$eventner->id.'_'.bin2hex(random_bytes(6));
        session([$this->previewSessionKey => $normalized]);

        $this->previewData = PendaftarImport::previewRows($normalized);
        $this->fileName = $this->file->getClientOriginalName();
        $this->showImportModal = true;

        $this->dispatch('import:preview-ready');
    }

    /** Tolak berkas: pesannya disimpan agar bisa dibaca modal dan diperiksa tes. */
    private function tolak(string $pesan): void
    {
        $this->pesanError = $pesan;
        $this->showImportModal = true;
    }

    /**
     * Kunci pembanding duplikat yang sudah ada di DB.
     *
     * Tabel `registrations` tidak punya unique index untuk kombinasi
     * (npsn, kategori, label), jadi deteksi harus query eksplisit — bukan
     * mengandalkan constraint. Baris `dibatalkan` tidak dihitung: itu justru
     * baris yang digantikan.
     */
    private function kunciTersimpan(int $eventnerId, int $kategoriId): array
    {
        return Registration::where('eventner_id', $eventnerId)
            ->where('competition_category_id', $kategoriId)
            ->where('status_berkas', '!=', 'dibatalkan')
            ->get(['npsn', 'nama_sekolah', 'label_pasukan'])
            ->mapWithKeys(fn ($r) => [
                PendaftarImport::kunci($r->npsn, $r->nama_sekolah, $kategoriId, $r->label_pasukan) => true,
            ])
            ->all();
    }

    public function closePreview()
    {
        $this->resetPreview();
        $this->file = null;
    }

    /** Nama kategori tujuan untuk ditampilkan di modal langkah 1. */
    public function targetName(): string
    {
        $kategori = PendaftarImport::kategoriUntukPendaftaran(auth()->user()?->eventner, $this->activeTab);

        return $kategori
            ? ($kategori->full_name ?? $kategori->name)
            : 'Kategori tidak diketahui — pilih ulang kategori di halaman ini.';
    }

    /**
     * Tulis hasil pratinjau ke DB dalam satu transaksi.
     *
     * Semua baris masuk atau tidak sama sekali: kegagalan di tengah (mis.
     * tabrakan qr_token) tidak boleh meninggalkan setengah file terimpor tanpa
     * jejak baris mana yang sudah masuk.
     */
    public function confirmImport()
    {
        $eventner = auth()->user()?->eventner;
        if (! $eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        if (! $this->previewSessionKey || ! session()->has($this->previewSessionKey)) {
            return $this->tolak('Sesi pratinjau berakhir. Silakan upload ulang file.');
        }

        $normalized = session($this->previewSessionKey);

        // Kategori ditulis ke DB, jadi jangan percaya nilai properti begitu saja
        // — tab bisa sudah berganti sejak pratinjau dibuat.
        $kategori = PendaftarImport::kategoriUntukPendaftaran($eventner, $this->activeTab);
        if (! $kategori) {
            return $this->tolak('Kategori lomba tidak valid. Pilih ulang kategori lalu ulangi import.');
        }

        $fields = $this->fieldsImport();
        $tersimpan = 0;
        $terlewat = 0;

        try {
            DB::transaction(function () use ($eventner, $kategori, $fields, $normalized, &$tersimpan, &$terlewat) {
                // Diperiksa ULANG di dalam transaksi: antara pratinjau dan
                // konfirmasi, panitia bisa saja menambah pendaftar manual dengan
                // NPSN + label yang sama.
                $duplikat = $this->kunciTersimpan($eventner->id, $kategori->id);

                foreach ($normalized['rows'] as $row) {
                    if ($row['status'] !== 'baru') {
                        continue;
                    }

                    $data = [];
                    foreach ($fields as $field) {
                        $nilai = $row['data'][$field->builtin_source] ?? null;
                        $data[$field->builtin_source] = ($nilai !== null && $nilai !== '')
                            ? strip_tags((string) $nilai)
                            : null;
                    }

                    // Kolom NOT NULL: jangan kirim null (STRICT_TRANS_TABLES
                    // menolaknya). Validasi pratinjau sudah menahan baris kosong,
                    // ini jaring supaya satu baris tidak menggagalkan transaksi.
                    foreach (PendaftarImport::KOLOM_WAJIB_DB as $wajib) {
                        $data[$wajib] = (string) ($data[$wajib] ?? '');
                    }

                    // NPSN kosong disimpan sebagai null, bukan '' — pembanding
                    // duplikat membedakan "tanpa NPSN" dari NPSN kosong sengaja.
                    if (($data['npsn'] ?? '') === '') {
                        $data['npsn'] = null;
                    }

                    $kunci = PendaftarImport::kunci(
                        $data['npsn'] ?? null,
                        $data['nama_sekolah'] ?? null,
                        $kategori->id,
                        $row['data']['label_pasukan'] ?? null
                    );

                    if (isset($duplikat[$kunci])) {
                        $terlewat++;

                        continue;
                    }

                    $duplikat[$kunci] = true;

                    Registration::create([
                        ...$data,
                        'eventner_id' => $eventner->id,
                        'competition_category_id' => $kategori->id,
                        'label_pasukan' => ($label = trim((string) ($row['data']['label_pasukan'] ?? ''))) !== ''
                            ? strip_tags($label)
                            : null,
                        'status_berkas' => 'Menunggu',
                    ]);

                    $tersimpan++;
                }
            });
        } catch (\Throwable $e) {
            return $this->tolak('Gagal menyimpan import: '.$e->getMessage());
        }

        $this->resetPreview();
        $this->showImportModal = false;
        $this->file = null;

        $message = "Import berhasil: {$tersimpan} pendaftar baru"
            .($terlewat > 0 ? ", {$terlewat} dilewati karena sudah terdaftar." : '.');

        $this->dispatch('import:done', message: $message);
        session()->flash('success', $message);

        // Daftar di halaman induk ikut segar tanpa reload.
        $this->dispatch('$refresh');
    }

    private function resetPreview()
    {
        if ($this->previewSessionKey) {
            session()->forget($this->previewSessionKey);
            $this->previewSessionKey = '';
        }

        $this->reset('previewData', 'previewMeta', 'rowErrors', 'fileName', 'pesanError');
    }

    public function render()
    {
        return view('livewire.eventner.participant.import');
    }
}
