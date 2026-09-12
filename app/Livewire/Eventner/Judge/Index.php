<?php

namespace App\Livewire\Eventner\Judge;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Judge;
use App\Models\AssessmentCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithFileUploads;

    public $name = '';
    public $phone_number = '';
    public $photo;
    public $currentPhotoPath = null;
    public $selectedCategories = [];

    public $isEditMode = false;
    public $editingId = null;

    public $selectedJudgeId = null;
    public $selectedJudgePdfLevel = '';

    /** Juri yang modal akses tablet-nya sedang terbuka. */
    public $selectedTabletJudgeId = null;

    /** Juri yang modal rincian tugas/kategori penilaiannya sedang terbuka. */
    public $selectedCategoriesJudgeId = null;

    protected $eventnerId;

    public function boot()
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403);
        }
        $this->eventnerId = $eventner->id;
    }

    #[Computed]
    public function judges()
    {
        // competitionCategory.parent ikut dimuat: modal rincian tugas
        // mengelompokkan kategori per tingkat lomba (full_name butuh induk).
        return Judge::with('assessmentCategories.competitionCategory.parent')
            ->where('eventner_id', $this->eventnerId)
            ->latest()
            ->get();
    }

    #[Computed]
    public function availableCategories()
    {
        return AssessmentCategory::with('competitionCategory.parent')
            ->where('eventner_id', $this->eventnerId)
            ->get();
    }

    /**
     * Tingkat lomba yang ditugaskan ke juri terpilih (via kategori penilaiannya),
     * untuk filter PDF per juri.
     */
    #[Computed]
    public function judgePdfLevels()
    {
        if (!$this->selectedJudgeId) {
            return collect();
        }

        $judge = $this->judges->firstWhere('id', $this->selectedJudgeId);
        if (!$judge) {
            return collect();
        }

        return $judge->assessmentCategories
            ->map(fn($cat) => $cat->competitionCategory)
            ->filter()
            ->map(fn($cc) => [
                'id' => $cc->id,
                'full_name' => $cc->full_name,
            ])
            ->unique('id')
            ->sortBy('full_name')
            ->values();
    }

    /**
     * Kategori dikelompokkan per tingkat/kelas kompetisi (competition_category),
     * contoh parent "LOBB" dengan child "U13", "U16". Kunci grup = id
     * competition_category (child), label = full_name ("LOBB — U13") sehingga
     * U13 dan U16 tampil terpisah meski induknya sama. Kategori yang menunjuk
     * langsung ke induk (parent, tanpa child) tetap jadi grup sendiri.
     *
     * Dipakai bersama oleh modal rincian tugas juri dan form tambah/edit.
     */
    private function groupByCompetitionLevel($categories)
    {
        $grouped = [];
        foreach ($categories as $cat) {
            $cc = $cat->competitionCategory;
            if (!$cc) {
                $grouped['Lainnya'] = [
                    'name' => 'Lainnya',
                    'items' => ($grouped['Lainnya']['items'] ?? []) + [$cat->id => $cat],
                ];
                continue;
            }
            // grup per competition_category itu sendiri (child jika ada, atau induk)
            $grouped[$cc->id] = [
                'name' => $cc->full_name,
                'items' => ($grouped[$cc->id]['items'] ?? []) + [$cat->id => $cat],
            ];
        }

        return collect($grouped)->sortBy('name');
    }

    #[Computed]
    public function availableCategoriesGrouped()
    {
        return $this->groupByCompetitionLevel($this->availableCategories);
    }

    /** Juri yang modal rincian tugasnya terbuka. */
    #[Computed]
    public function categoriesJudge()
    {
        if (!$this->selectedCategoriesJudgeId) {
            return null;
        }

        return $this->judges->firstWhere('id', $this->selectedCategoriesJudgeId);
    }

    /**
     * Tugas juri terpilih, dikelompokkan per tingkat lomba supaya urutan
     * kategorinya terbaca: tingkat dulu, baru kategori penilaiannya.
     */
    #[Computed]
    public function categoriesJudgeGrouped()
    {
        $judge = $this->categoriesJudge;

        if (!$judge) {
            return collect();
        }

        return $this->groupByCompetitionLevel($judge->assessmentCategories);
    }

    public function openCategoriesModal($id)
    {
        $this->selectedCategoriesJudgeId = $id;
    }

    public function closeCategoriesModal()
    {
        $this->selectedCategoriesJudgeId = null;
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'selectedCategories' => 'array',
            'selectedCategories.*' => 'exists:assessment_categories,id',
        ];

        if ($this->photo) {
            $rules['photo'] = 'image|max:2048';
        }

        $this->validate($rules);

        $photoPath = $this->currentPhotoPath;

        if ($this->photo) {
            if ($this->currentPhotoPath) {
                Storage::delete('public/' . $this->currentPhotoPath);
            }
            $photoPath = $this->photo->store('judges', 'public');
        }

        if ($this->isEditMode && $this->editingId) {
            $judge = Judge::where('eventner_id', $this->eventnerId)->findOrFail($this->editingId);
            $judge->update([
                'name' => strip_tags($this->name),
                'phone_number' => strip_tags($this->phone_number),
                'photo' => $photoPath,
            ]);
            $judge->assessmentCategories()->sync($this->selectedCategories);
            session()->flash('success', 'Data juri berhasil diperbarui.');
        } else {
            $judge = Judge::create([
                'eventner_id' => $this->eventnerId,
                'name' => strip_tags($this->name),
                'phone_number' => strip_tags($this->phone_number),
                'photo' => $photoPath,
            ]);
            $judge->assessmentCategories()->attach($this->selectedCategories);
            session()->flash('success', 'Juri baru berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    /**
     * Siapkan form mode tambah (reset semua) lalu buka modal lewat JS.
     */
    public function openCreate()
    {
        $this->reset(['name', 'phone_number', 'photo', 'currentPhotoPath', 'selectedCategories', 'isEditMode', 'editingId']);
        $this->dispatch('open-judge-modal');
    }

    public function selectJudgeForPdf($id)
    {
        $this->selectedJudgeId = $id;
        $this->selectedJudgePdfLevel = '';
    }

    public function edit($id)
    {
        $judge = Judge::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $this->isEditMode = true;
        $this->editingId = $judge->id;
        $this->name = $judge->name;
        $this->phone_number = $judge->phone_number ?? '';
        $this->currentPhotoPath = $judge->photo;
        $this->selectedCategories = $judge->assessmentCategories->pluck('id')->toArray();

        // Kalau edit dipicu dari modal rincian tugas, tutup dulu modal itu.
        $this->selectedCategoriesJudgeId = null;

        // Buka modal setelah re-render selesai (dispatch diproses pasca-morph)
        $this->dispatch('open-judge-modal');
    }

    /**
     * QR + link tablet juri. QR dirender sebagai data-URI (tanpa file di storage),
     * sama seperti cetak QR peserta.
     */
    #[Computed]
    public function tabletQr()
    {
        if (!$this->selectedTabletJudgeId) {
            return null;
        }

        $judge = $this->judges->firstWhere('id', $this->selectedTabletJudgeId);
        if (!$judge) {
            return null;
        }

        // Selalu host entry milik platform (config app.entry_host) — bukan host
        // request — supaya QR tetap sah walau dibuat dari subdomain event.
        $url = judge_entry_url($judge->access_token);

        try {
            // v6: outputInterface, bukan kunci 'outputType' di array — kunci
            // itu diabaikan diam-diam dan QR keluar sebagai SVG.
            $options = new \chillerlan\QRCode\QROptions([
                'scale' => 8,
                'imageTransparent' => false,
            ]);
            $options->outputInterface = \chillerlan\QRCode\Output\QRGdImagePNG::class;

            return [
                'url' => $url,
                'image' => (new \chillerlan\QRCode\QRCode($options))->render($url),
                'judge' => $judge,
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Judge tablet QR render failed', [
                'judge_id' => $judge->id,
                'error' => $e->getMessage(),
            ]);

            return ['url' => $url, 'image' => null, 'judge' => $judge];
        }
    }

    public function openTabletModal($id)
    {
        $this->selectedTabletJudgeId = $id;
    }

    public function closeTabletModal()
    {
        $this->selectedTabletJudgeId = null;
    }

    /**
     * Ganti token = cabut akses tablet lama (link/QR lama jadi 404).
     */
    public function regenerateAccessToken($id)
    {
        $judge = Judge::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $judge->update(['access_token' => \Illuminate\Support\Str::random(16)]);

        $this->selectedTabletJudgeId = $judge->id;
        session()->flash('success', 'Token akses tablet juri diperbarui. Link lama tidak berlaku lagi.');
    }

    public function delete($id)
    {
        $judge = Judge::where('eventner_id', $this->eventnerId)->findOrFail($id);
        if ($judge->photo) {
            Storage::delete('public/' . $judge->photo);
        }
        $judge->delete();
        session()->flash('success', 'Juri berhasil dihapus.');
    }

    public function resetForm()
    {
        $this->reset(['name', 'phone_number', 'photo', 'currentPhotoPath', 'selectedCategories', 'isEditMode', 'editingId']);
        $this->dispatch('close-judge-modal');
    }

    /**
     * Reset state filter PDF saat juri baru dipilih di dropdown.
     */
    public function updatedSelectedJudgeId()
    {
        $this->selectedJudgePdfLevel = '';
    }

    /**
     * Reset state filter PDF saat dropdown ditutup (batal).
     */
    public function cancelPdfFilter()
    {
        $this->selectedJudgeId = null;
        $this->selectedJudgePdfLevel = '';
    }

    public function render()
    {
        return view('livewire.eventner.judge.index');
    }
}
