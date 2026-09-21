<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Jawaban satu registrasi untuk satu field pendaftaran.
 *
 * Hanya dipakai oleh field buatan panitia (builtin_source = null). Field
 * bawaan nilainya tetap ditulis ke kolom nyata di tabel registrations supaya
 * view lama (landing, hasil, vote, sertifikat) tidak perlu diubah; baris di
 * sini untuk field bawaan hanya salinan cadangan.
 *
 * Untuk tipe file/image, `value` berisi path relatif disk 'public'
 * (mis. registrations/fields/12/abc.pdf).
 */
class RegistrationFieldValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_id',
        'registration_field_id',
        'value',
    ];

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function field()
    {
        return $this->belongsTo(RegistrationField::class, 'registration_field_id');
    }

    /**
     * URL publik untuk tipe file/image, null bila kosong.
     */
    public function getUrlAttribute(): ?string
    {
        if (! $this->value) {
            return null;
        }

        return asset('storage/' . ltrim($this->value, '/'));
    }
}
