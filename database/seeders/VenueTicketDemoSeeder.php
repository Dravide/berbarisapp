<?php

namespace Database\Seeders;

use App\Models\Eventner;
use App\Models\EventnerVenue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data contoh untuk mencoba tiket per tempat: SMA 1 dan SMA 2 dengan harga dan
 * kuota berbeda. Hanya untuk lingkungan pengembangan — jangan dijalankan di
 * production karena mengaktifkan penjualan tiket pada event yang sudah ada.
 */
class VenueTicketDemoSeeder extends Seeder
{
    public function run(): void
    {
        $event = Eventner::where('slug', 'lomba-pbb-2026')->first()
            ?? Eventner::approved()->orderBy('id')->first();

        if (!$event) {
            $this->command?->warn('Tidak ada event untuk diisi data contoh.');
            return;
        }

        if (app()->environment('production')) {
            $this->command?->error('Seeder ini tidak untuk production.');
            return;
        }

        $venue = [
            [
                'name' => 'SMA 1',
                'alamat' => 'Jl. Melati No. 3, Bandung',
                'sort_order' => 1,
                'ticket_price' => 50000,
                'ticket_kuota' => 200,
            ],
            [
                'name' => 'SMA 2',
                'alamat' => 'Jl. Cendana No. 17, Bandung',
                'sort_order' => 2,
                'ticket_price' => 35000,
                'ticket_kuota' => 100,
            ],
        ];

        foreach ($venue as $data) {
            EventnerVenue::updateOrCreate(
                ['eventner_id' => $event->id, 'name' => $data['name']],
                $data + [
                    'is_active' => true,
                    'checkin_token' => Str::random(40),
                ]
            );
        }

        $event->update([
            'ticket_active' => true,
            'ticket_price' => 50000,
            'ticket_max_per_order' => 10,
            'checkin_token' => $event->checkin_token ?: Str::random(40),
        ]);

        $this->command?->info("Data contoh tiket per tempat siap untuk: {$event->nama_event}");
        $this->command?->line("  Halaman tiket : " . url("/event/{$event->slug}/ticket"));
        $this->command?->line("  Scan event    : " . url("/scan/{$event->checkin_token}"));

        foreach (EventnerVenue::where('eventner_id', $event->id)->get() as $v) {
            $this->command?->line("  Gerbang {$v->name} : " . url("/scan/{$v->checkin_token}")
                . " (Rp " . number_format((int) $v->ticket_price, 0, ',', '.') . ", kuota {$v->ticket_kuota})");
        }
    }
}
