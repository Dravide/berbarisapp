<?php

namespace Database\Factories;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    public function definition(): array
    {
        return [
            'eventner_id' => Eventner::factory(),
            'competition_category_id' => CompetitionCategory::factory(),
            'nama_sekolah' => fake()->company() . ' ' . fake()->randomElement(['SMA', 'SMK', 'MA']),
            'npsn' => (string) fake()->unique()->numberBetween(10000000, 99999999),
            'nama_pelatih' => fake()->name(),
            'no_hp' => '08' . fake()->numerify('##########'),
            'school_email' => fake()->safeEmail(),
            'status_berkas' => 'confirmed',
            'payment_status' => 'free',
            // magic_token kolom varchar(32) — gunakan random 16 char (konsisten dgn Registration::boot)
            'magic_token' => \Illuminate\Support\Str::random(16),
        ];
    }

    /**
     * Registration with a squad label (A/B/C) — distinguishes multiple teams from one school.
     */
    public function pasukan(string $label = 'A'): static
    {
        return $this->state(fn (array $attrs) => [
            'label_pasukan' => $label,
        ]);
    }

    /**
     * Registration with participants.
     */
    public function withParticipants(int $count = 1): static
    {
        return $this->has(Participant::factory()->count($count), 'participants');
    }

    /**
     * Booking status (not yet confirmed).
     */
    public function booking(): static
    {
        return $this->state(fn (array $attrs) => [
            'status_berkas' => 'booking',
        ]);
    }

    /**
     * Cancelled registration.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attrs) => [
            'status_berkas' => 'dibatalkan',
        ]);
    }

    /**
     * Paid registration (for paid events).
     */
    public function paid(): static
    {
        return $this->state(fn (array $attrs) => [
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Sudah menyetor uang muka, sisa tagihan belum dilunasi.
     *
     * Sengaja TIDAK membuat baris `registration_payments`: yang diuji biasanya
     * hanya tampilan/rekap, dan baris pembayaran sungguhan dibuat lewat
     * PaymentRecorder di tes yang memang menguji ledger. `amount_paid` diisi
     * karena tanpa angka itu `jumlahDibayar()` jatuh ke 0 dan DP-nya tidak
     * terlihat sama sekali.
     */
    public function dpPaid(float $dibayar = 150000): static
    {
        return $this->state(fn (array $attrs) => [
            'payment_status' => 'dp_paid',
            'amount_paid' => $dibayar,
            'dp_deadline' => now()->addDays(14),
        ]);
    }
}
