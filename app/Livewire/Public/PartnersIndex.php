<?php

namespace App\Livewire\Public;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Registration;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
#[Title('Mitra Sekolah - BARIS APP')]
class PartnersIndex extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Registration::query()
            ->select(
                'npsn',
                DB::raw('MAX(nama_sekolah) as nama_sekolah'),
                DB::raw('MAX(logo_sekolah) as logo_sekolah'),
                DB::raw('COUNT(*) as total_registrations'),
                DB::raw('COUNT(DISTINCT eventner_id) as total_events'),
                DB::raw('SUM(CASE WHEN status_berkas = \'Terverifikasi\' THEN 1 ELSE 0 END) as verified_count'),
            )
            ->groupBy('npsn');

        if ($this->search) {
            $query->where('nama_sekolah', 'like', '%' . $this->search . '%');
        }

        $schools = $query->orderBy('nama_sekolah')->paginate(16);

        // Load participant counts per school
        $npsns = $schools->pluck('npsn')->toArray();

        if (!empty($npsns)) {
            $participantCounts = DB::table('registrations')
                ->join('participants', 'registrations.id', '=', 'participants.registration_id')
                ->whereIn('registrations.npsn', $npsns)
                ->select('registrations.npsn', DB::raw('COUNT(participants.id) as total_participants'))
                ->groupBy('registrations.npsn')
                ->pluck('total_participants', 'npsn');

            $eventNames = Registration::with('eventner')
                ->whereIn('npsn', $npsns)
                ->get()
                ->groupBy('npsn')
                ->map(fn($regs) => $regs->pluck('eventner.nama_event')->filter()->unique()->values());

            $schools->each(function ($school) use ($participantCounts, $eventNames) {
                $school->total_participants = $participantCounts[$school->npsn] ?? 0;
                $school->events = $eventNames[$school->npsn] ?? collect();
                // Strip suffix pasukan untuk tampilan
                $school->display_name = preg_replace('/\s*\([A-Z]+\)$/', '', $school->nama_sekolah);
            });
        }

        return view('livewire.public.partners-index', [
            'schools' => $schools,
        ])
            ->layout('layouts.frontend', ['eventner' => null])
            ->title('Mitra Sekolah - ' . get_setting('site_title', 'BARIS APP'));
    }
}
