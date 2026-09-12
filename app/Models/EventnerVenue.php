<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventnerVenue extends Model
{
    use HasFactory;

    protected $fillable = [
        'eventner_id',
        'name',
        'alamat',
        'latitude',
        'longitude',
        'google_maps_url',
        'is_active',
        'sort_order',
        'ticket_kuota',
        'ticket_price',
        'checkin_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'ticket_kuota' => 'integer',
            'ticket_price' => 'integer',
        ];
    }

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function competitionCategories()
    {
        return $this->hasMany(CompetitionCategory::class, 'venue_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'venue_id');
    }

    /**
     * Jumlah tiket yang menahan slot di tempat ini.
     *
     * PENDING ikut dihitung: tanpa itu, dua pembeli bisa lolos bersamaan dan
     * kuota jebol saat keduanya membayar. Konsisten dengan perhitungan omzet
     * di RevenueDashboard yang juga menganggap PENDING sebagai terpakai.
     */
    public function ticketsSoldCount(): int
    {
        return (int) $this->tickets()
            ->whereIn('status', ['PENDING', 'PAID', 'CHECKED_IN', 'ACTIVE'])
            ->sum('quantity');
    }

    /** Sisa kuota; null artinya tanpa batas (ticket_kuota belum diisi). */
    public function remainingTicketSlots(): ?int
    {
        if ($this->ticket_kuota === null) {
            return null;
        }

        return max(0, $this->ticket_kuota - $this->ticketsSoldCount());
    }

    public function isTicketSoldOut(): bool
    {
        return $this->remainingTicketSlots() === 0;
    }

    /** Harga tempat ini; kalau belum diisi, pakai harga event sebagai fallback. */
    public function effectiveTicketPrice(int $fallback): int
    {
        return $this->ticket_price ?? $fallback;
    }

    /** Link peta: pakai URL manual kalau ada, kalau tidak susun dari koordinat. */
    public function getMapsUrlAttribute(): ?string
    {
        if ($this->google_maps_url) {
            return $this->google_maps_url;
        }

        if ($this->latitude && $this->longitude) {
            return 'https://www.google.com/maps?q=' . $this->latitude . ',' . $this->longitude;
        }

        return null;
    }

    /** Satu baris siap cetak: "SMA 1 — Jl. Melati 3". */
    public function getLabelAttribute(): string
    {
        return $this->alamat ? $this->name . ' — ' . $this->alamat : $this->name;
    }
}
