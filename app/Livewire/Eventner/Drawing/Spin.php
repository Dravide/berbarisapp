<?php

namespace App\Livewire\Eventner\Drawing;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Registration;

use App\Models\Eventner;

#[Layout('layouts.frontend')]
#[Title('Pengundian Urutan Tampil - BARIS APP')]
class Spin extends Component
{
    public $slug;
    public $eventnerId;
    public $activeTab = '';
    public $categories = [];
    public $currentSchool = null;
    public $spinResult = null;
    public $isSpinning = false;
    public $isAuthenticated = false;
    public $inputCode = '';
    public $allDrawn = false;

    public function mount($slug)
    {
        $this->slug = $slug;
        $eventner = Eventner::where('slug', $slug)->firstOrFail();
        $this->eventnerId = $eventner->id;

        if (!$eventner->drawing_code) {
            $this->isAuthenticated = true;
        }

        if ($eventner) {
            $this->categories = $eventner->competitionCategories()->get()->toArray();
        }

        if (count($this->categories) > 0) {
            $this->activeTab = $this->categories[0]['id'];
        }

        $this->loadNextSchool();
    }

    public function switchTab($categoryId)
    {
        $this->activeTab = $categoryId;
        $this->spinResult = null;
        $this->isSpinning = false;
        $this->loadNextSchool();
    }

    public function loadNextSchool()
    {
        $this->currentSchool = Registration::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->activeTab)
            ->whereNull('urutan_tampil')
            ->inRandomOrder()
            ->first();

        $this->allDrawn = is_null($this->currentSchool);
        $this->spinResult = null;
    }

    public function spin()
    {
        if (!$this->currentSchool || $this->isSpinning) return;

        // Hitung nomor urut yang belum terpakai
        $usedNumbers = Registration::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->activeTab)
            ->whereNotNull('urutan_tampil')
            ->pluck('urutan_tampil')
            ->toArray();

        $category = \App\Models\CompetitionCategory::find($this->activeTab);
        $totalInCategory = $category->kuota ?? Registration::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->activeTab)
            ->count();

        $availableNumbers = array_diff(range(1, max(1, $totalInCategory)), $usedNumbers);
        
        if (empty($availableNumbers)) return;

        // Pilih secara acak dari yang tersedia
        $this->spinResult = collect($availableNumbers)->random();
    }

    public function saveResult()
    {
        if (!$this->currentSchool || !$this->spinResult) return;

        $this->currentSchool->update([
            'urutan_tampil' => $this->spinResult,
        ]);

        session()->flash('success', $this->currentSchool->nama_sekolah . ' mendapat urutan tampil #' . $this->spinResult);
        
        $this->loadNextSchool();
    }

    public function resetDrawing()
    {
        Registration::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->activeTab)
            ->update(['urutan_tampil' => null]);

        session()->flash('success', 'Semua hasil undian pada kategori ini telah di-reset.');
        $this->loadNextSchool();
    }

    public function verifyCode()
    {
        $eventner = Eventner::findOrFail($this->eventnerId);
        if (!$eventner->drawing_code || $this->inputCode === $eventner->drawing_code) {
            $this->isAuthenticated = true;
        } else {
            $this->addError('inputCode', 'Kode akses salah!');
        }
    }

    public function render()
    {
        $eventner = Eventner::findOrFail($this->eventnerId);

        $drawnSchools = Registration::where('eventner_id', $eventner->id)
                ->where('competition_category_id', $this->activeTab)
                ->whereNotNull('urutan_tampil')
                ->orderBy('urutan_tampil')
                ->get();

        $category = \App\Models\CompetitionCategory::find($this->activeTab);
        $totalSchools = $category->kuota ?? Registration::where('eventner_id', $eventner->id)
                ->where('competition_category_id', $this->activeTab)
                ->count();

        return view('livewire.eventner.drawing.spin', [
            'drawnSchools' => $drawnSchools,
            'totalSchools' => $totalSchools,
            'eventner' => $eventner,
        ]);
    }
}
