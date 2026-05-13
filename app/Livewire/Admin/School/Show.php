<?php

namespace App\Livewire\Admin\School;

use Livewire\Component;
use App\Models\Registration;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Detail Sekolah - BARIS APP')]
class Show extends Component
{
    public $npsn;
    public $schoolInfo;
    public $registrations;

    public function mount($npsn)
    {
        $this->npsn = $npsn;
        $this->loadData();
    }

    public function loadData()
    {
        $registrations = Registration::with(['eventner', 'competitionCategory', 'participants'])
            ->where('npsn', $this->npsn)
            ->orderBy('created_at', 'desc')
            ->get();

        $first = $registrations->first();
        if (!$first) {
            return redirect()->route('admin.schools.index')->with('error', 'Sekolah tidak ditemukan.');
        }

        $this->schoolInfo = [
            'npsn' => $first->npsn,
            'nama_sekolah' => $first->nama_sekolah,
            'logo_sekolah' => $first->logo_sekolah,
            'nama_pelatih' => $first->nama_pelatih,
            'no_hp' => $first->no_hp,
            'total_registrations' => $registrations->count(),
            'total_participants' => $registrations->sum(fn($r) => $r->participants->count()),
            'events' => $registrations->pluck('eventner.nama_event')->unique()->values(),
        ];

        $this->registrations = $registrations;
    }

    public function render()
    {
        return view('livewire.admin.school.show');
    }
}
