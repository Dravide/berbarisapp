<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'eventner_id' => $this->eventner_id,
            'nama_sekolah' => $this->nama_sekolah,
            'display_name' => $this->display_name,
            'npsn' => $this->npsn,
            'label_pasukan' => $this->label_pasukan,
            'nama_pelatih' => $this->nama_pelatih,
            'no_hp' => $this->no_hp,
            'school_email' => $this->school_email,
            'danton_nama' => $this->danton_nama,
            'danton_nisn' => $this->danton_nisn,
            'status_berkas' => $this->status_berkas,
            'is_finalized' => $this->is_finalized,
            'urutan_tampil' => $this->urutan_tampil,
            'payment_status' => $this->payment_status,
            'total_fee' => $this->total_fee,
            'logo_sekolah' => $this->logo_sekolah ? asset('storage/' . $this->logo_sekolah) : null,
            'foto_pelatih' => $this->foto_pelatih ? asset('storage/' . $this->foto_pelatih) : null,
            'danton_foto' => $this->danton_foto ? asset('storage/' . $this->danton_foto) : null,
            'surat_tugas' => $this->surat_tugas ? asset('storage/' . $this->surat_tugas) : null,
            'bukti_pendaftaran' => $this->bukti_pendaftaran ? asset('storage/' . $this->bukti_pendaftaran) : null,
            'payment_proof' => $this->payment_proof ? asset('storage/' . $this->payment_proof) : null,

            'event' => $this->whenLoaded('eventner', fn () => [
                'id' => $this->eventner->id,
                'nama_event' => $this->eventner->nama_event,
                'slug' => $this->eventner->slug,
                'venue' => $this->eventner->venue,
                'tanggal' => $this->eventner->tanggal,
                'logo' => $this->eventner->logo_event ? asset('storage/' . $this->eventner->logo_event) : null,
                'scoring_code' => $this->eventner->scoring_code,
            ]),

            'competition_category' => $this->whenLoaded('competitionCategory', fn () => [
                'id' => $this->competitionCategory->id,
                'name' => $this->competitionCategory->full_name,
            ]),

            'participants' => $this->whenLoaded('participants', fn () =>
                $this->participants->map(fn ($p) => [
                    'id' => $p->id,
                    'nama' => $p->nama,
                    'nisn' => $p->nisn,
                    'foto' => $p->foto ? asset('storage/' . $p->foto) : null,
                ])
            ),

            'payment_bank' => $this->whenLoaded('paymentBankAccount', fn () => [
                'bank_name' => $this->paymentBankAccount->bank_name,
                'account_number' => $this->paymentBankAccount->account_number,
                'account_name' => $this->paymentBankAccount->account_name,
            ]),

            'total_votes' => $this->when($this->relationLoaded('voteTransactions'), function () {
                return $this->voteTransactions->sum('votes_earned');
            }),
        ];
    }
}
