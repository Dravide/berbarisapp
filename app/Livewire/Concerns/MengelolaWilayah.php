<?php

namespace App\Livewire\Concerns;

use App\Models\RegistrationField;
use App\Rules\Wilayah;
use App\Services\WilayahService;

/**
 * Dropdown wilayah berjenjang untuk field bertipe `wilayah`.
 *
 * Dipakai dua komponen yang view-nya berbeda dan tidak punya partial bersama
 * (`Public\Registration\Create` dan `Public\MagicLink\Registration`), jadi
 * perilakunya hidup di sini supaya tidak ada dua salinan aturan reset.
 *
 * Bentuk state: `$wilayah[field_key][tingkat]` = kode wilayahnya. Tingkat
 * memakai nama, bukan angka, karena dipakai langsung sebagai bagian nama
 * properti di view: `wire:model.live="wilayah.asal_kabupaten.provinsi"`.
 *
 * Nilai yang disimpan ke `fieldValues` adalah gabungan kode + nama
 * ("32 - JAWA BARAT / 32.01 - KAB. BOGOR") dan hanya disusun saat peserta
 * BERINTERAKSI. Nilai lama tidak pernah disusun ulang diam-diam — kalau tidak,
 * baris teks bebas dari era sebelumnya akan terhapus begitu form dibuka.
 */
trait MengelolaWilayah
{
    /** @var array<string, array<string, string>> field_key => tingkat => kode */
    public array $wilayah = [];

    /** Nama tingkat berurutan; index-nya sekaligus tingkat kedalamannya. */
    private const TINGKAT_WILAYAH = ['provinsi', 'kabupaten', 'kecamatan'];

    /**
     * Muat pilihan tersimpan dari nilai yang sudah ada.
     *
     * Dipanggil dari mount() kedua komponen. Untuk tiap field wilayah dibaca
     * kode per tingkat dari nilai tersimpan, lalu daftar opsinya diambil supaya
     * dropdown tampil sudah terisi.
     */
    protected function muatWilayahTersimpan($fields): void
    {
        foreach ($fields as $field) {
            if ($field->type !== 'wilayah') {
                continue;
            }

            $kode = WilayahService::kodeDari($this->nilaiWilayahAwal($field));

            if ($kode === []) {
                continue;
            }

            foreach (self::TINGKAT_WILAYAH as $i => $tingkat) {
                $this->wilayah[$field->field_key][$tingkat] = $kode[$i] ?? '';
            }
        }
    }

    /**
     * Peserta mengubah satu tingkat.
     *
     * $key datang dari Livewire sebagai "asal_kabupaten.provinsi" (tanpa awalan
     * nama properti — Livewire memangkasnya). Mengubah tingkat mana pun
     * mengosongkan tingkat di bawahnya, karena kabupaten dari provinsi lama
     * tidak berlaku lagi untuk provinsi baru.
     */
    public function updatedWilayah($value, $key): void
    {
        [$fieldKey, $tingkat] = array_pad(explode('.', (string) $key, 2), 2, null);

        if ($fieldKey === null || $tingkat === null) {
            return;
        }

        // Livewire memanggil hook ini untuk dua bentuk: perubahan satu tingkat
        // ("asal_kabupaten.provinsi") dan penggantian seluruh cabang lewat
        // wire:model tanpa .live ("asal_kabupaten" dengan $value array).
        // Bentuk kedua tidak punya $tingkat — ambil saja tingkat teratas yang
        // terisi supaya resetnya tetap jalan.
        if ($tingkat === '') {
            $terisi = array_filter(self::TINGKAT_WILAYAH, fn ($t) => ($this->wilayah[$fieldKey][$t] ?? '') !== '');

            if ($terisi === []) {
                return;
            }

            $tingkat = end($terisi);
        }

        $posisi = array_search($tingkat, self::TINGKAT_WILAYAH, true);

        if ($posisi === false) {
            return;
        }

        foreach (array_slice(self::TINGKAT_WILAYAH, $posisi + 1) as $bawah) {
            $this->wilayah[$fieldKey][$bawah] = '';
        }

        $this->susunNilaiWilayah($fieldKey);
    }

    /**
     * Opsi satu tingkat untuk satu field.
     *
     * Turun satu tingkat bila kode induknya belum dipilih. Kalau API tidak
     * mengembalikan apa pun (mis. sedang tumbang) hasilnya kosong, dan view
     * memakainya sebagai sinyal untuk menampilkan input teks biasa.
     *
     * @return list<array{kode: string, nama: string}>
     */
    public function opsiWilayah(string $fieldKey, string $tingkat): array
    {
        $provinsi = $this->wilayah[$fieldKey]['provinsi'] ?? '';

        return match ($tingkat) {
            'provinsi' => WilayahService::provinsi(),
            'kabupaten' => $provinsi === '' ? [] : WilayahService::kabupaten($provinsi),
            'kecamatan' => ($this->wilayah[$fieldKey]['kabupaten'] ?? '') === ''
                ? []
                : WilayahService::kecamatan($this->wilayah[$fieldKey]['kabupaten']),
            default => [],
        };
    }

    /**
     * Kedalaman dropdown sebuah field — dipatok `wilayah_level`, atau ikut
     * tingkat lomba event bila `auto`.
     */
    public function kedalamanWilayah(RegistrationField $field): int
    {
        return $field->kedalamanWilayah();
    }

    /**
     * Gabungkan pilihan yang sudah lengkap jadi nilai simpan.
     *
     * Dihitung ulang dari daftar opsi, bukan dari label di DOM: kode + nama
     * resmi datang dari API, jadi yang tersimpan tidak bergantung pada apa yang
     * kebetulan dirender.
     */
    private function susunNilaiWilayah(string $fieldKey): void
    {
        $bagian = [];

        foreach (self::TINGKAT_WILAYAH as $tingkat) {
            $kode = $this->wilayah[$fieldKey][$tingkat] ?? '';

            if ($kode === '') {
                continue;
            }

            $nama = '';

            foreach ($this->opsiWilayah($fieldKey, $tingkat) as $opsi) {
                if ($opsi['kode'] === $kode) {
                    $nama = $opsi['nama'];
                    break;
                }
            }

            if ($nama !== '') {
                $bagian[] = ['kode' => $kode, 'nama' => $nama];
            }
        }

        $this->fieldValues[$fieldKey] = WilayahService::gabung($bagian);
    }

    /**
     * Tambahkan rule wilayah ke aturan satu field.
     *
     * Dipakai kedua komponen dari aturanField()-nya masing-masing. String
     * aturannya dipecah dulu dengan explode('|') — di dalam array, Laravel
     * TIDAK memecah string bergaya "nullable|string|max:600" dan akan
     * mencarinya sebagai method `validateNullable|string|max`, lalu melempar
     * BadMethodCallException.
     *
     * @param  array<string, string|array<int, mixed>>  $aturan
     */
    private function tambahAturanWilayah(array &$aturan, RegistrationField $field, string $aturanDasar): void
    {
        $aturan['fieldValues.' . $field->field_key] = array_merge(
            explode('|', $aturanDasar),
            [new Wilayah($field->kedalamanWilayah(), (bool) $field->is_required, $field->label)],
        );
    }

    /** Nilai awal field wilayah — komponen menyediakannya (fieldValues atau DB). */
    abstract protected function nilaiWilayahAwal(RegistrationField $field): string;
}
