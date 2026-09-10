<?php

namespace App\Livewire\Eventner\Certificate;

use App\Models\CertificateTemplate;
use App\Models\CertificateTextField;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\Registration;
use App\Services\ChampionCalculator;
use App\Traits\FeatureGatedComponent;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.admin')]
class Editor extends Component
{
    use FeatureGatedComponent;

    protected string $requiredFeature = 'certificate';

    public $eventner;
    public $templateId;
    public $template;
    public $textFields = [];

    // Field management
    public $selectedFieldId = null;
    public $newFieldKey = '';

    // Download & preview
    public $previewChampionCategoryId = null;
    public $previewCompetitionCategoryId = null;
    public $previewSchool = null;
    public $previewMode = 'participant';
    public $showPreview = false;

    // Field properties panel
    public $editingField = [
        'id' => null,
        'field_key' => '',
        'label' => '',
        'x' => 0,
        'y' => 0,
        'font_size' => 18,
        'font_color' => '#000000',
        'text_align' => 'center',
        'font_weight' => 'normal',
        'max_width' => null,
    ];

    public function mount($template)
    {
        $this->bootFeatureGate();
        $this->eventner = Auth::user()->eventner;

        if (!$this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        $this->templateId = $template;
        $this->loadTemplate();
    }

    public function loadTemplate()
    {
        $tpl = CertificateTemplate::where('eventner_id', $this->eventner->id)
            ->with('textFields')
            ->findOrFail($this->templateId);

        $this->template = [
            'id' => $tpl->id,
            'name' => $tpl->name,
            'width' => $tpl->width,
            'height' => $tpl->height,
            'image_url' => $tpl->file_path ? Storage::url($tpl->file_path) : null,
            'file_path' => $tpl->file_path,
        ];

        $this->textFields = $tpl->textFields->map(function ($f) {
            return [
                'id' => $f->id,
                'field_key' => $f->field_key,
                'label' => $f->label,
                'x' => $f->x,
                'y' => $f->y,
                'font_size' => $f->font_size,
                'font_color' => $f->font_color,
                'text_align' => $f->text_align,
                'font_weight' => $f->font_weight,
                'max_width' => $f->max_width,
            ];
        })->toArray();
    }

    // ── Text Field ─────────────────────────────────────────────────────

    public function addTextField()
    {
        $this->validate(['newFieldKey' => 'required|string']);

        $availableFields = CertificateTemplate::availableFields();
        $key = $this->newFieldKey;
        $label = $availableFields[$key] ?? $key;

        $defaultX = round($this->template['width'] / 2, 1);
        $defaultY = round($this->template['height'] / 2, 1);

        $field = CertificateTextField::create([
            'certificate_template_id' => $this->templateId,
            'field_key' => $key,
            'label' => $label,
            'x' => $defaultX,
            'y' => $defaultY,
            'font_size' => 18,
            'font_color' => '#000000',
            'text_align' => 'center',
            'font_weight' => 'normal',
            'max_width' => null,
        ]);

        $this->newFieldKey = '';
        $this->loadTemplate();
        $this->selectField($field->id);
        $this->dispatch('canvas-reload');
    }

    public function selectField($id)
    {
        $field = CertificateTextField::where('certificate_template_id', $this->templateId)->findOrFail($id);
        $this->selectedFieldId = $id;
        $this->editingField = [
            'id' => $field->id,
            'field_key' => $field->field_key,
            'label' => $field->label,
            'x' => $field->x,
            'y' => $field->y,
            'font_size' => $field->font_size,
            'font_color' => $field->font_color,
            'text_align' => $field->text_align,
            'font_weight' => $field->font_weight,
            'max_width' => $field->max_width,
        ];
    }

    #[On('field-moved')]
    public function fieldMoved($id, $x, $y)
    {
        CertificateTextField::where('certificate_template_id', $this->templateId)
            ->where('id', $id)
            ->update(['x' => round((float) $x, 1), 'y' => round((float) $y, 1)]);

        $this->loadTemplate();
        $this->selectField((int) $id);
        $this->dispatch('canvas-reload', fields: $this->textFields);
    }

    #[On('field-selected-from-canvas')]
    public function handleFieldSelected($id)
    {
        $this->selectField((int) $id);
    }

    public function updatedEditingField($value, $key)
    {
        if (!$this->selectedFieldId) return;

        $id = $this->selectedFieldId;
        $updatable = ['font_size', 'font_color', 'text_align', 'font_weight', 'max_width', 'x', 'y'];

        if (in_array($key, $updatable)) {
            $updateVal = match ($key) {
                'max_width' => ($value ?: null),
                'x', 'y' => round((float) $value, 1),
                default => $value,
            };
            // Scope ke template milik eventner sendiri — cegah update field tenant lain.
            CertificateTextField::where('certificate_template_id', $this->templateId)
                ->where('id', $id)
                ->update([$key => $updateVal]);

            $idx = collect($this->textFields)->search(fn($f) => $f['id'] == $id);
            if ($idx !== false) {
                $this->textFields[$idx][$key] = $updateVal;
            }

            if (in_array($key, ['x', 'y'])) {
                $this->dispatch('canvas-reload');
            }
        }
    }

    public function deleteField($id)
    {
        CertificateTextField::where('certificate_template_id', $this->templateId)
            ->where('id', $id)
            ->delete();

        if ($this->selectedFieldId == $id) {
            $this->selectedFieldId = null;
            $this->reset('editingField');
        }

        $this->loadTemplate();
        $this->dispatch('canvas-reload');
        session()->flash('success', 'Field teks berhasil dihapus.');
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    public function getAvailableFieldKeysProperty()
    {
        return CertificateTemplate::availableFields();
    }

    public function getUsedFieldKeysProperty()
    {
        return collect($this->textFields)->pluck('field_key')->toArray();
    }

    // ── Download & Preview ──────────────────────────────────────────────

    /**
     * Contoh nilai tiap field untuk tampilan canvas —
     * data event asli bila ada, sisanya contoh generik.
     */
    public function getSampleValuesProperty(): array
    {
        $ev = $this->eventner;

        return [
            'nama_sekolah'         => 'SD Negeri 1 Contoh',
            'nama_peserta'         => 'Andi Pratama',
            'gelar_juara'          => 'Juara 1',
            'gelar_juara_lengkap'  => 'Juara 1 LOBB - U13 - SD / MI',
            'peringkat'            => '1',
            'kategori_juara'       => 'JUARA LOBB',
            'kategori_lomba'       => 'LOBB - U13 - SD / MI',
            'nama_event'           => $ev?->nama_event ?: 'Nama Event Contoh',
            'tanggal'              => $ev?->tanggal
                ? \Carbon\Carbon::parse($ev->tanggal)->translatedFormat('d F Y')
                : '15 Agustus 2026',
            'venue'                => $ev?->venue ?: 'Lapangan Contoh',
            'nama_pelatih'         => 'Budi Santoso, S.Pd.',
            'total_skor'           => '2.850',
            'diselenggarakan_oleh' => $ev?->diselenggarakan_oleh ?: 'Diselenggarakan Oleh Contoh',
        ];
    }

    public function getChampionCategoriesProperty()
    {
        if (!$this->eventner) return collect();
        return ChampionCategory::where('eventner_id', $this->eventner->id)
            ->orderBy('name')
            ->get();
    }

    public function getCompetitionCategoriesProperty()
    {
        if (!$this->eventner) return collect();
        return $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->with('parent:id,name')
            ->orderBy('name')
            ->get();
    }

    /**
     * Kategori lomba yang relevan dengan kategori juara terpilih —
     * lewat rubrik: rubrik global (tanpa tingkat) atau rubrik milik
     * tingkat tsb. Kalau kategori juara tak punya rubrik → semua.
     */
    public function getFilteredCompetitionCategoriesProperty()
    {
        $all = $this->competitionCategories;
        if (!$this->previewChampionCategoryId) return $all;

        $championCategory = $this->championCategories->firstWhere('id', $this->previewChampionCategoryId);
        if (!$championCategory) return $all;

        $championCategory->loadMissing('assessmentSubCategories.category');

        $subs = $championCategory->assessmentSubCategories;
        if ($subs->isEmpty()) return $all;

        // Kumpulkan competition_category_id dari rubrik; null = global
        $catIds = $subs->map(fn($sub) => $sub->category?->competition_category_id)
            ->filter(fn($id) => !is_null($id))
            ->unique()
            ->values();

        if ($catIds->isEmpty()) return $all;

        return $all->whereIn('id', $catIds->all())->values();
    }

    public function updatedPreviewChampionCategoryId($value)
    {
        // Ganti kategori juara → reset kategori lomba; kalau cuma
        // 1 tingkat relevan, langsung terpilih.
        $this->previewCompetitionCategoryId = null;
        $this->previewSchool = null;
        $filtered = $this->filteredCompetitionCategories;
        if ($filtered->count() === 1) {
            $this->previewCompetitionCategoryId = $filtered->first()->id;
        }
    }

    public function updatedPreviewCompetitionCategoryId($value)
    {
        // Ganti kategori lomba → daftar sekolah berubah, reset pilihan.
        $this->previewSchool = null;
    }

    /**
     * Sekolah terdaftar pada kategori lomba terpilih — opsi filter sekolah.
     */
    public function getSchoolOptionsProperty()
    {
        if (!$this->eventner || !$this->previewCompetitionCategoryId) return collect();

        return Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->previewCompetitionCategoryId)
            ->orderBy('nama_sekolah')
            ->get()
            ->map(fn($reg) => [
                'key' => (string) ($reg->npsn ?: mb_strtolower(trim((string) $reg->nama_sekolah))),
                'label' => $reg->nama_sekolah,
            ])
            ->unique('key')
            ->values();
    }

    public function togglePreview()
    {
        $this->showPreview = !$this->showPreview;
    }

    /**
     * Data preview sertifikat halaman pertama dengan juara asli.
     * Pakai ChampionCalculator (logika sama dengan download PDF).
     */
    public function getPreviewDataProperty()
    {
        if (!$this->showPreview
            || !$this->previewChampionCategoryId
            || !$this->previewCompetitionCategoryId) {
            return null;
        }

        $championCategory = ChampionCategory::where('eventner_id', $this->eventner->id)
            ->with(['assessmentSubCategories.criterias', 'rankTitles', 'tiebreakSubCategories.criterias'])
            ->find($this->previewChampionCategoryId);
        $competitionCategory = CompetitionCategory::with('parent')->find($this->previewCompetitionCategoryId);

        if (!$championCategory) {
            return null;
        }

        [$eventner, $category, $winners] = app(ChampionCalculator::class)->winners($championCategory, $this->previewCompetitionCategoryId);

        if (empty($winners)) {
            return ['error' => 'Belum ada data juara untuk kategori ini.'];
        }

        // Filter sekolah: pemenang pertama milik sekolah terpilih.
        if ($this->previewSchool !== null && $this->previewSchool !== '') {
            $winner = collect($winners)->first(function ($w) {
                $reg = $w['registration'];
                $key = $reg->npsn ?: mb_strtolower(trim((string) $reg->nama_sekolah));
                return $key === (string) $this->previewSchool;
            });
            if (!$winner) {
                return ['error' => 'Belum ada juara dari sekolah terpilih.'];
            }
        } else {
            $winner = $winners[0];
        }

        // Gelar: sama seperti CertificateController (tambah nomor posisi dalam
        // grup; fallback "Juara {rank}" bila rank title tidak meng-cover)
        $title = null;
        foreach ($category->rankTitles as $rt) {
            if ($rt->coversRank($winner['rank'])) {
                $title = $rt->rank_start !== $rt->rank_end
                    ? $rt->title . ' ' . ($winner['rank'] - $rt->rank_start + 1)
                    : $rt->title;
                break;
            }
        }
        if (!$title) {
            $title = 'Juara ' . $winner['rank'];
        }

        $pages = [];
        $sampleParticipant = $this->previewMode === 'school'
            ? null
            : ($winner['registration']->participants->first() ?? null);
        $pages[] = [
            'registration' => $winner['registration'],
            'participant' => $sampleParticipant,
            'rank' => $winner['rank'],
            'title' => $title,
            'total' => $winner['total'],
        ];

        // QR menuju link event
        $eventQrDataUri = null;
        if (collect($this->textFields)->contains('field_key', 'qr_event')) {
            $options = new QROptions;
            $options->outputInterface = QRGdImagePNG::class;
            $options->outputBase64 = false;
            $options->eccLevel = 'H';
            $png = (new QRCode($options))->render($this->eventner->publicUrl('detail'));
            $eventQrDataUri = 'data:image/png;base64,' . base64_encode($png);
        }

        return [
            'pages' => $pages,
            'championCategory' => $category,
            'competitionCategory' => $competitionCategory,
            'eventQrDataUri' => $eventQrDataUri,
            'winnerCount' => count($winners),
        ];
    }

    public function render()
    {
        return view('livewire.eventner.certificate.editor', [
            'availableFieldKeys' => $this->availableFieldKeys,
            'usedFieldKeys' => $this->usedFieldKeys,
            'championCategories' => $this->championCategories,
            'competitionCategories' => $this->filteredCompetitionCategories,
            'schoolOptions' => $this->schoolOptions,
            'previewData' => $this->previewData,
            'sampleValues' => $this->sampleValues,
        ])->title('Edit Layout: ' . ($this->template['name'] ?? '') . ' - ' . $this->eventner->nama_event);
    }
}
