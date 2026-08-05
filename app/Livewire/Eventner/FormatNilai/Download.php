<?php

namespace App\Livewire\Eventner\FormatNilai;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\AssessmentCategory;
use App\Models\CompetitionCategory;
use App\Models\Judge;
use App\Models\Registration;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.admin')]
#[Title('Unduh Format Penilaian - BARIS APP')]
class Download extends Component
{
    public $eventnerId;

    // Filter
    public $selectedJudgeId = '';
    public $selectedLevelId = '';

    // Mode: kosong | peserta | daftar
    public $mode = 'kosong';

    // Mode "peserta": satu registrasi terpilih
    public $selectedRegistrationId = '';

    public function mount()
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }
        $this->eventnerId = $eventner->id;
    }

    /** Tingkat lomba (child / parent tanpa child) milik eventner. */
    #[Computed]
    public function levels()
    {
        return CompetitionCategory::where('eventner_id', $this->eventnerId)
            ->where(function ($q) {
                $q->whereNotNull('parent_id')
                   ->orWhere(function ($sq) {
                       $sq->whereNull('parent_id')->whereDoesntHave('children');
                   });
            })
            ->with('parent')
            ->orderBy('name')
            ->get();
    }

    /** Semua juri eventner (yang punya kategori penugasan). */
    #[Computed]
    public function judges()
    {
        return Judge::with('assessmentCategories')
            ->where('eventner_id', $this->eventnerId)
            ->whereHas('assessmentCategories')
            ->orderBy('name')
            ->get();
    }

    /** Kategori penilaian berdasarkan filter juri + tingkat. */
    #[Computed]
    public function categories()
    {
        $q = AssessmentCategory::with(['subCategories.criterias', 'deductionCategories.criterias'])
            ->where('eventner_id', $this->eventnerId);

        if ($this->selectedLevelId) {
            $q->where('competition_category_id', $this->selectedLevelId);
        }

        if ($this->selectedJudgeId) {
            $q->whereHas('judges', fn($j) => $j->where('judges.id', $this->selectedJudgeId));
        }

        return $q->orderBy('sort_order')->get();
    }

    /** Peserta per tingkat terpilih, urut nomor undian. */
    #[Computed]
    public function registrations()
    {
        $q = Registration::with('competitionCategory')
            ->where('eventner_id', $this->eventnerId);

        if ($this->selectedLevelId) {
            $q->where('competition_category_id', $this->selectedLevelId);
        }

        return $q->orderByRaw('COALESCE(urutan_tampil, 999999)')
            ->orderBy('nama_sekolah')
            ->get();
    }

    /** URL unduh PDF sesuai filter & mode saat ini. */
    public function pdfUrl()
    {
        return route('eventner.format-nilai.download-pdf', [
            'judge_id'   => $this->selectedJudgeId ?: null,
            'level_id'   => $this->selectedLevelId ?: null,
            'mode'       => $this->mode,
            'registration_id' => $this->mode === 'peserta' && $this->selectedRegistrationId
                ? $this->selectedRegistrationId
                : null,
        ]);
    }

    public function render()
    {
        return view('livewire.eventner.format-nilai.download');
    }
}
