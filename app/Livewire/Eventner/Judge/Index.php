<?php

namespace App\Livewire\Eventner\Judge;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\CompetitionGroup;
use App\Models\Judge;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public $isEditMode = false;
    public $editingId = null;

    public $selectedJudgeId = null;
    public $selectedJudgePdfLevel = '';

    /** Juri yang modal akses tablet-nya sedang terbuka. */
    public $selectedTabletJudgeId = null;

    /** Juri yang modal rincian penugasan grupbnya sedang terbuka. */
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
        // mengelompokkan tingkat lomba (full_name butuh induk).
        return Judge::with([
            'assessmentCategories.competitionCategory.parent',
            'assessmentCategories.competitionSeries',
            'assessmentCategories.competitionRound',
        ])
            ->where('eventner_id', $this->eventnerId)
            ->latest()
            ->get();
    }

    /**
     * Baris penugasan yang dipegang tiap juri, dari competition_group_judge.
     *
     * Sumbernya sama dengan yang dibaca tablet juri, jadi modal rincian tak
     * bisa lagi menampilkan tugas yang berbeda dari yang benar-benar dinilai.
     * Satu baris = satu label: nama grup, atau "Final"/"Belum Bergrup"/
     * "Seluruh Tingkat" untuk tiga baris tanpa grup.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection>
     */
    #[Computed]
    public function assignmentsByJudge()
    {
        return DB::table('competition_group_judge as cgj')
            ->join('judges as j', 'j.id', '=', 'cgj.judge_id')
            ->leftJoin('competition_groups as cg', 'cg.id', '=', 'cgj.competition_group_id')
            ->leftJoin('competition_categories as cc', 'cc.id', '=', 'cgj.competition_category_id')
            ->leftJoin('competition_categories as induk', 'induk.id', '=', 'cc.parent_id')
            ->where('j.eventner_id', $this->eventnerId)
            ->orderBy('cgj.competition_category_id')
            ->orderBy('cgj.scope')
            ->orderBy('cgj.competition_group_id')
            ->get([
                'cgj.judge_id',
                'cgj.scope',
                'cgj.competition_category_id',
                'cg.name as group_name',
                'cc.name as level_name',
                'induk.name as parent_name',
            ])
            ->groupBy('judge_id')
            ->map(fn ($baris) => $baris
                ->groupBy('competition_category_id')
                ->map(fn ($perTingkat) => [
                    'name' => trim(
                        ($perTingkat->first()->parent_name ? $perTingkat->first()->parent_name . ' — ' : '')
                        . $perTingkat->first()->level_name
                    ),
                    'items' => $perTingkat->map(fn ($b) => $b->scope === CompetitionGroup::SCOPE_GROUP
                        ? $b->group_name
                        : CompetitionGroup::SCOPE_LABELS[$b->scope])->values(),
                ])
                ->values());
    }

    /**
     * Tingkat lomba yang ditugaskan ke juri terpilih, untuk filter PDF per juri.
     */
    #[Computed]
    public function judgePdfLevels()
    {
        if (!$this->selectedJudgeId) {
            return collect();
        }

        $idTingkat = DB::table('competition_group_judge as cgj')
            ->where('cgj.judge_id', $this->selectedJudgeId)
            ->pluck('cgj.competition_category_id')
            ->filter()
            ->unique()
            ->all();

        if ($idTingkat === []) {
            return collect();
        }

        return \App\Models\CompetitionCategory::with('parent')
            ->whereIn('id', $idTingkat)
            ->get()
            ->map(fn ($cc) => [
                'id' => $cc->id,
                'full_name' => $cc->full_name,
            ])
            ->unique('id')
            ->sortBy('full_name')
            ->values();
    }

    /** Juri yang modal rincian penugasannya terbuka. */
    #[Computed]
    public function categoriesJudge()
    {
        if (!$this->selectedCategoriesJudgeId) {
            return null;
        }

        return $this->judges->firstWhere('id', $this->selectedCategoriesJudgeId);
    }

    /** Penugasan juri terpilih, dikelompokkan per tingkat lomba. */
    #[Computed]
    public function categoriesJudgeGrouped()
    {
        if (!$this->selectedCategoriesJudgeId) {
            return collect();
        }

        return $this->assignmentsByJudge->get($this->selectedCategoriesJudgeId, collect());
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
            session()->flash('success', 'Data juri berhasil diperbarui.');
        } else {
            Judge::create([
                'eventner_id' => $this->eventnerId,
                'name' => strip_tags($this->name),
                'phone_number' => strip_tags($this->phone_number),
                'photo' => $photoPath,
            ]);
            session()->flash('success', 'Juri baru berhasil ditambahkan. Tugaskan grupbnya di halaman Tingkat Lomba.');
        }

        $this->resetForm();
    }

    /**
     * Siapkan form mode tambah (reset semua) lalu buka modal lewat JS.
     */
    public function openCreate()
    {
        $this->reset(['name', 'phone_number', 'photo', 'currentPhotoPath', 'isEditMode', 'editingId']);
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
        $this->reset(['name', 'phone_number', 'photo', 'currentPhotoPath', 'isEditMode', 'editingId']);
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
