<?php

namespace App\Livewire\Eventner\FormatNilai;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\DeductionCategory;
use App\Models\DeductionCriteria;
use App\Support\FormatNilaiImport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Import extends Component
{
    use WithFileUploads;

    /** Target tingkat (dari Builder). '' = Global / Semua Tingkat. */
    public $activeTab = '';

    public bool $showPreview = false;

    public $file;

    /** Rows hasil parse untuk tabel preview (data ringkas). */
    public array $previewData = [];

    /** Ringkasan counts + nama target. */
    public array $previewMeta = [];

    /** Error per baris yang dilewati. */
    public array $rowErrors = [];

    /** Session key unik per upload. */
    public string $previewSessionKey = '';

    /** Nama file terakhir yang di-upload (untuk ditampilkan di modal). */
    public string $fileName = '';

    private const MAX_FILE_KB = 2048;

    public function mount(string $activeTab = '')
    {
        $this->activeTab = $activeTab;
    }

    public function setActiveTab($id)
    {
        $this->activeTab = $id;
    }

    /** Tingkat lomba (child / parent tanpa child) milik eventner. */
    protected function eventnerId()
    {
        return Auth::user()?->eventner?->id;
    }

    public function competitionCategories()
    {
        return CompetitionCategory::where('eventner_id', $this->eventnerId())
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

    public function targetName(): string
    {
        if ($this->activeTab !== '') {
            return CompetitionCategory::where('eventner_id', $this->eventnerId())
                ->find($this->activeTab)?->full_name ?? 'Tingkat (tidak ditemukan)';
        }

        return 'Semua Tingkat (Global)';
    }

    /**
     * Parse file Excel → preview (belum menyentuh DB). Hasil disimpan di session
     * agar tidak perlu re-upload saat konfirmasi.
     */
    public function uploadExcel()
    {
        $this->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls', 'max:'.self::MAX_FILE_KB],
        ], [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.extensions' => 'File harus berformat .xlsx atau .xls.',
            'file.max' => 'Ukuran file maksimal '.self::MAX_FILE_KB.' KB.',
        ]);

        $eventnerId = $this->eventnerId();
        if (! $eventnerId) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        try {
            $reader = IOFactory::createReaderForFile($this->file->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($this->file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            $spreadsheet->disconnectWorksheets();
        } catch (\Throwable $e) {
            session()->flash('import_error', 'Gagal membaca file Excel: '.$e->getMessage());

            return;
        }

        if (empty($rows)) {
            session()->flash('import_error', 'File Excel kosong.');

            return;
        }

        // Lewati baris header bila ada.
        if (FormatNilaiImport::isHeaderRow($rows[0])) {
            array_shift($rows);
        }

        $normalized = FormatNilaiImport::normalizeRows($rows);

        if (empty($normalized['rubrik']) && empty($normalized['pengurangan'])) {
            session()->flash('import_error', 'Tidak ada data Rubrik/Pengurangan valid yang ditemukan di file.');

            return;
        }

        $rubrikCount = count($normalized['rubrik']);
        $penguranganCount = count($normalized['pengurangan']);

        $this->previewData = FormatNilaiImport::previewRows($normalized);
        $this->rowErrors = $normalized['errors'];
        $this->previewMeta = [
            'targetName' => $this->targetName(),
            'rubrikCount' => $rubrikCount,
            'penguranganCount' => $penguranganCount,
            'criteriaCount' => collect($normalized['rubrik'])
                ->sum(fn ($c) => collect($c['subCategories'])->sum(fn ($s) => count($s['criterias']))),
            'deductionCriteriaCount' => collect($normalized['pengurangan'])
                ->sum(fn ($g) => count($g['criterias'])),
        ];

        $this->previewSessionKey = 'format_nilai_import_'.$eventnerId.'_'.bin2hex(random_bytes(6));
        session([$this->previewSessionKey => $normalized]);

        $this->fileName = $this->file->getClientOriginalName();
        $this->showPreview = true;

        $this->dispatch('import:preview-ready');
    }

    public function closePreview()
    {
        $this->forgetSessionPreview();
        $this->reset('showPreview', 'previewData', 'previewMeta', 'rowErrors', 'fileName');
        $this->file = null;
    }

    /**
     * Konfirmasi → tulis hasil preview ke DB dalam transaksi (perilaku merge:
     * menambah kategori baru tanpa menghapus data lama).
     */
    public function confirmImport()
    {
        $eventnerId = $this->eventnerId();
        if (! $eventnerId) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        if (! $this->previewSessionKey || ! session()->has($this->previewSessionKey)) {
            session()->flash('import_error', 'Sesi preview berakhir. Silakan upload ulang file.');

            return;
        }

        $normalized = session($this->previewSessionKey);
        $targetCompetitionCategoryId = $this->activeTab !== '' ? (int) $this->activeTab : null;

        try {
            DB::transaction(function () use ($eventnerId, $normalized, $targetCompetitionCategoryId) {
                // Mapping: indeks rubrik di file → id assessment_category yang baru dibuat.
                $rubrikMap = [];

                foreach ($normalized['rubrik'] as $rubrik) {
                    $maxOrder = AssessmentCategory::where('eventner_id', $eventnerId)->max('sort_order') ?? 0;

                    $category = AssessmentCategory::create([
                        'eventner_id' => $eventnerId,
                        'competition_category_id' => $targetCompetitionCategoryId,
                        'name' => strip_tags($rubrik['name']),
                        'sort_order' => $maxOrder + 1,
                    ]);
                    $rubrikMap[] = $category->id;

                    foreach ($rubrik['subCategories'] as $subIndex => $sub) {
                        $newSub = AssessmentSubCategory::create([
                            'assessment_category_id' => $category->id,
                            'name' => strip_tags($sub['name']),
                            'sort_order' => $subIndex + 1,
                        ]);

                        foreach ($sub['criterias'] as $critIndex => $crit) {
                            AssessmentCriteria::create([
                                'assessment_sub_category_id' => $newSub->id,
                                'name' => strip_tags($crit['name']),
                                'score_options' => $crit['score_options'],
                                'weight' => $crit['weight'] >= 0 ? $crit['weight'] : 1,
                                'sort_order' => $critIndex + 1,
                            ]);
                        }
                    }
                }

                foreach ($normalized['pengurangan'] as $dedGroup) {
                    $assessmentCategoryId = $rubrikMap[$dedGroup['rubrik_index']] ?? null;
                    if (! $assessmentCategoryId) {
                        continue;
                    }

                    $dedMaxOrder = DeductionCategory::where('eventner_id', $eventnerId)->max('sort_order') ?? 0;
                    $dedCat = DeductionCategory::create([
                        'eventner_id' => $eventnerId,
                        'assessment_category_id' => $assessmentCategoryId,
                        'name' => strip_tags($dedGroup['name']),
                        'sort_order' => $dedMaxOrder + 1,
                    ]);

                    foreach ($dedGroup['criterias'] as $critIndex => $dedCrit) {
                        DeductionCriteria::create([
                            'deduction_category_id' => $dedCat->id,
                            'name' => strip_tags($dedCrit['name']),
                            'deduction_options' => $dedCrit['deduction_options'],
                            'sort_order' => $critIndex + 1,
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            session()->flash('import_error', 'Gagal menyimpan import: '.$e->getMessage());

            return;
        }

        $rubrikCount = $this->previewMeta['rubrikCount'] ?? 0;
        $criteriaCount = $this->previewMeta['criteriaCount'] ?? 0;
        $dedGroupCount = $this->previewMeta['penguranganCount'] ?? 0;
        $dedCount = $this->previewMeta['deductionCriteriaCount'] ?? 0;

        $this->forgetSessionPreview();
        $this->reset('showPreview', 'previewData', 'previewMeta', 'rowErrors', 'fileName');
        $this->file = null;

        $this->dispatch('import:done');
        session()->flash('success', "Import berhasil: {$rubrikCount} kategori rubrik, {$criteriaCount} kriteria, {$dedGroupCount} kelompok pengurangan ({$dedCount} kriteria).");
    }

    private function forgetSessionPreview()
    {
        if ($this->previewSessionKey) {
            session()->forget($this->previewSessionKey);
            $this->previewSessionKey = '';
        }
    }

    public function render()
    {
        return view('livewire.eventner.format-nilai.import');
    }
}
