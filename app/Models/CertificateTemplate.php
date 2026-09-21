<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'eventner_id',
        'name',
        'file_path',
        'width',
        'height',
        'is_active',
        'show_besign',
        'besign_text',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_besign' => 'boolean',
        'width' => 'float',
        'height' => 'float',
    ];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function textFields()
    {
        return $this->hasMany(CertificateTextField::class);
    }

    /**
     * All available text field keys that can be placed on a certificate.
     * Maps field_key => display label.
     *
     * Versi instance menambahkan field builder milik event ini (mis. "Asal
     * Kabupaten") supaya panitia bisa memakai isian sendiri sebagai placeholder
     * sertifikat tanpa perlu ubah kode.
     */
    public function availableFieldsForEvent(): array
    {
        $dasar = static::availableFields();

        $eventner = $this->eventner;

        if (! $eventner) {
            return $dasar;
        }

        foreach (RegistrationField::forEventner($eventner) as $field) {
            // Grup anggota tidak punya nilai tunggal, berkas tidak bisa jadi
            // teks sertifikat. Selain itu baris builder selalu menang — label
            // hasil suntingan panitia yang dipakai, termasuk untuk field bawaan
            // seperti nama_sekolah / nama_pelatih.
            if ($field->isFile() || $field->isGroup()) {
                continue;
            }

            $dasar[$field->field_key] = $field->label;
        }

        return $dasar;
    }

    /**
     * All available text field keys that can be placed on a certificate.
     * Maps field_key => display label.
     */
    public static function availableFields(): array
    {
        return [
            'nama_sekolah'         => 'Nama Sekolah',
            'nama_peserta'         => 'Nama Peserta',
            'gelar_juara'          => 'Gelar Juara (otomatis sesuai peringkat)',
            'gelar_juara_lengkap'  => 'Gelar Juara Lengkap (contoh: Juara 1 LOBB - U13 - SD / MI)',
            'peringkat'            => 'Peringkat (angka)',
            'kategori_juara'       => 'Kategori Juara',
            'kategori_lomba'       => 'Kategori Lomba (jenis lomba - tingkat)',
            'nama_event'           => 'Nama Event',
            'tanggal'              => 'Tanggal',
            'venue'                => 'Venue / Lokasi',
            'nama_pelatih'         => 'Nama Pelatih',
            'total_skor'           => 'Total Skor',
            'diselenggarakan_oleh' => 'Diselenggarakan Oleh',
            'qr_event'             => 'QR Code (menuju link event — font size = ukuran mm)',
        ];
    }
}
