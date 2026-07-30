<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_sekolah' => $this->nama_sekolah,
            'logo_sekolah' => $this->logo_sekolah ? asset('storage/' . $this->logo_sekolah) : null,
            'total_votes' => (int) ($this->total_votes ?? 0),
            'status_berkas' => $this->status_berkas,
            'kategori' => $this->whenLoaded('competitionCategory', fn () => [
                'id' => $this->competitionCategory->id,
                'name' => $this->competitionCategory->full_name,
            ]),
            'jumlah_peserta' => $this->whenLoaded('participants', fn () => $this->participants->count()),
        ];
    }
}
