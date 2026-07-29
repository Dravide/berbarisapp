<?php

namespace App\Livewire\Admin\School;

use Livewire\Component;
use App\Models\School;
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
        $school = School::find($this->npsn);
        if (!$school) {
            return redirect()->route('admin.schools.index')->with('error', 'Sekolah tidak ditemukan.');
        }

        $registrations = Registration::with(['eventner', 'competitionCategory', 'participants'])
            ->where('npsn', $this->npsn)
            ->orderBy('created_at', 'desc')
            ->get();

        $this->schoolInfo = [
            'npsn' => $school->npsn,
            'nama_sekolah' => $school->nama_sekolah,
            'logo_sekolah' => $school->logo_sekolah,
            'no_hp' => $school->no_hp,
            'school_email' => $school->school_email,
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
