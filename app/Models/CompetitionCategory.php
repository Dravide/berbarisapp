<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitionCategory extends Model
{
    use HasFactory;

    protected $fillable = ['eventner_id', 'parent_id', 'venue_id', 'name', 'tanggal_pelaksanaan', 'kuota', 'max_registrations_per_school', 'registration_fee', 'sort_order'];

    protected function casts(): array
    {
        return [
            'registration_fee' => 'decimal:2',
        ];
    }

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function venue()
    {
        return $this->belongsTo(EventnerVenue::class, 'venue_id');
    }

    /**
     * Juri yang menilai tingkat ini, dari competition_group_judge — pivot yang
     * sama dengan layar penugasan di halaman detail, yang satu-satunya jalur
     * tulisnya. Relasi lama membaca competition_category_judge yang sudah tak
     * pernah ditulis lagi sejak penugasan pindah per grup, sehingga daftar
     * selalu menampilkan "belum ada juri" meski centangannya lengkap.
     *
     * distinct(): satu juri sering menilai dua grup sekaligus — dua baris
     * pivot, satu model.
     */
    public function judges()
    {
        return $this->belongsToMany(Judge::class, 'competition_group_judge')->distinct();
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function assessmentCategories()
    {
        return $this->hasMany(AssessmentCategory::class, 'competition_category_id');
    }

    /** Pool penilaian di dalam tingkat ini ("Grup A" / "Grup B"). */
    public function groups()
    {
        return $this->hasMany(CompetitionGroup::class, 'competition_category_id')->orderBy('sort_order');
    }

    /** Babak penilaian di dalam tingkat ini (penyisihan, final, ...). */
    public function rounds()
    {
        return $this->hasMany(CompetitionRound::class, 'competition_category_id')->orderBy('sort_order');
    }

    /**
     * Seri urutan perlombaan di dalam tingkat ini ("Seri A" / "Seri B").
     *
     * Lepas dari groups(): seri yang menentukan lembar nilai, grup yang
     * menentukan tabel peringkat. Lihat CompetitionSeries.
     */
    public function series()
    {
        return $this->hasMany(CompetitionSeries::class, 'competition_category_id')->orderBy('sort_order');
    }

    /**
     * Gelombang harga biaya pendaftaran tingkat ini, terurut tanggal.
     *
     * Urut `starts_on` bukan sekadar tampilan: ia juga yang membuat
     * gelombangAktif() deterministik bila ada dua gelombang yang tumpang
     * tindih karena ditulis langsung ke DB (layar pengaturan menolaknya,
     * tapi seeder/impor bisa melewatinya).
     */
    public function gelombang()
    {
        return $this->hasMany(CompetitionFeeWave::class, 'competition_category_id')
            ->orderBy('starts_on');
    }

    /**
     * Gelombang yang mencakup $tanggal; null bila di luar semuanya.
     *
     * Ini SATU-SATUNYA penentu gelombang mana yang berlaku hari ini. Panggil
     * dengan tanggal eksplisit di tes supaya hasilnya tidak bergantung jam
     * mesin yang menjalankan.
     */
    public function gelombangAktif(?\DateTimeInterface $tanggal = null): ?CompetitionFeeWave
    {
        $kapan = ($tanggal ? \Carbon\Carbon::parse($tanggal) : now())->toDateString();

        // Membaca relasi yang SUDAH DIMUAT, bukan membangun query baru:
        // `$this->gelombang()` menembak SQL tiap panggilan, jadi eager load
        // tidak menolong dan tiap kartu publik menembak dua query tambahan.
        // Urutannya tetap deterministik — relasinya `orderBy('starts_on')`.
        return $this->gelombang
            ->first(fn (CompetitionFeeWave $g) => $g->starts_on->toDateString() <= $kapan
                && $g->ends_on->toDateString() >= $kapan);
    }

    /**
     * Biaya pendaftaran yang berlaku pada $tanggal — angka yang ditagih.
     *
     * Gelombang menang atas harga biasa; di luar semua gelombang, harga biasa
     * yang dipakai (dan boleh null = gratis). Semua tempat yang menagih atau
     * menampilkan biaya WAJIB lewat sini, tidak boleh membaca `registration_fee`
     * mentah: kalau penagih memakai kolom dan layar memakai method ini,
     * pendaftar melihat angka yang berbeda dari yang ditagih.
     */
    public function biayaPada(?\DateTimeInterface $tanggal = null): ?float
    {
        $gelombang = $this->gelombangAktif($tanggal);

        if ($gelombang) {
            return (float) $gelombang->amount;
        }

        return $this->registration_fee !== null ? (float) $this->registration_fee : null;
    }

    /**
     * Angka termurah yang mungkin dibayar tingkat ini — untuk label "mulai
     * dari" di kartu publik. Membandingkan gelombang termurah dengan harga
     * biasa, jadi tingkat bergelombang lebih MAHAL pun tidak menipu pembaca
     * dengan angka yang tak pernah bisa didapat.
     *
     * null = tingkat ini memang tidak berbiaya sama sekali.
     */
    public function biayaTerendah(): ?float
    {
        $kandidat = $this->gelombang->pluck('amount')
            ->map(fn ($angka) => (float) $angka);

        if ($this->registration_fee !== null) {
            $kandidat->push((float) $this->registration_fee);
        }

        return $kandidat->isEmpty() ? null : (float) $kandidat->min();
    }

    /**
     * Ada biaya sama sekali? Pengganti semua `@if($category->registration_fee)`.
     * Tingkat tanpa harga biasa tapi punya gelombang tetap berbiaya.
     */
    public function punyaBiaya(): bool
    {
        return $this->biayaTerendah() !== null;
    }

    /**
     * Kenapa rentang ini ditolak — null berarti sah.
     *
     * Bentrok dicek inklusif dua arah; gelombang yang hanya BERSENTUHAN
     * (berakhir 31 Okt, berikutnya mulai 1 Nov) tidak dianggap bentrok.
     * Pesannya menyebut nama gelombang yang bertabrakan supaya panitia tahu
     * mana yang harus digeser, bukan sekadar "tanggal tidak valid".
     *
     * @param  int|null  $kecualiId  gelombang yang sedang diedit — dirinya
     *                               sendiri tidak boleh dihitung bentrok.
     */
    public function alasanGelombangBentrok(string $mulai, string $selesai, ?int $kecualiId = null): ?string
    {
        $bentrok = $this->gelombang
            ->when($kecualiId, fn ($daftar) => $daftar->where('id', '!=', $kecualiId))
            ->first(fn (CompetitionFeeWave $gelombang) => $gelombang->starts_on->toDateString() <= $selesai
                && $gelombang->ends_on->toDateString() >= $mulai);

        if (! $bentrok) {
            return null;
        }

        return 'Rentang ini bertabrakan dengan "' . $bentrok->name . '" ('
            . $bentrok->labelRentang() . '). Geser tanggalnya supaya tidak tumpang tindih.';
    }

    public function remainingSlots(): int
    {
        if (!$this->kuota) {
            return PHP_INT_MAX; // unlimited
        }
        return max(0, $this->kuota - $this->registrations()->count());
    }

    public function getFullNameAttribute(): string
    {
        if ($this->parent_id && $this->parent) {
            return $this->parent->name . ' — ' . $this->name;
        }
        return $this->name;
    }

    public function isParent(): bool
    {
        return is_null($this->parent_id);
    }

    public function isChild(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * Kategori yang boleh dipilih pendaftar: tingkat (anak), atau induk lama
     * tanpa anak dari data flat sebelum hierarki diperkenalkan.
     *
     * Induk yang PUNYA anak sengaja disaring. Menyaring di daftar formulir saja
     * tidak cukup: id kategori datang dari DOM, jadi id induk bisa tetap dikirim
     * dan membuat pendaftaran mendarat di tingkat induk — bukan di tingkat
     * lomba. Scope ini jadi satu-satunya definisi "boleh dipilih", dipakai
     * render (tampil), toggleCategory (masuk keranjang), dan submit (dibuat).
     */
    public function scopeSelectable($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('parent_id');

            $q->orWhere(function ($sq) {
                $sq->whereNull('parent_id')->whereDoesntHave('children');
            });
        });
    }
}
