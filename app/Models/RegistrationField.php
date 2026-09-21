<?php

namespace App\Models;

use App\Services\WilayahService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Definisi satu kolom formulir pendaftaran milik satu event.
 *
 * Panitia mengaturnya sendiri lewat /eventner/registration-fields. Dua jenis:
 *
 *  - Field bawaan (is_builtin = true): nilai dibaca/ditulis ke kolom nyata di
 *    tabel registrations lewat builtin_source (mis. nama_sekolah). Mengubah
 *    label TIDAK menyentuh data lama — view publik yang membaca kolom literal
 *    (hasil, vote, landing) tetap hidup.
 *  - Field buatan panitia (mis. asal_kabupaten): nilainya hidup di
 *    registration_field_values.
 */
class RegistrationField extends Model
{
    use HasFactory;

    public const TYPES = ['text', 'textarea', 'number', 'date', 'select', 'wilayah', 'file', 'image', 'group'];

    public const FILE_TYPES = ['file', 'image'];

    /**
     * Kedalaman dropdown field bertipe `wilayah`.
     *
     * `auto` mengikuti `tingkat_perlombaan` event (nasional → provinsi,
     * tingkat provinsi → provinsi + kabupaten); sisanya memaksa sendiri, yang
     * mewujudkan "wilayah dinamis" (provinsi + kabupaten + kecamatan sekaligus).
     */
    public const WILAYAH_LEVELS = ['auto', 'provinsi', 'kabupaten', 'kecamatan'];

    /**
     * Kartu tempat field dirender di portal, PDF, dan modal panitia.
     *
     * Judulnya hidup di sini, bukan sebagai literal di tiap view — supaya
     * "Data Pelatih" tidak perlu ditulis ulang di lima berkas.
     */
    public const SECTIONS = [
        'umum' => 'Data Pendaftaran',
        'pelatih' => 'Data Pelatih',
        'danton' => 'Komandan Pleton (Danton)',
    ];

    protected $fillable = [
        'eventner_id',
        'field_key',
        'label',
        'type',
        'section',
        'options',
        'sub_fields',
        'default_value',
        'is_required',
        'is_active',
        'is_builtin',
        'builtin_source',
        'max_kb',
        'help_text',
        'sort_order',
        'wilayah_level',
    ];

    protected $casts = [
        'options' => 'array',
        'sub_fields' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'is_builtin' => 'boolean',
        'max_kb' => 'integer',
        'sort_order' => 'integer',
    ];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function values()
    {
        return $this->hasMany(RegistrationFieldValue::class);
    }

    public function isFile(): bool
    {
        return in_array($this->type, self::FILE_TYPES, true);
    }

    /**
     * Field berulang (mis. daftar anggota pasukan).
     *
     * Nilainya TIDAK pernah masuk loop skalar: `nilaiField()`,
     * `simpanFieldTeks()`, `fieldValuesForDisplay()`, dan validator
     * menyaring baris group. `builtin_source`-nya penanda mesin simpan yang
     * sudah ada (mis. 'peserta' → tabel participants), bukan nama kolom.
     */
    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    /** Judul kartu section ini; fallback ke 'umum' bila slug tidak dikenal. */
    public function sectionTitle(): string
    {
        return self::SECTIONS[$this->section] ?? self::SECTIONS['umum'];
    }

