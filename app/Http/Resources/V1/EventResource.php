<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_event' => $this->nama_event,
            'slug' => $this->slug,
            'scoring_code' => $this->scoring_code,
            'subdomain' => $this->subdomain,
            'deskripsi' => $this->deskripsi,
            'poster' => $this->poster ? asset('storage/' . $this->poster) : null,
            'logo_event' => $this->logo_event ? asset('storage/' . $this->logo_event) : null,
            'header_banner' => $this->header_banner ? asset('storage/' . $this->header_banner) : null,
            'venue' => $this->venue,
            'lokasi' => $this->lokasi,
            'tanggal' => $this->tanggal,
            'tanggal_akhir' => $this->tanggal_akhir,
            'vote_active' => $this->vote_active,
            'vote_start' => $this->vote_start,
            'vote_end' => $this->vote_end,
            'vote_price' => $this->vote_price,
            // Batas jumlah vote satu transaksi. Dikirim ke klien supaya
            // aplikasi bisa menahan angka sebelum menabrak 422 dari server.
            'vote_max_per_order' => $this->vote_max_per_order,
            'registration_status' => $this->registration_status,
            'ticket_active' => $this->ticket_active,
            'ticket_start' => $this->ticket_start,
            'ticket_end' => $this->ticket_end,
            'ticket_price' => $this->ticket_price,
            'ticket_max_per_order' => $this->ticket_max_per_order,
            'ticket_description' => $this->ticket_description,
            // Tanpa daftar ini aplikasi tidak bisa menampilkan pilihan tempat,
            // sementara `purchase` menolak (422) event multi-tempat yang tidak
            // mengirim venue_id. Dikirim juga saat kosong supaya klien bisa
            // membedakan "event tanpa tempat" dari "field belum ada".
            'ticket_venues' => $this->ticketVenues()->map(fn ($venue) => [
                'id' => $venue->id,
                'name' => $venue->name,
                'alamat' => $venue->alamat,
                'latitude' => $venue->latitude,
                'longitude' => $venue->longitude,
                'ticket_price' => $venue->effectiveTicketPrice((int) $this->ticket_price),
                'ticket_kuota' => $venue->ticket_kuota,
                'remaining' => $venue->remainingTicketSlots(),
                'is_sold_out' => $venue->isTicketSoldOut(),
            ]),
            'link_instagram' => $this->link_instagram,
            'link_tiktok' => $this->link_tiktok,
            'link_whatsapp' => $this->link_whatsapp,
            'link_livestreaming' => $this->link_livestreaming,
            'diselenggarakan_oleh' => $this->diselenggarakan_oleh,
            'categories' => $this->whenLoaded('competitionCategories', function () {
                return $this->competitionCategories->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->full_name,
                    'parent_id' => $c->parent_id,
                    'kuota' => $c->kuota,
                    // Kolom mentah tetap ada: kontrak API lama tidak boleh
                    // patah. Klien baru memakai `fee_saat_ini`, yang sama
                    // dengan angka yang benar-benar ditagih hari ini.
                    'registration_fee' => $c->registration_fee,
                    'fee_saat_ini' => $c->biayaPada(),
                    'fee_terendah' => $c->biayaTerendah(),
                    'fee_waves' => $c->gelombang->map(fn ($g) => [
                        'name' => $g->name,
                        'starts_on' => $g->starts_on->toDateString(),
                        'ends_on' => $g->ends_on->toDateString(),
                        'amount' => (float) $g->amount,
                    ])->values(),
                    'total_peserta' => $c->registrations_count ?? $c->registrations()->count(),
                ]);
            }),
        ];
    }
}
