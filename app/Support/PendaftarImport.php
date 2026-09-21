<?php

namespace App\Support;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

/**
 * Helper murni untuk import pendaftar (tabel `registrations`) dari file Excel.
 *
 * Tanpa DB dan tanpa HTTP — seluruh keputusan di sini bisa diuji tanpa
 * menyentuh basis data. Pemanggil (komponen Livewire) yang menyiapkan daftar
 * field dan himpunan duplikat, lalu menuliskan hasilnya.
 *
 * Kolom dan aturan datang dari builder (`registration_fields`), bukan literal:
 * template digenerate dari daftar yang sama dengan parser, jadi panitia yang
 * mengganti label field di /eventner/registration-fields tidak membuat import
 * diam-diam tidak sinkron. Lihat RegistrationField::defaults().
 *
 * Yang TIDAK diimpor, dan alasannya:
 *  - Field berkas/foto (Logo Sekolah, Surat Tugas, Kwitansi, Foto Pelatih,
 *    Foto Danton): jalur import tidak bisa mengunggah, nilainya path di disk.
 *  - Field buatan panitia (Nama Pembina, Asal Kabupaten / Kota): nilainya hidup
 *    di `registration_field_values`, bukan kolom di `registrations`.
 *  - Field group (Anggota Pasukan): anggotanya hidup di tabel `participants`.
 *  - Field danton (Nama Danton, NISN Danton): punya kolom nyata, tapi modal
 *    manual pun tidak bisa mengisinya (`Participant\Index` tidak punya
 *    propertinya), jadi dikeluarkan supaya import setara dengan modal.
 */
class PendaftarImport
{
    /** Sinkron dengan batas unggahan di FormatNilai\Import. */
    public const MAX_FILE_KB = 2048;

    /**
     * Batas baris per file.
     *
     * Hasil normalisasi diparkir di session sampai panitia menekan Simpan, dan
     * di produksi `SESSION_DRIVER=database` — payload yang membengkak berarti
     * satu baris `sessions` besar untuk setiap pratinjau yang ditinggalkan.
     */
    public const MAX_BARIS = 2000;

    /**
     * Lebar kolom `registrations.label_pasukan` (varchar 10).
     *
     * Diperiksa di sini, bukan diserahkan ke DB: MySQL dengan sql_mode longgar
     * memotong diam-diam, dan label yang terpotong merusak pencocokan duplikat
     * pada import berikutnya. Modal manual tidak pernah memvalidasi ini karena
     * tidak punya propertinya — jalur import adalah yang pertama mengisi kolom
     * ini, jadi batasnya harus ditegakkan di sini.
     */
    public const MAX_LABEL_PASUKAN = 10;

    // Indeks kolom (0-based dari $sheet->toArray()).
    public const COL_NAMA_SEKOLAH = 0;

    public const COL_NPSN = 1;

    public const COL_LABEL_PASUKAN = 2;

    public const COL_NAMA_PELATIH = 3;

    public const COL_NO_HP = 4;

    public const COL_SCHOOL_EMAIL = 5;

    /** Urutan kolom bawaan template — generator template dan parser membaca ini. */
    public const HEADER = [
        self::COL_NAMA_SEKOLAH => 'Nama Sekolah',
        self::COL_NPSN => 'NPSN',
        self::COL_LABEL_PASUKAN => 'Nama Pasukan',
        self::COL_NAMA_PELATIH => 'Nama Pelatih',
        self::COL_NO_HP => 'No. HP',
        self::COL_SCHOOL_EMAIL => 'Email Sekolah',
    ];

    /**
     * Kolom yang boleh diimpor — semuanya punya kolom nyata di `registrations`.
     *
     * Diturunkan dari HEADER supaya urutannya tidak bisa melenceng dari
     * template: satu daftar, bukan dua yang harus dijaga sejajar.
     *
     * `label_pasukan` ikut walau tidak ada di modal manual: panitia memang
     * butuh menandai beberapa pasukan dari satu sekolah.
     */
    public const KOLOM_DIDUKUNG = [
        'nama_sekolah',
        'npsn',
        'label_pasukan',
        'nama_pelatih',
        'no_hp',
        'school_email',
    ];

    /**
     * Alias header tambahan, di luar label builder.
     *
     * Label builder sudah dicocokkan lebih dulu (template digenerate dari sana),
     * jadi tabel ini hanya menambal ejaan yang sering dipakai panitia saat
     * mengetik file sendiri.
     */
    public const ALIAS = [
        'namasekolah' => 'nama_sekolah',
        'sekolah' => 'nama_sekolah',
        'npsn' => 'npsn',
        'nomorpokoksekolahnasional' => 'npsn',
        'namapasukan' => 'label_pasukan',
        'pasukan' => 'label_pasukan',
        'labelpasukan' => 'label_pasukan',
        'namapelatih' => 'nama_pelatih',
        'pelatih' => 'nama_pelatih',
        'nohp' => 'no_hp',
        'hp' => 'no_hp',
        'nomorhp' => 'no_hp',
        'telp' => 'no_hp',
        'telepon' => 'no_hp',
        'wa' => 'no_hp',
        'whatsapp' => 'no_hp',
        'emailsekolah' => 'school_email',
        'email' => 'school_email',
        'schoolemail' => 'school_email',
    ];

