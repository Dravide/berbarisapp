<?php

namespace App\Models;

use App\Services\WilayahService;
use Illuminate\Database\Eloquent\Builder;
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
        'competition_series_id',
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
        'daftar_ulang_at',
        'total_fee',
        'amount_paid',
        'dp_deadline',
        'payment_status',
        'payment_proof',
        'payment_bank_account_id',
        'payment_verified_at',
        'payment_verified_by',
        'verification_method',
        'verified_at',
        'verified_by',
        'verification_notes',
    ];

    protected function casts(): array
    {
        return [
            'total_fee' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'dp_deadline' => 'datetime',
            'payment_verified_at' => 'datetime',
            'daftar_ulang_at' => 'datetime',
            'verified_at' => 'datetime',
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

    /**
     * Seri urutan perlombaan peserta ini — penentu lembar nilainya.
     *
     * Terpisah dari competitionGroup(): grup menyusun tabel peringkat dan
     * nomor undian, seri menentukan rubrik & juri mana yang dipakai. Dua
     * pasukan satu grup boleh berbeda seri.
     */
    public function competitionSeries()
    {
        return $this->belongsTo(CompetitionSeries::class, 'competition_series_id');
    }

    /** Baris kelolosan peserta ini ke sebuah babak. */
    public function roundRegistrations()
    {
        return $this->hasMany(CompetitionRoundRegistration::class);
    }

    /**
     * Nomor undian yang berlaku di babak ini. null = belum diundi di babak itu.
     *
     * Hanya babak FINAL yang menyimpan undiannya sendiri: competition_round_registrations
     * hanya berisi finalis, jadi babak penyisihan tidak punya baris di sana sama
     * sekali. Kalau pembacaan dipaksa lewat baris babak, nomor undian fase grup
     * hilang dari panel nilai dan tablet juri. Karena itu bercabang menurut
     * jenis babak, bukan sekadar null/tidak.
     *
     * Babak pembaca wajib memuatkan relasi roundRegistrations — tanpa itu
     * pemanggilan di dalam loop jadi N+1.
     */
    public function nomorUndian(?CompetitionRound $round): ?int
    {
        if ($round === null || ! $round->isFinal()) {
            return $this->urutan_tampil !== null ? (int) $this->urutan_tampil : null;
        }

        $baris = $this->roundRegistrations
            ->firstWhere('competition_round_id', $round->id);

        return $baris?->urutan_tampil !== null ? (int) $baris->urutan_tampil : null;
    }

    /**
     * Urutkan daftar mengikuti nomor undian babak ini — peserta yang belum
     * diundi (nomor null) paling bawah, lalu dirapikan per nama sekolah.
     *
     * Babak final mengambil nomornya dari competition_round_registrations;
     * babak penyisihan dan tingkat tanpa babak tetap mengurut dari
     * registrations.urutan_tampil seperti dulu.
     *
     * Sengaja lewat sub-query berkorelasi, BUKAN leftJoin: tabel itu juga punya
     * kolom eventner_id dan competition_group_id, jadi join membuat setiap
     * saringan pemanggil ("where eventner_id", "where competition_group_id")
     * jadi ambigu dan query-nya gagal. Sub-query-nya kena unique index
     * (competition_round_id, registration_id), jadi tetap satu pencarian
     * index per baris.
     *
     * Dipisah jadi scope karena tiga layar (panel nilai, tablet juri, lembar
     * PDF peserta) harus menampilkan urutan yang identik — kalau tiap layar
     * menyusun ORDER BY sendiri, cepat atau lambat salah satunya menyimpang.
     */
    public function scopeUrutNomorUndian(Builder $query, ?CompetitionRound $round): Builder
    {
        if ($round === null || ! $round->isFinal()) {
            return $query->orderByRaw('COALESCE(registrations.urutan_tampil, 999999)');
        }

        return $query->orderByRaw(
            'COALESCE((SELECT undian.urutan_tampil FROM competition_round_registrations undian'
            . ' WHERE undian.registration_id = registrations.id AND undian.competition_round_id = ?), 999999)',
            [$round->id]
        );
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

    /**
     * Penerimaan uang untuk pendaftaran ini — DP dan pelunasan, urut waktu.
     *
     * Satu-satunya sumber angka `amount_paid`; model ini tidak pernah
     * menghitung ulang sendiri (lihat App\Services\PaymentRecorder).
     */
    public function registrationPayments()
    {
        return $this->hasMany(RegistrationPayment::class)->orderBy('created_at');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isHardcopyVerified(): bool
    {
        return $this->verification_method === 'hardcopy' && $this->status_berkas === 'Terverifikasi';
    }

    public function getIsHardcopyVerifiedAttribute(): bool
    {
        return $this->isHardcopyVerified();
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class, 'registration_id');
    }

    public function judgeNotes()
    {
        return $this->hasMany(JudgeNote::class);
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

    /** Sudah menyetor uang muka tapi tagihannya belum tertutup. */
    public function isDpPaid(): bool
    {
        return $this->payment_status === 'dp_paid';
    }

    /**
     * Uang yang sudah benar-benar diterima panitia untuk pendaftaran ini.
     *
     * Sumbernya `amount_paid` — cache dari baris `registration_payments`
     * berstatus verified yang dijumlah ulang oleh App\Services\PaymentRecorder.
     *
     * Ada satu cadangan: baris yang sudah ditandai `paid` tapi belum punya
     * catatan pembayaran sama sekali. Itu pendaftaran lama dari sebelum ledger
     * ada (migrasi backfill mengisinya, tapi seeder, factory, dan baris yang
     * ditandai lunas langsung oleh panitia tidak lewat sana). Membacanya
     * sebagai nol akan mencetak "DIBAYAR SEBAGIAN" pada kwitansi yang sudah
     * lunas — dokumen yang salah untuk uang yang sudah masuk. Begitu ada satu
     * saja baris pembayaran, angka ledger yang dipakai dan cadangan ini mati.
     */
    public function jumlahDibayar(): float
    {
        $tercatat = (float) $this->amount_paid;

        if ($tercatat > 0) {
            return min($tercatat, (float) $this->total_fee);
        }

        if ($this->payment_status === 'paid') {
            return (float) $this->total_fee;
        }

        return 0.0;
    }

    /**
     * Ungkapan SQL untuk "uang yang sudah diterima" — pasangan agregat dari
     * jumlahDibayar().
     *
     * Dibutuhkan karena layar keuangan menjumlah puluhan ribu baris di dalam
     * database; memanggil accessor per baris berarti menarik semuanya ke PHP.
     * Dua ungkapan yang menghitung angka yang sama HARUS tinggal bersebelahan:
     * kalau salah satunya berubah sendiri, dashboard dan portal akan
     * menampilkan sisa tagihan yang berbeda untuk pendaftaran yang sama.
     */
    public static function sqlDibayar(): string
    {
        return "CASE WHEN amount_paid > 0 THEN amount_paid "
            . "WHEN payment_status = 'paid' THEN total_fee ELSE 0 END";
    }

    /**
     * Sisa tagihan yang belum dibayar. Nol berarti lunas.
     *
     * Selalu dihitung dari `total_fee` (snapshot harga saat booking) dikurangi
     * uang yang sudah diterima — bukan dari `payment_status`, yang hanya
     * cermin. Pendaftaran gratis menghasilkan nol.
     */
    public function sisaTagihan(): float
    {
        return max(0, (float) $this->total_fee - $this->jumlahDibayar());
    }

    /**
     * Sudah menyetor sebagian, tapi masih ada sisa.
     *
     * Inilah arti "DP" di seluruh sistem. Ia sengaja TIDAK sama dengan
     * `payment_status === 'dp_paid'`: status itu bisa tertinggal bila
     * PaymentRecorder gagal di tengah jalan, sedangkan perbandingan dua angka
     * selalu benar.
     */
    public function sudahDp(): bool
    {
        return $this->jumlahDibayar() > 0 && $this->sisaTagihan() > 0;
    }

    /**
     * Nominal DP yang harus dibayar pendaftaran ini — null bila tingkatnya
     * tidak menawarkan DP, sudah lunas, atau sudah pernah menyetor DP.
     *
     * Memakai `total_fee` milik baris ini, bukan harga kategori hari ini:
     * pendaftar yang masuk di gelombang pertama tidak boleh tiba-tiba ditagih
     * DP menurut harga gelombang terakhir.
     */
    public function targetDp(): ?float
    {
        $kategori = $this->competitionCategory;

        if (! $kategori || ! $kategori->punyaDp()) {
            return null;
        }

        $sisa = $this->sisaTagihan();

        if ($sisa <= 0) {
            return null;
        }

        // Sudah menyetor uang muka = tidak ditawari DP lagi. Tanpa penjagaan
        // ini portal menampilkan "Bayar DP dulu" untuk pasukan yang justru
        // sedang melunasi, dan setiap kiriman berikutnya tercatat sebagai DP
        // kedua dengan nominal yang sama.
        if ($this->sudahDp()) {
            return null;
        }

        return $kategori->nominalDp((float) $this->total_fee);
    }

    /**
     * Lewat tenggat pelunasan setelah DP.
     *
     * Penanda untuk panitia, BUKAN pembatal: pendaftaran yang telat melunasi
     * tidak otomatis dibatalkan, karena pembatalan menuntut kebijakan refund
     * yang belum ada. Yang dilakukan sistem hanya membuatnya terlihat.
     */
    public function lewatTenggatDp(): bool
    {
        return $this->sudahDp()
            && $this->dp_deadline !== null
            && now()->gt($this->dp_deadline);
    }

    public function isBooking(): bool
    {
        return $this->status_berkas === 'booking';
    }

    /**
     * Masa booking sudah boleh diisi — Technical Meeting lewat, biaya
     * pendaftaran lunas, atau uang muka sudah diverifikasi.
     *
     * Inilah satu-satunya aturan "sudah boleh mengisi" di portal. Dipakai
     * bersama view, komponen Livewire, dan API supaya ketiganya tidak pernah
     * berbeda pendapat — beda pendapat di sini berarti tombol tampil tapi
     * server menolak, atau sebaliknya.
     *
     * DP ikut membuka pengisian (keputusan pemilik produk): sekolah yang sudah
     * menyetor uang muka tidak perlu menunggu TM untuk melengkapi data pelatih,
     * danton, dan anggota. Yang TIDAK dibuka oleh DP adalah finalisasi —
     * lihat bolehFinalisasi().
     *
     * Perhatikan: yang dibaca status milik pendaftaran ini, bukan event. Bukti
     * transfer yang masih menunggu verifikasi belum membuka apa pun.
     */
    public function sudahBolehMengisi(): bool
    {
        if ($this->isPaid() || $this->sudahDp()) {
            return true;
        }

        $tm = $this->eventner?->technical_meeting;

        return ! ($tm && now()->lt($tm));
    }

    /**
     * Tombol "Konfirmasi & Kirim" boleh ditekan?
     *
     * Sengaja LEBIH KETAT daripada sudahBolehMengisi(): DP membuka isian, tapi
     * tidak membuka finalisasi. Panitia tidak boleh menerima berkas yang
     * biaya pendaftarannya belum tertutup — begitu berkas masuk antrean
     * verifikasi, pasukan itu sudah dihitung ikut lomba.
     *
     * Pendaftaran yang sudah lepas dari `booking` tidak pernah ditahan gerbang
     * ini; yang dijaga hanya masa booking itu sendiri.
     */
    public function bolehFinalisasi(): bool
    {
        if ($this->status_berkas !== 'booking') {
            return true;
        }

        if ($this->isPaid()) {
            return true;
        }

        $tm = $this->eventner?->technical_meeting;

        return ! ($tm && now()->lt($tm));
    }

    /**
     * Formulir isian di portal /reg/{token} ditampilkan?
     *
     * Pendaftaran yang sudah lepas dari status `booking` tidak pernah ditahan
     * gerbang masa booking — yang dijaga hanya masa booking itu sendiri.
     */
    public function formulirTerbuka(): bool
    {
        return $this->status_berkas !== 'booking' || $this->sudahBolehMengisi();
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
