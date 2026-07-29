<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $primaryKey = 'npsn';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'npsn',
        'nama_sekolah',
        'logo_sekolah',
        'no_hp',
        'school_email',
    ];

    public function registrations()
    {
        return $this->hasMany(Registration::class, 'npsn', 'npsn');
    }
}
