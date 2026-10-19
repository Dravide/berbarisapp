<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Dokumen yang diunggah panitia sendiri (juknis, jadwal, denah, dsb).
 *
 * Berbeda dari "Juknis" yang digenerate PublicJuknisController dari rubrik
 * penilaian: berkas di sini apa adanya dari panitia, jadi tipenya bebas
 * (PDF, Office, gambar) dan nama unduhannya memakai nama asli berkas.
 */
class EventDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'eventner_id',
        'title',
        'file',
        'original_name',
        'file_size',
        'description',
        'sort_order',
        'is_primary',
        'download_count',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'file_size' => 'integer',
        'download_count' => 'integer',
    ];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function getExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->file, PATHINFO_EXTENSION));
    }

    public function getIsImageAttribute(): bool
    {
        return in_array($this->extension, ['jpg', 'jpeg', 'png'], true);
    }

    /**
     * PDF dan gambar raster boleh disajikan inline (dibuka browser), sisanya
     * (doc/docx/xls/xlsx) hanya masuk akal sebagai lampiran.
     *
     * Sengaja daftar-putih, bukan "apa saja selain Office": berkas yang dirender
     * browser berjalan di origin yang sama dengan aplikasi, jadi tipe yang bisa
     * mengeksekusi skrip (svg, html) tidak boleh ikut.
     */
    public function getBisaDipratinjauAttribute(): bool
    {
        return in_array($this->extension, ['pdf', 'jpg', 'jpeg', 'png'], true);
    }

    /**
     * Content-Type eksplisit untuk penyajian inline.
     *
     * Tidak memakai mimeType() disk: tebakan itu bergantung pada berkas benar-
     * benar ada dan pada deteksi finfo, sedangkan header ini yang menentukan
     * apakah browser merender PDF atau mengunduhnya.
     */
    public function getMimePratinjauAttribute(): string
    {
        return match ($this->extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/'.$this->file);
    }

    /**
     * Nama asli berkas, dibersihkan dari karakter path.
     *
     * Dipakai langsung sebagai nama unduhan (Content-Disposition), dan nama itu
     * datang dari pengguna — tanpa pembersihan, "../../x.pdf" ikut tercetak.
     * Nama kosong setelah dibersihkan jatuh ke judul dokumen.
     */
    public function getNamaUnduhanAttribute(): string
    {
        $bersih = Str::of($this->original_name ?? '')
            ->replace(['/', '\\', "\0"], '-')
            ->replace(['"', "'"], '')
            ->trim()
            ->toString();

        return $bersih !== '' ? $bersih : (Str::slug($this->title).'.'.$this->extension);
    }

    public function getUkuranTerbacaAttribute(): string
    {
        $bytes = (int) $this->file_size;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024).' KB';
        }

        return $bytes.' B';
    }

    public function getIkonAttribute(): string
    {
        return match ($this->extension) {
            'pdf' => 'ti-file-type-pdf',
            'doc', 'docx' => 'ti-file-type-doc',
            'xls', 'xlsx' => 'ti-file-type-xls',
            'jpg', 'jpeg', 'png' => 'ti-photo',
            default => 'ti-file',
        };
    }
}
