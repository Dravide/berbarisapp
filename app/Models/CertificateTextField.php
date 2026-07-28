<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateTextField extends Model
{
    use HasFactory;

    protected $fillable = [
        'certificate_template_id',
        'field_key',
        'label',
        'x',
        'y',
        'font_size',
        'font_color',
        'text_align',
        'font_weight',
        'max_width',
    ];

    protected $casts = [
        'x' => 'float',
        'y' => 'float',
        'font_size' => 'integer',
        'max_width' => 'float',
    ];

    public function certificateTemplate()
    {
        return $this->belongsTo(CertificateTemplate::class);
    }
}
