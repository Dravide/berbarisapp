<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoreDeduction extends Model
{
    protected $fillable = [
        'eventner_id',
        'registration_id',
        'deduction_criteria_id',
        'amount',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Besar pengurangan tanpa tanda.
     *
     * Operator bisa mengetik "-5" maupun "5" di Format Penilaian, dan Import
     * pun menerima angka apa pun apa adanya. Tanda yang tersimpan jadi tidak
     * bisa dipercaya, sehingga setiap pembaca harus menormalkannya sendiri —
     * dan dulu memang begitu: ada yang abs(), ada yang memakai nilai mentah,
     * sehingga satu halaman mengurangi 5 dan halaman lain menambah 5. Akses
     * ini jadi satu-satunya sumber; pemakainya mengurangi, bukan menjumlah.
     */
    public function getMagnitudeAttribute(): float
    {
        return abs((float) $this->amount);
    }

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function deductionCriteria()
    {
        return $this->belongsTo(DeductionCriteria::class);
    }
}