    /**
     * Field aktif milik satu event, urut sesuai tampilan.
     *
     * `eventner` ikut di-eager-load: kedalamanWilayah() membaca
     * tingkat_perlombaan event, dan loop render memangilnya per field — tanpa
     * eager-load itu satu query tambahan untuk tiap field wilayah.
     */
    public static function forEventner(Eventner $eventner): Collection
    {
        return static::where('eventner_id', $eventner->id)
            ->where('is_active', true)
            ->with('eventner')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Baris definisi untuk satu `builtin_source`, termasuk yang nonaktif.
     *
     * Dipakai view yang punya markup tetap (modal panitia, kartu danton) dan
     * hanya butuh label + tanda wajibnya — bukan loop penuh.
     */
    public static function bySource(Eventner $eventner, string $source): ?self
    {
        return static::where('eventner_id', $eventner->id)
            ->where('builtin_source', $source)
            ->first();
    }

    /**
     * Label tampil untuk `builtin_source` tertentu.
     *
     * $fallback = label lama, dipakai kalau event ini belum punya barisnya
     * (belum pernah membuka builder). Bila barisnya ada tapi dinonaktifkan,
     * mengembalikan null — pemanggil memakai itu sebagai sinyal "jangan
     * render".
     */
    public static function labelUntuk(Eventner $eventner, string $source, ?string $fallback = null): ?string
    {
        $field = static::bySource($eventner, $source);

        if (! $field) {
            return $fallback;
        }

        return $field->is_active ? $field->label : null;
    }

    /**
     * Definisi bawaan yang dimiliki setiap event. `field_key` = nama kolom di
     * tabel registrations, jadi field bawaan tidak perlu tabel nilai sendiri.
     *
     * `$eventner` dipakai untuk mewarisi toggle lama (surat_tugas_required,
     * kwitansi_required) ke is_required saat baris pertama kali dibuat.
     */
    public static function defaults(?Eventner $eventner = null): array
    {
        // Toggle lama hanya bilang "wajib", bukan "tampil". Memetakannya ke
        // is_active membuat event dengan kwitansi_required=0 kehilangan field
        // kwitansi sama sekali — panitia tidak punya cara mengunggahnya.
        // Jadi kwitansi yang tidak diwajibkan tetap muncul, hanya tanpa tanda *.
        $suratTugasWajib = (bool) ($eventner->surat_tugas_required ?? true);
        $kwitansiWajib = (bool) ($eventner->kwitansi_required ?? true);

        return [
            [
                'field_key' => 'nama_sekolah',
                'label' => 'Nama Sekolah',
                'type' => 'text',
                'is_required' => true,
                'builtin_source' => 'nama_sekolah',
            ],
            [
                'field_key' => 'npsn',
                'label' => 'NPSN',
                'type' => 'text',
                'is_required' => true,
                'builtin_source' => 'npsn',
                'help_text' => 'Nomor Pokok Sekolah Nasional. Dipakai mencocokkan data sekolah dan menggabungkan pasukan dari sekolah yang sama.',
            ],
            [
                'field_key' => 'label_pasukan',
                'label' => 'Nama Pasukan',
                'type' => 'text',
                'is_required' => false,
                'builtin_source' => 'label_pasukan',
                'help_text' => 'Mis. "Pasukan A", "Pleton 2". Boleh dikosongkan bila sekolah hanya mengirim satu pasukan.',
            ],
            [
                'field_key' => 'nama_pelatih',
                'label' => 'Nama Pelatih',
                'type' => 'text',
                'section' => 'pelatih',
                'is_required' => true,
                'builtin_source' => 'nama_pelatih',
            ],
            [
                'field_key' => 'nama_pembina',
                'label' => 'Nama Pembina',
                'type' => 'text',
                'is_required' => false,
                'builtin_source' => null,
            ],
            [
                'field_key' => 'asal_kabupaten',
                'label' => 'Asal Kabupaten / Kota',
                'type' => 'wilayah',
                'wilayah_level' => 'auto',
                'is_required' => false,
                'builtin_source' => null,
            ],
            [
                'field_key' => 'no_hp',
                'label' => 'No. HP / WhatsApp',
                'type' => 'text',
                'section' => 'pelatih',
                'is_required' => true,
                'builtin_source' => 'no_hp',
            ],
            [
                'field_key' => 'school_email',
                'label' => 'Email Penanggung Jawab',
                'type' => 'text',
                'is_required' => true,
                'builtin_source' => 'school_email',
                'help_text' => 'Magic link portal pendaftaran dikirim ke email ini. Mematikan field ini mematikan pengiriman otomatisnya.',
            ],
            [
                'field_key' => 'logo_sekolah',
                'label' => 'Logo Sekolah',
                'type' => 'image',
                'is_required' => false,
                'builtin_source' => 'logo_sekolah',
                'max_kb' => 3072,
            ],
            [
                'field_key' => 'surat_tugas',
                'label' => 'Surat Tugas',
                'type' => 'file',
                'is_required' => $suratTugasWajib,
                'is_active' => true,
                'builtin_source' => 'surat_tugas',
                'max_kb' => 5120,
            ],
            [
                'field_key' => 'bukti_pendaftaran',
                'label' => 'Kwitansi Pendaftaran',
                'type' => 'file',
                'is_required' => $kwitansiWajib,
                'is_active' => true,
                'builtin_source' => 'bukti_pendaftaran',
                'max_kb' => 5120,
            ],
            // Foto pelatih, danton, dan daftar anggota pasukan: nilainya sudah
            // punya tempat sendiri (kolom registrations / tabel participants),
            // jadi baris ini hanya memasok label, tanda wajib, dan tampil atau
            // tidaknya. Mesin baca/tulisnya tidak berubah — itu yang menjaga
            // view lama (drawing, scoring, champion) tetap hidup.
            [
                'field_key' => 'foto_pelatih',
                'label' => 'Foto Resmi Pelatih',
                'type' => 'image',
                'section' => 'pelatih',
                'is_required' => false,
                'builtin_source' => 'foto_pelatih',
                'max_kb' => 3072,
            ],
            [
                'field_key' => 'danton_nama',
                'label' => 'Nama Danton',
                'type' => 'text',
                'section' => 'danton',
                'is_required' => true,
                'builtin_source' => 'danton_nama',
            ],
            [
                'field_key' => 'danton_nisn',
                'label' => 'NISN Danton',
                'type' => 'text',
                'section' => 'danton',
                'is_required' => false,
                'builtin_source' => 'danton_nisn',
            ],
            [
                'field_key' => 'danton_foto',
                'label' => 'Pas Foto Danton',
                'type' => 'image',
                'section' => 'danton',
                'is_required' => false,
                'builtin_source' => 'danton_foto',
                'max_kb' => 3072,
            ],
            [
                'field_key' => 'peserta',
                'label' => 'Anggota Pasukan',
                'type' => 'group',
                'section' => 'umum',
                'is_required' => false,
                // Bukan nama kolom — penanda mesin simpan yang sudah ada
                // (tabel participants). Baris group selalu dilewati loop
                // skalar; lihat isGroup().
                'builtin_source' => 'peserta',
                'sub_fields' => [
                    'min_rows' => 1,
                    'default_rows' => 12,
                    'items' => [
                        ['key' => 'nama', 'label' => 'Nama Lengkap', 'type' => 'text', 'is_required' => true],
                        ['key' => 'nisn', 'label' => 'NISN', 'type' => 'text', 'is_required' => false],
                        ['key' => 'foto', 'label' => 'Pas Foto Anggota', 'type' => 'image', 'is_required' => false, 'max_kb' => 3072],
                    ],
                ],
            ],
        ];
    }

    /**
     * Jumlah baris kosong yang disiapkan untuk satu field group.
     */
    public function defaultRows(): int
    {
        return (int) ($this->sub_fields['default_rows'] ?? 12);
    }

    /**
     * Sub-field milik field group, key => definisi.
     */
    public function subField(string $key): ?array
    {
        return collect($this->sub_fields['items'] ?? [])->firstWhere('key', $key);
    }

    /**
     * Pastikan event punya field bawaan. Idempoten, dan TIDAK menimpa
     * perubahan panitia (label, wajib, aktif) pada baris yang sudah ada.
     *
     * Dipanggil otomatis saat event dibuat (hook created di Eventner), jadi
     * event baru selalu punya definisi lengkap sebelum formulir, PDF, atau
     * sertifikatnya dibuka. Builder, form publik, dan portal juga memanggilnya
     * sebagai jaring kedua untuk event lama.
     */
    public static function ensureDefaults(Eventner $eventner): void
    {
        $urutan = (int) static::where('eventner_id', $eventner->id)->max('sort_order');

        foreach (static::defaults($eventner) as $i => $definisi) {
            static::firstOrCreate(
                [
                    'eventner_id' => $eventner->id,
                    'field_key' => $definisi['field_key'],
                ],
                [
                    'label' => $definisi['label'],
                    'type' => $definisi['type'],
                    'wilayah_level' => $definisi['wilayah_level'] ?? null,
                    'section' => $definisi['section'] ?? 'umum',
                    'options' => $definisi['options'] ?? null,
                    'sub_fields' => $definisi['sub_fields'] ?? null,
                    'default_value' => $definisi['default_value'] ?? null,
                    'is_required' => $definisi['is_required'] ?? false,
                    'is_active' => $definisi['is_active'] ?? true,
                    // Hanya field yang nilainya ditulis ke kolom nyata di tabel
                    // registrations yang terkunci. Field seperti "Nama Pembina"
                    // dan "Asal Kabupaten" tidak punya kolom — panitia bebas
                    // menghapusnya.
                    'is_builtin' => $definisi['builtin_source'] !== null,
                    'builtin_source' => $definisi['builtin_source'],
                    'max_kb' => $definisi['max_kb'] ?? null,
                    'help_text' => $definisi['help_text'] ?? null,
                    'sort_order' => $urutan + $i + 1,
                ]
            );
        }
    }

    /**
     * `builtin_source` yang masih punya kolom toggle lama di `eventners`
     * (`surat_tugas_required`, `kwitansi_required`). Kolom itu dibaca halaman
     * pengaturan profil event; sekarang hanya cermin dari baris builder ini.
     */
    public const LEGACY_TOGGLE_SOURCES = ['surat_tugas', 'bukti_pendaftaran'];

    /**
     * Cerminkan "berkas ini wajib" ke kolom toggle lama di `eventners`.
     *
     * Kolom lama hanya bilang wajib, bukan tampil — jadi nilainya gabungan
     * keduanya: berkas wajib diunggah hanya kalau diminta (`is_required`) DAN
     * masih dipakai (`is_active`). Bila kolomnya hilang di suatu saat, tidak
     * ada yang rusak.
     *
     * Ditulis senyap: `updateQuietly` mencegah hook `Eventner::updated` yang
     * menjaga arah sebaliknya ikut jalan dan menimpa `is_required` baris ini
     * dengan nilai gabungan tadi — panitia yang mematikan lalu menyalakan
     * kembali field-nya tidak boleh kehilangan tanda wajibnya.
     */
    public function sinkronToggleLama(): void
    {
        if (! in_array($this->builtin_source, self::LEGACY_TOGGLE_SOURCES, true)) {
            return;
        }

        $this->eventner?->updateQuietly([
            $this->builtin_source === 'surat_tugas' ? 'surat_tugas_required' : 'kwitansi_required'
                => $this->is_required && $this->is_active,
        ]);
    }

    /**
     * Arah sebaliknya: kolom toggle lama di `eventners` masih disunting
     * langsung oleh halaman pengaturan profil event (dan data lama), jadi
     * baris builder-nya harus ikut menyesuaikan — bukan sebaliknya.
     *
     * `is_active` sengaja tidak disentuh — kolom lama hanya bilang wajib, dan
     * memaksakan "tidak wajib" jadi "tidak tampil" membuat panitia kehilangan
     * cara mengunggah berkasnya (lihat catatan di `defaults()`).
     */
    public static function sinkronDariToggleLama(Eventner $eventner, string $source, bool $wajib): void
    {
        if (! in_array($source, self::LEGACY_TOGGLE_SOURCES, true)) {
            return;
        }

        $field = static::bySource($eventner, $source);

        if (! $field || $field->is_required === $wajib) {
            return;
        }

        $field->update(['is_required' => $wajib]);
    }

    /**
     * Aturan validasi untuk satu field, dipakai form publik & portal magic link.
     *
     * @return array{rule: string, attribute: string}|null
     */
    public function validationRule(bool $wajib): string
    {
        $aturan = [$wajib ? 'required' : 'nullable'];

        $aturan[] = match ($this->type) {
            'number' => 'numeric',
            'date' => 'date',
            'select' => ($this->options ?: []) === []
                ? 'string'
                : 'in:' . implode(',', array_column($this->options, 'value')),
            'textarea' => 'string|max:5000',
            // Tiga tingkat digabung jadi satu string ("32 - JAWA BARAT /
            // 32.01 - KAB. BOGOR"), jadi batas 255 milik teks biasa tidak cukup.
            'wilayah' => 'string|max:600',
            default => 'string|max:255',
        };

        return implode('|', $aturan);
    }

    /**
     * Kedalaman dropdown untuk field wilayah.
     *
     * `auto` membaca `tingkat_perlombaan` event; tingkat lain dipatok panitia
     * lewat builder. Dipakai pemanggil supaya pemetaan tingkat → kedalaman
     * tidak ditulis ulang di view.
     */
    public function kedalamanWilayah(): int
    {
        return match ($this->wilayah_level) {
            'provinsi' => WilayahService::PROVINSI,
            'kabupaten' => WilayahService::KABUPATEN,
            'kecamatan' => WilayahService::KECAMATAN,
            default => WilayahService::levelUntukTingkat($this->eventner?->tingkat_perlombaan),
        };
    }
}