    /**
     * Kolom NOT NULL di `registrations` yang tidak punya default.
     *
     * Diperiksa eksplisit: STRICT_TRANS_TABLES menolak NULL, dan kegagalannya
     * terjadi di tengah transaksi import — jauh lebih mahal daripada satu baris
     * error di pratinjau. `npsn` TIDAK ikut di sini karena kolomnya sudah
     * nullable (lihat migrasi 2026_09_21_000001).
     */
    public const KOLOM_WAJIB_DB = ['nama_sekolah', 'no_hp'];

    // ── Dipakai bersama Participant\Index — satu definisi, tidak bisa drift ──

    /**
     * Aturan validasi dari baris builder.
     *
     * Isinya sama dengan modal manual (dulu Participant\Index::aturanFieldModal),
     * ditambah satu aturan yang hanya dibutuhkan import: batas panjang Nama
     * Pasukan.
     *
     * Dua pelonggaran/pengetatan di bawah hanya berlaku di jalur import
     * ($untukImport) dan TIDAK boleh bocor ke modal manual, yang sejak dulu
     * menghormati builder apa adanya:
     *
     * - NPSN: import membolehkan NPSN kosong — panitia yang memindahkan
     *   pendaftaran lama sering tidak memegang nomornya, dan kolomnya sudah
     *   nullable (migrasi 2026_09_21). Di modal manual NPSN diisi panitia
     *   sendiri, jadi builder tetap dihormati.
     * - KOLOM_WAJIB_DB: hanya di import, kolom NOT NULL dipaksa wajib walau
     *   panitia mematikan tanda wajibnya di builder. Di modal manual barisnya
     *   cuma gagal; di sini satu baris menggagalkan seluruh transaksi.
     *
     * @param  iterable<\App\Models\RegistrationField>  $fields
     */
    public static function aturanDari(iterable $fields, bool $untukImport = false): array
    {
        $aturan = [];

        foreach ($fields as $field) {
            $aturan[$field->builtin_source] = match ($field->builtin_source) {
                'school_email' => ($field->is_required ? 'required' : 'nullable').'|email|max:255',
                'npsn' => ($untukImport || ! $field->is_required ? 'nullable' : 'required').'|string|max:20',
                default => $field->validationRule($field->is_required),
            };

            if ($field->builtin_source === 'label_pasukan') {
                $aturan['label_pasukan'] = 'nullable|string|max:'.self::MAX_LABEL_PASUKAN;
            }
        }

        if ($untukImport) {
            foreach (self::KOLOM_WAJIB_DB as $wajib) {
                if (! isset($aturan[$wajib]) || ! str_contains($aturan[$wajib], 'required')) {
                    $aturan[$wajib] = 'required|string|max:255';
                }
            }
        }

        return $aturan;
    }

    /**
     * Pesan error memakai label panitia, bukan nama kolom.
     *
     * @param  iterable<\App\Models\RegistrationField>  $fields
     */
    public static function pesanDari(iterable $fields): array
    {
        $pesan = [];

        foreach ($fields as $field) {
            $pesan[$field->builtin_source.'.required'] = $field->label.' wajib diisi.';
            $pesan[$field->builtin_source.'.email'] = $field->label.' harus berupa alamat email yang valid.';
        }

        foreach (self::KOLOM_WAJIB_DB as $wajib) {
            $pesan[$wajib.'.required'] ??= 'Kolom ini tidak boleh kosong.';
        }

        $pesan['label_pasukan.max'] = 'Nama Pasukan maksimal '.self::MAX_LABEL_PASUKAN.' karakter.';

        return $pesan;
    }

    /**
     * Kategori tujuan pendaftaran: milik event ini DAN tingkat lomba.
     *
     * Cermin Participant\Index::kategoriTerpilih(). Id kategori datang dari DOM,
     * jadi `exists` polos tidak cukup — tanpa scope ini pendaftar hasil import
     * bisa mendarat di kategori event lain, atau di kategori induk yang bukan
     * tujuan pendaftaran.
     */
    public static function kategoriUntukPendaftaran(?Eventner $eventner, $categoryId): ?CompetitionCategory
    {
        if (! $eventner || $categoryId === '' || $categoryId === null) {
            return null;
        }

        return CompetitionCategory::where('eventner_id', $eventner->id)
            ->selectable()
            ->find($categoryId);
    }

