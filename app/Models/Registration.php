<?php

namespace App\Models;

use App\Services\WilayahService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Registration extends Model
{
    use HasApiTokens, HasFactory, LogsActivity;

    protected $fillable = [
        'eventner_id',
        'competition_category_id',
        'competition_group_id',
        'label_pasukan',
        'nama_sekolah',
        'npsn',
        'nama_pelatih',
        'no_hp',
        'school_email',
        'foto_pelatih',
        'magic_token',
        'password',
        'logo_sekolah',
        'surat_tugas',
        'danton_nama',
        'danton_nisn',
        'danton_foto',
        'status_berkas',
        'bukti_pendaftaran',
        'is_finalized',
        'urutan_tampil',
        'total_fee',
        'payment_status',
        'payment_proof',
        'payment_bank_account_id',
        'payment_verified_at',
        'payment_verified_by',
    ];

    protected function casts(): array
    {
        return [
            'total_fee' => 'decimal:2',
            'payment_verified_at' => 'datetime',
        ];
    }

    /**
     * Nama tampil: sekolah + label pasukan (A/B/C) jika ada.
     *
     * Sengaja membaca kolom langsung, bukan lewat getFieldValue(): properti ini
     * ada di $appends sehingga ikut dipanggil setiap serialisasi (termasuk
     * daftar API) — jalur field builder akan memaksa query definisi per baris.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->label_pasukan
            ? $this->nama_sekolah . ' — Pasukan ' . $this->label_pasukan
            : $this->nama_sekolah;
    }

    protected $hidden = ['password'];

    protected $appends = ['display_name'];

    /** Cache per-instance untuk definisi field & nilainya — bukan kolom DB. */
    protected ?array $fieldValuesCache = null;

    protected ?Collection $fieldDefinitionsCache = null;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->magic_token) {
                $model->magic_token = \Illuminate\Support\Str::random(16);
            }
            if (!$model->status_berkas) {
                $model->status_berkas = 'booking';
            }
            if (!$model->qr_token) {
                $model->qr_token = strtoupper(\Illuminate\Support\Str::random(8));
            }
        });
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'npsn', 'npsn');
    }

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function competitionCategory()
    {
        return $this->belongsTo(CompetitionCategory::class);
    }

    /** Grup penilaian peserta ini (pool internal yang dibagi panitia). */
    public function competitionGroup()
    {
        return $this->belongsTo(CompetitionGroup::class, 'competition_group_id');
    }

    /** Baris kelolosan peserta ini ke sebuah babak. */
    public function roundRegistrations()
    {
        return $this->hasMany(CompetitionRoundRegistration::class);
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function voteTransactions()
    {
        return $this->hasMany(VoteTransaction::class);
    }

    public function scoreDeductions()
    {
        return $this->hasMany(ScoreDeduction::class);
    }

    public function paymentBankAccount()
    {
        return $this->belongsTo(EventnerBankAccount::class, 'payment_bank_account_id');
    }

    public function paymentVerifiedBy()
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class, 'registration_id');
    }

    /**
     * Jawaban field buatan panitia (registration_field_values).
     *
     * Field bawaan menyimpan nilainya di kolom tabel ini, bukan di sini — lihat
     * RegistrationField::defaults().
     */
    public function fieldValues()
    {
        return $this->hasMany(RegistrationFieldValue::class);
    }

    /**
     * Semua jawaban field, dipetakan `field_key` => nilai.
     *
     * Satu pintu baca untuk field builder: field bawaan diambil dari kolom
     * nyata lewat builtin_source, field baru dari registration_field_values.
     * Di-cache per instance supaya view yang memanggil berkali-kali (ceklist,
     * PDF, sertifikat) tidak menembak query berulang.
     */
    public function fieldValuesMap(): array
    {
        if ($this->fieldValuesCache !== null) {
            return $this->fieldValuesCache;
        }

        $nilai = [];

        foreach ($this->fieldDefinitions() as $field) {
            // Field group (daftar anggota) tidak punya nilai tunggal —
            // nilainya hidup di tabel participants.
            if ($field->isGroup()) {
                continue;
            }

            $nilai[$field->field_key] = $field->builtin_source
                ? ($this->{$field->builtin_source} ?? null)
                : null;
        }

        foreach ($this->fieldValues as $value) {
            $field = $value->field;

            if (! $field) {
                continue;
            }

            $nilai[$field->field_key] = $value->value;
        }

        // Field yang definisinya sudah hilang tapi nilainya masih ada (mis.
        // field nonaktif pada registrasi lama) tetap bisa dibaca lewat key-nya.
        return $this->fieldValuesCache = array_filter($nilai, fn ($v) => $v !== null && $v !== '');
    }

    /** Definisi field milik event registrasi ini (termasuk yang nonaktif). */
    protected function fieldDefinitions(): Collection
    {
        return $this->fieldDefinitionsCache ??= RegistrationField::where('eventner_id', $this->eventner_id)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Definisi field event ini untuk view (PDF/print) — satu query per baris
     * registrasi, bukan per pemanggilan.
     */
    public function fieldDefinitionsForEvent(): Collection
    {
        return $this->fieldDefinitions();
    }

    /**
     * Label builder untuk satu `builtin_source`; null bila barisnya tidak ada
     * atau dinonaktifkan panitia (pemanggil memakai itu sebagai sinyal).
     */
    public function fieldLabelFor(string $source): ?string
    {
        $field = $this->fieldDefinitions()->firstWhere('builtin_source', $source);

        return $field && $field->is_active ? $field->label : null;
    }

    /**
     * Nilai satu field berdasarkan `field_key`, null bila kosong.
     *
     * Fallback terakhir: kolom bernama sama di tabel registrations — supaya
     * pemanggil lama (mis. `label_pasukan`) tetap dapat nilai walau event-nya
     * belum punya definisi field.
     */
    public function getFieldValue(string $fieldKey): ?string
    {
        $map = $this->fieldValuesMap();

        if (array_key_exists($fieldKey, $map)) {
            return $map[$fieldKey];
        }

        $langsung = $this->getAttribute($fieldKey);

        return $langsung !== null && $langsung !== '' ? (string) $langsung : null;
    }

    /**
     * Field yang aktif + nilainya, urut sesuai urutan formulir — satu-satunya
     * yang dipanggil view untuk menampilkan data dinamis.
     *
     * @return \Illuminate\Support\Collection<int, array{key: string, label: string, value: ?string, url: ?string, type: string, is_file: bool, is_required: bool}>
     */
    public function fieldValuesForDisplay(?Collection $fields = null): \Illuminate\Support\Collection
    {
        $fields ??= $this->fieldDefinitions()->where('is_active', true);

        return $fields
            ->filter(fn ($field) => $field->is_active && ! $field->isGroup())
            ->map(function ($field) {
                $nilai = $field->builtin_source
                    ? ($this->{$field->builtin_source} ?? null)
                    : ($this->fieldValues->firstWhere('registration_field_id', $field->id)?->value ?? null);

                return [
                    'key' => $field->field_key,
                    'label' => $field->label,
                    // Field wilayah hanya menampilkan namanya di sini — kode BPS
                    // tetap tersimpan utuh dan tetap dikirim lewat field_key-nya
                    // sendiri kalau pemanggil butuh. Lihat WilayahService::nama().
                    'value' => $nilai !== null && $nilai !== ''
                        ? ($field->type === 'wilayah' ? WilayahService::nama((string) $nilai) : (string) $nilai)
                        : null,
                    'url' => ($field->isFile() && $nilai) ? asset('storage/' . ltrim($nilai, '/')) : null,
                    'type' => $field->type,
                    'is_file' => $field->isFile(),
                    'is_required' => (bool) $field->is_required,
                ];
            })
            ->values();
    }

    public function isUnpaid(): bool
    {
        return $this->payment_status === 'unpaid';
    }

    public function isPaymentPending(): bool
    {
        return $this->payment_status === 'pending_verification';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isFree(): bool
    {
        return $this->payment_status === 'free';
    }

    public function isBooking(): bool
    {
        return $this->status_berkas === 'booking';
    }

    public function isConfirmed(): bool
    {
        return $this->status_berkas === 'confirmed';
    }

    public function isVerified(): bool
    {
        return $this->status_berkas === 'Terverifikasi';
    }

    /**
     * Resolve a certificate text field value for this registration.
     */
    public function resolveCertificateField(string $fieldKey, array $context = []): string
    {
        $eventner = $context['eventner'] ?? $this->eventner;
        $winner = $context['winner'] ?? null;
        $championCategory = $context['championCategory'] ?? null;
        $competitionCategory = $context['competitionCategory'] ?? null;
        $participant = $context['participant'] ?? null;

        // Kategori lomba format "parent - child": parent = jenis lomba
        // (LOBB / RUKIBRA / VARMUS), child = tingkat (contoh: "LOBB - U13 - SD / MI")
        $cat = $competitionCategory ?? $this->competitionCategory;
        $catFull = $cat
            ? (($cat->parent_id && $cat->parent) ? $cat->parent->name . ' - ' . $cat->name : $cat->name)
            : '';

        // Gelar juara: pakai title dari rank title; fallback "Juara {rank}"
        // bila rank title tidak meng-cover peringkat tsb.
        $gelar = $winner['title'] ?? '';
        if ($gelar === '' || $gelar === null) {
            $gelar = isset($winner['rank']) ? 'Juara ' . $winner['rank'] : '';
        }

        return match ($fieldKey) {
            'nama_sekolah'  => $this->nama_sekolah,
            'gelar_juara'   => $gelar,
            'gelar_juara_lengkap' => trim($gelar . ($catFull ? ' ' . $catFull : '')),
            'kategori_juara' => $championCategory?->name ?? '',
            'kategori_lomba' => $catFull,
            'nama_event'    => $eventner?->nama_event ?? '',
            'tanggal'       => $eventner?->tanggal
                ? \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d F Y')
                : '',
            'venue'         => $eventner?->venue ?? '',
            'nama_pelatih'  => $this->nama_pelatih ?? '',
            'nama_peserta'  => $participant?->nama ?? $this->participants->pluck('nama')->join(', '),
            'peringkat'     => (string) ($winner['rank'] ?? ''),
            'total_skor'    => isset($winner['total']) ? number_format((float) $winner['total'], 0, ',', '.') : '',
            'diselenggarakan_oleh' => $eventner?->diselenggarakan_oleh ?? '',
            // Field buatan panitia (field builder) — mis. asal_kabupaten atau
            // nama_pembina — dipakai sebagai placeholder sertifikat.
            //
            // WilayahService::nama() membuang kode BPS dari field wilayah supaya
            // sertifikat mencetak "JAWA BARAT, KAB. BOGOR", bukan
            // "32 - JAWA BARAT / 32.01 - KAB. BOGOR". Nilai yang bukan hasil
            // gabung kode dilewatkan apa adanya, jadi cabang ini tetap aman
            // untuk semua field buatan panitia yang lain.
            default         => WilayahService::nama($this->getFieldValue($fieldKey)),
        };
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama_sekolah', 'status_berkas', 'is_finalized'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Pendaftaran {$this->nama_sekolah} telah di-{$eventName}");
    }
}
