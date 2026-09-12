<?php

use App\Models\Eventner;
use App\Models\Ticket;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill tiket lama ke tempatnya.
 *
 * Hanya event yang punya TEPAT SATU tempat aktif yang diisi — satu tempat
 * berarti satu gerbang, jadi penunjukannya pasti benar. Event tanpa tempat
 * atau dengan lebih dari satu tempat dibiarkan NULL, sehingga tidak ada tiket
 * lama yang tiba-tiba ditolak di gerbang.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->eachSingleVenueEvent(function (Eventner $eventner, int $venueId) {
            Ticket::where('eventner_id', $eventner->id)
                ->whereNull('venue_id')
                ->update(['venue_id' => $venueId]);
        });
    }

    /** Kebalikan persis dari up() — tidak perlu menyimpan daftar id. */
    public function down(): void
    {
        $this->eachSingleVenueEvent(function (Eventner $eventner, int $venueId) {
            Ticket::where('eventner_id', $eventner->id)
                ->where('venue_id', $venueId)
                ->update(['venue_id' => null]);
        });
    }

    private function eachSingleVenueEvent(callable $callback): void
    {
        Eventner::query()->with('venues')->chunkById(100, function ($eventners) use ($callback) {
            foreach ($eventners as $eventner) {
                $aktif = $eventner->venues->where('is_active', true);

                if ($aktif->count() !== 1) {
                    continue;
                }

                $callback($eventner, $aktif->first()->id);
            }
        });
    }
};
