<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Pengajuan event dari formulir /ajukan di subdomain EO.
 *
 * Status: `baru` → `dibalas` → (opsional) `selesai` / `batal`. Nilainya
 * sengaja string bebas, bukan enum DB: menambah satu status di tengah jalan
 * tidak perlu migrasi, dan yang membatasi perpindahannya adalah panel admin.
 */
class EoInquiry extends Model
{
    use HasFactory;

    protected $table = 'eo_inquiries';

    protected $fillable = [
        'instansi',
        'penanggung_jawab',
        'whatsapp',
        'email',
        'jenis_lomba',
        'tanggal',
        'lokasi',
        'jumlah_peserta',
        'pekerjaan',
        'catatan',
        'status',
        'catatan_admin',
        'dibalas_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'pekerjaan' => 'array',
        'jumlah_peserta' => 'integer',
        'dibalas_at' => 'datetime',
    ];

    public const STATUS = [
        'baru' => 'Baru',
        'dibalas' => 'Sudah dibalas',
        'selesai' => 'Jadi dikerjakan',
        'batal' => 'Tidak jadi',
    ];

    public function scopeBaru($query)
    {
        return $query->where('status', 'baru');
    }
}
