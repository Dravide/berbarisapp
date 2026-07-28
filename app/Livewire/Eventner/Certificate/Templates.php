<?php

namespace App\Livewire\Eventner\Certificate;

use App\Models\CertificateTemplate;
use App\Models\ChampionCategory;
use App\Traits\FeatureGatedComponent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class Templates extends Component
{
    use FeatureGatedComponent;
    use WithFileUploads;

    protected string $requiredFeature = 'certificate';

    public $eventner;

    // Template management
    public $templates = [];
    public $editingTemplate = null;
    public $templateForm = [
        'name' => '',
        'width' => 297,
        'height' => 210,
        'show_besign' => true,
        'besign_text' => '',
    ];
    public $templateImage;
    public $showTemplateForm = false;
    public $presetKey = '';

    // Download
    public $downloadTemplateId = null;
    public $downloadChampionCategoryId = null;
    public $downloadCompetitionCategoryId = null;

    // Paper presets
    public $paperPresets = [
        'a4_landscape' => ['label' => 'A4 Landscape (297×210mm)', 'width' => 297, 'height' => 210],
        'a4_portrait'  => ['label' => 'A4 Portrait (210×297mm)', 'width' => 210, 'height' => 297],
        'a3_landscape' => ['label' => 'A3 Landscape (420×297mm)', 'width' => 420, 'height' => 297],
        'a3_portrait'  => ['label' => 'A3 Portrait (297×420mm)', 'width' => 297, 'height' => 420],
        'custom'       => ['label' => 'Custom', 'width' => null, 'height' => null],
    ];

    public function mount()
    {
        $this->bootFeatureGate();
        $this->eventner = Auth::user()->eventner;

        if (!$this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        $this->loadTemplates();
    }

    public function loadTemplates()
    {
        $this->templates = $this->eventner->certificateTemplates()
            ->with('textFields')
            ->orderBy('name')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'file_path' => $t->file_path,
                    'width' => $t->width,
                    'height' => $t->height,
                    'is_active' => $t->is_active,
                    'show_besign' => $t->show_besign ?? false,
                    'besign_text' => $t->besign_text,
                    'image_url' => $t->file_path ? Storage::url($t->file_path) : null,
                    'fields_count' => $t->textFields->count(),
                ];
            })->toArray();
    }

    public function updatedPresetKey($value)
    {
        if (isset($this->paperPresets[$value])) {
            $preset = $this->paperPresets[$value];
            if ($preset['width'] !== null) {
                $this->templateForm['width'] = $preset['width'];
            }
            if ($preset['height'] !== null) {
                $this->templateForm['height'] = $preset['height'];
            }
        }
    }

    // ── Template CRUD ──────────────────────────────────────────────────

    public function createTemplate()
    {
        $this->reset('templateForm', 'templateImage', 'editingTemplate');
        $this->templateForm = ['name' => '', 'width' => 297, 'height' => 210];
        $this->showTemplateForm = true;
    }

    public function editTemplate($id)
    {
        $tpl = CertificateTemplate::where('eventner_id', $this->eventner->id)->findOrFail($id);
        $this->editingTemplate = $id;
        $this->templateForm = [
            'name' => $tpl->name,
            'width' => $tpl->width,
            'height' => $tpl->height,
            'show_besign' => $tpl->show_besign ?? true,
            'besign_text' => $tpl->besign_text ?? '',
        ];
        $this->showTemplateForm = true;
    }

    public function saveTemplate()
    {
        $this->validate([
            'templateForm.name' => 'required|string|max:255',
            'templateForm.width' => 'required|numeric|min:50|max:1000',
            'templateForm.height' => 'required|numeric|min:50|max:1000',
        ]);

        $data = [
            'eventner_id' => $this->eventner->id,
            'name' => $this->templateForm['name'],
            'width' => $this->templateForm['width'],
            'height' => $this->templateForm['height'],
            'show_besign' => $this->templateForm['show_besign'] ?? true,
            'besign_text' => $this->templateForm['besign_text'] ?? null,
        ];

        if ($this->editingTemplate) {
            $tpl = CertificateTemplate::where('eventner_id', $this->eventner->id)->findOrFail($this->editingTemplate);

            if ($this->templateImage) {
                $this->validate(['templateImage' => 'image|max:10240']);
                if ($tpl->file_path) {
                    Storage::disk('public')->delete($tpl->file_path);
                }
                $data['file_path'] = $this->templateImage->store('certificate-templates', 'public');
            }

            $tpl->update($data);
            session()->flash('success', 'Template berhasil diperbarui.');
        } else {
            $this->validate(['templateImage' => 'required|image|max:10240']);
            $data['file_path'] = $this->templateImage->store('certificate-templates', 'public');

            CertificateTemplate::create($data);
            session()->flash('success', 'Template berhasil dibuat.');
        }

        $this->showTemplateForm = false;
        $this->reset('templateImage', 'editingTemplate');
        $this->loadTemplates();
    }

    public function deleteTemplate($id)
    {
        $tpl = CertificateTemplate::where('eventner_id', $this->eventner->id)->findOrFail($id);
        if ($tpl->file_path) {
            Storage::disk('public')->delete($tpl->file_path);
        }
        $tpl->delete();

        $this->loadTemplates();
        session()->flash('success', 'Template berhasil dihapus.');
    }

    // ── Download helpers ───────────────────────────────────────────────

    public function getChampionCategoriesProperty()
    {
        if (!$this->eventner) return collect();

        return ChampionCategory::where('eventner_id', $this->eventner->id)
            ->orderBy('name')
            ->get();
    }

    public function getCompetitionCategoriesForDownloadProperty()
    {
        if (!$this->eventner) return collect();

        return $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.eventner.certificate.templates', [
            'championCategories' => $this->championCategories,
            'competitionCategories' => $this->competitionCategoriesForDownload,
        ])->title('Sertifikat - ' . $this->eventner->nama_event);
    }
}