    // ── Pencocokan header ───────────────────────────────────────────────

    /**
     * Label kolom untuk baris header, urut KOLOM_DIDUKUNG.
     *
     * Urutan tetap (bukan sort_order builder) supaya template tidak berubah
     * tata letaknya hanya karena panitia menggeser urutan field — file lama
     * tetap terbaca karena pencocokannya per nama header, bukan posisi.
     *
     * @param  Collection<int, \App\Models\RegistrationField>  $fields
     */
    public static function headers(Collection $fields): array
    {
        $label = $fields->pluck('label', 'builtin_source');

        return array_map(
            // `$i` = indeks kolom, jadi fallback-nya HEADER pada posisi yang
            // sama — itulah yang menjaga KOLOM_DIDUKUNG tetap sejajar HEADER.
            fn (string $source, int $i) => $label[$source] ?? self::HEADER[$i] ?? $source,
            self::KOLOM_DIDUKUNG,
            array_keys(self::KOLOM_DIDUKUNG)
        );
    }

    /** Bandingkan header tanpa peduli huruf besar/kecil, spasi, dan tanda baca. */
    public static function normalisasiHeader(?string $header): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $header)));
    }

    /**
     * Header → `builtin_source`; null bila bukan kolom yang dikenal.
     *
     * Label builder dicocokkan lebih dulu karena template digenerate dari sana;
     * ALIAS hanya jaring untuk file yang diketik sendiri panitia.
     *
     * @param  Collection<int, \App\Models\RegistrationField>  $fields
     */
    public static function sumberDariHeader(?string $header, Collection $fields): ?string
    {
        $normal = self::normalisasiHeader($header);

        if ($normal === '') {
            return null;
        }

        foreach ($fields as $field) {
            if (self::normalisasiHeader($field->label) === $normal
                && in_array($field->builtin_source, self::KOLOM_DIDUKUNG, true)) {
                return $field->builtin_source;
            }
        }

        $alias = self::ALIAS[$normal] ?? null;

        return $alias && in_array($alias, self::KOLOM_DIDUKUNG, true) ? $alias : null;
    }

    /**
     * Apakah baris ini baris judul?
     *
     * Diperiksa ke seluruh sel, bukan hanya sel pertama: panitia kadang
     * menggeser kolom, dan kolom pertama bisa saja yang tidak dikenal.
     *
     * @param  Collection<int, \App\Models\RegistrationField>  $fields
     */
    public static function isHeaderRow(array $row, Collection $fields): bool
    {
        foreach ($row as $cell) {
            if (self::sumberDariHeader((string) $cell, $fields) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Peta indeks kolom → `builtin_source`, plus daftar kolom yang diabaikan.
     *
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    public static function petaKolom(array $headerRow, Collection $fields): array
    {
        $peta = [];
        $diabaikan = [];

        foreach ($headerRow as $i => $header) {
            $teks = trim((string) $header);

            if ($teks === '') {
                continue;
            }

            $sumber = self::sumberDariHeader($teks, $fields);

            if ($sumber === null) {
                $diabaikan[] = $teks;

                continue;
            }

            // Kolom kembar: yang pertama menang, sisanya dilaporkan.
            if (in_array($sumber, $peta, true)) {
                $diabaikan[] = $teks;

                continue;
            }

            $peta[$i] = $sumber;
        }

        return [$peta, $diabaikan];
    }

    // ── Duplikat ────────────────────────────────────────────────────────

    /**
     * Kunci pembanding duplikat: NPSN + kategori + Nama Pasukan.
     *
     * Bila NPSN kosong, pembandingnya jatuh ke nama sekolah — dua sekolah
     * berbeda yang sama-sama tanpa NPSN tidak boleh saling dianggap duplikat.
     * Huruf besar/kecil disamakan HANYA untuk pembandingan; nilai yang disimpan
     * tetap memakai ejaan panitia.
     */
    public static function kunci(?string $npsn, ?string $namaSekolah, int $kategoriId, ?string $label): string
    {
        $penanda = trim((string) $npsn) !== ''
            ? 'NPSN:'.mb_strtoupper(trim((string) $npsn))
            : 'SEKOLAH:'.mb_strtoupper(trim((string) $namaSekolah));

        return $penanda.'|'.$kategoriId.'|'.mb_strtoupper(trim((string) $label));
    }

    // ── Inti ────────────────────────────────────────────────────────────

    /**
     * Normalisasi seluruh baris data menjadi baris siap-tulis. Tidak menyentuh DB.
     *
     * @param  array<int, array<int, mixed>>  $rows  data TANPA baris header
     * @param  array<int, mixed>  $headerRow  baris header asli (untuk peta kolom)
     * @param  Collection<int, \App\Models\RegistrationField>  $fields  field imporable
     * @param  array<string, true>  $duplikatTersimpan  kunci yang sudah ada di DB
     * @param  int  $rowOffset  jumlah baris yang dibuang sebelum $rows (header)
     * @param  bool  $untukImport  longgarkan NPSN + tegakkan kolom NOT NULL (lihat aturanDari)
     * @return array{rows: array, errors: array, ringkasan: array, kolomDiabaikan: array}
     */
    public static function normalizeRows(
        array $rows,
        array $headerRow,
        Collection $fields,
        array $duplikatTersimpan,
        int $kategoriId,
        int $rowOffset = 1,
        bool $untukImport = false
    ): array {
        $hasil = [
            'rows' => [],
            'errors' => [],
            'ringkasan' => ['baru' => 0, 'duplikat' => 0, 'error' => 0],
            'kolomDiabaikan' => [],
        ];

        if (count($rows) > self::MAX_BARIS) {
            $hasil['errors'][] = [
                'row' => $rowOffset + 1,
                'message' => 'File berisi lebih dari '.self::MAX_BARIS." baris. Pecah jadi beberapa file lalu impor bertahap.",
            ];

            return $hasil;
        }

        [$peta, $diabaikan] = self::petaKolom($headerRow, $fields);
        $hasil['kolomDiabaikan'] = $diabaikan;
        $aturan = self::aturanDari($fields, $untukImport);
        $pesan = self::pesanDari($fields);

        /** @var array<string, int> $terlihat kunci => nomor baris Excel, untuk duplikat dalam satu file */
        $terlihat = [];

        foreach ($rows as $i => $row) {
            // 1-based, plus baris yang sudah dibuang pemanggil (header), supaya
            // nomor yang ditampilkan cocok dengan nomor baris di Excel.
            $rowNo = $rowOffset + $i + 1;

            $data = [];
            foreach ($peta as $kolom => $sumber) {
                $data[$sumber] = trim((string) ($row[$kolom] ?? ''));
            }

            // Baris kosong sepenuhnya dilewati tanpa error — Excel sering
            // menyisakan baris kosong di ekor file.
            if (collect($data)->every(fn ($v) => $v === '')) {
                continue;
            }

            $validator = Validator::make($data, $aturan, $pesan);

            if ($validator->fails()) {
                $ringkas = implode(' ', Arr::flatten($validator->errors()->messages()));
                $hasil['errors'][] = ['row' => $rowNo, 'message' => $ringkas];
                $hasil['rows'][] = self::barisRingkas($rowNo, $data, 'error', $ringkas);
                $hasil['ringkasan']['error']++;

                continue;
            }

            $kunci = self::kunci(
                $data['npsn'] ?? null,
                $data['nama_sekolah'] ?? null,
                $kategoriId,
                $data['label_pasukan'] ?? null
            );

            if (isset($duplikatTersimpan[$kunci])) {
                $catatan = 'Sudah terdaftar di kategori ini.';
                $hasil['errors'][] = ['row' => $rowNo, 'message' => $catatan];
                $hasil['rows'][] = self::barisRingkas($rowNo, $data, 'duplikat', $catatan);
                $hasil['ringkasan']['duplikat']++;

                continue;
            }

            if (isset($terlihat[$kunci])) {
                $catatan = "Sama dengan baris {$terlihat[$kunci]} di file ini.";
                $hasil['errors'][] = ['row' => $rowNo, 'message' => $catatan];
                $hasil['rows'][] = self::barisRingkas($rowNo, $data, 'duplikat', $catatan);
                $hasil['ringkasan']['duplikat']++;

                continue;
            }

            $terlihat[$kunci] = $rowNo;

            $hasil['rows'][] = self::barisRingkas($rowNo, $data, 'baru', null);
            $hasil['ringkasan']['baru']++;
        }

        return $hasil;
    }

    /** Satu baris untuk tabel pratinjau. */
    private static function barisRingkas(int $rowNo, array $data, string $status, ?string $catatan): array
    {
        return [
            'row' => $rowNo,
            'status' => $status,
            'catatan' => $catatan,
            'data' => $data,
        ];
    }

    /**
     * Baris ringkas untuk tabel pratinjau UI.
     *
     * @return array<int, array{row:int, nama_sekolah:string, npsn:string, label_pasukan:string, pelatih:string, status:string, catatan:?string}>
     */
    public static function previewRows(array $normalized): array
    {
        $rows = [];

        foreach ($normalized['rows'] as $row) {
            $rows[] = [
                'row' => $row['row'],
                'nama_sekolah' => $row['data']['nama_sekolah'] ?? '',
                'npsn' => $row['data']['npsn'] ?? '',
                'label_pasukan' => $row['data']['label_pasukan'] ?? '',
                'pelatih' => $row['data']['nama_pelatih'] ?? '',
                'status' => $row['status'],
                'catatan' => $row['catatan'],
            ];
        }

        return $rows;
    }
}
