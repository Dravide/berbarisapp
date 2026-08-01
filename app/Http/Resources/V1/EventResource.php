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
            'registration_status' => $this->registration_status,
            'ticket_active' => $this->ticket_active,
            'ticket_start' => $this->ticket_start,
            'ticket_end' => $this->ticket_end,
            'ticket_price' => $this->ticket_price,
            'ticket_max_per_order' => $this->ticket_max_per_order,
            'ticket_description' => $this->ticket_description,
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
                    'registration_fee' => $c->registration_fee,
                    'total_peserta' => $c->registrations_count ?? $c->registrations()->count(),
                ]);
            }),
        ];
    }
}
