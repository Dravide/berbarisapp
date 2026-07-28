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
     */
    public static function availableFields(): array
    {
        return [
            'nama_sekolah'         => 'Nama Sekolah',
            'gelar_juara'          => 'Gelar Juara',
            'kategori_juara'       => 'Kategori Juara',
            'kategori_lomba'       => 'Kategori Lomba',
            'nama_event'           => 'Nama Event',
            'tanggal'              => 'Tanggal',
            'venue'                => 'Venue / Lokasi',
            'nama_pelatih'         => 'Nama Pelatih',
            'nama_peserta'         => 'Nama Peserta',
            'diselenggarakan_oleh' => 'Diselenggarakan Oleh',
        ];
    }
}
