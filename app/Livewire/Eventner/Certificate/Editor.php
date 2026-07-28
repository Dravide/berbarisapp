<?php

namespace App\Livewire\Eventner\Certificate;

use App\Models\CertificateTemplate;
use App\Models\CertificateTextField;
use App\Traits\FeatureGatedComponent;
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
            CertificateTextField::where('id', $id)->update([$key => $updateVal]);

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

    public function render()
    {
        return view('livewire.eventner.certificate.editor', [
            'availableFieldKeys' => $this->availableFieldKeys,
            'usedFieldKeys' => $this->usedFieldKeys,
        ])->title('Edit Layout: ' . ($this->template['name'] ?? '') . ' - ' . $this->eventner->nama_event);
    }
}
