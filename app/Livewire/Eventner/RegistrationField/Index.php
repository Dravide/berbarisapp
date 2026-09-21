<?php

namespace App\Livewire\Eventner\RegistrationField;

use App\Models\RegistrationField;
use App\Models\RegistrationFieldValue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Field builder formulir pendaftaran.
 *
 * Panitia menentukan sendiri field apa saja yang diminta saat pendaftaran —
 * termasuk berkas unggahan. Definisi tersimpan per event di
 * `registration_fields`, lalu dibaca form publik, portal magic link, dashboard
 * peserta, cetakan PDF, dan sertifikat.
 *
 * Field bawaan (is_builtin) tidak boleh dihapus atau diganti tipenya: nilainya
 * ditulis ke kolom nyata di tabel registrations yang masih dibaca tampilan
 * publik lama (landing, hasil, vote). Yang boleh diubah: label, wajib, aktif,
 * petunjuk, dan urutan.
 */
#[Layout('layouts.admin')]
class Index extends Component
{
    public $label = '';
    public $type = 'text';
    public $help_text = '';
    public $default_value = '';
    public $max_kb = '';
    public $is_required = false;
    public $is_active = true;

    /** Satu opsi per baris untuk tipe select. */
    public $optionsText = '';

    /**
     * Kedalaman dropdown untuk tipe wilayah.
     *
     * `auto` mengikuti tingkat_perlombaan event, sisanya dipatok panitia —
     * inilah cara membuat field "wilayah dinamis" (tiga tingkat sekaligus).
     */
    public $wilayahLevel = 'auto';

    public $isEditMode = false;
    public $editingId = null;

    public $showFormModal = false;

    protected $eventnerId;

    public function boot()
    {
        $eventner = Auth::user()->eventner;
        if (! $eventner) {
            abort(403);
        }
        $this->eventnerId = $eventner->id;

        // Event baru/event lama yang belum pernah dibuka builder-nya langsung
        // punya field bawaan tanpa perlu seeder.
        RegistrationField::ensureDefaults($eventner);
    }

    #[Computed]
    public function fields()
    {
        return RegistrationField::where('eventner_id', $this->eventnerId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /** Jumlah registrasi yang sudah mengisi tiap field — penentu boleh dihapus. */
    #[Computed]
    public function usageCounts()
    {
        return RegistrationFieldValue::whereIn('registration_field_id', $this->fields->pluck('id'))
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->selectRaw('registration_field_id, count(*) as total')
            ->groupBy('registration_field_id')
            ->pluck('total', 'registration_field_id');
    }

    /** Peringatan bila email dimatikan — magic link tidak akan terkirim. */
    #[Computed]
    public function emailFieldDisabled(): bool
    {
        $email = $this->fields->firstWhere('builtin_source', 'school_email');

        return $email && ! $email->is_active;
    }

    public function create()
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit($id)
    {
        $field = $this->findField($id);

        $this->isEditMode = true;
        $this->editingId = $field->id;
        $this->label = $field->label;
        $this->type = $field->type;
        $this->help_text = $field->help_text ?? '';
        $this->default_value = $field->default_value ?? '';
        $this->max_kb = $field->max_kb === null ? '' : (string) $field->max_kb;
        $this->is_required = $field->is_required;
        $this->is_active = $field->is_active;
        $this->optionsText = collect($field->options ?? [])
            ->map(fn ($o) => ($o['value'] ?? '') . ' | ' . ($o['label'] ?? ''))
            ->implode("\n");
        $this->wilayahLevel = $field->wilayah_level ?: 'auto';

        $this->showFormModal = true;
    }

    public function save()
    {
        $field = $this->isEditMode && $this->editingId ? $this->findField($this->editingId) : null;

        $this->validate([
            'label' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', RegistrationField::TYPES),
            'help_text' => 'nullable|string|max:255',
            'default_value' => 'nullable|string|max:255',
            'max_kb' => 'nullable|integer|min:1|max:51200',
            'optionsText' => 'nullable|string',
            'wilayahLevel' => 'required|in:' . implode(',', RegistrationField::WILAYAH_LEVELS),
        ], [
            'label.required' => 'Label field wajib diisi.',
            'label.max' => 'Label field maksimal 255 karakter.',
            'type.in' => 'Tipe field tidak dikenal.',
            'max_kb.integer' => 'Batas ukuran harus berupa angka (KB).',
            'wilayahLevel.in' => 'Tingkat wilayah tidak dikenal.',
        ]);

        $options = $this->parseOptions();

        if ($this->type === 'select' && $options === []) {
            $this->addError('optionsText', 'Tipe pilihan butuh minimal satu opsi.');
            return;
        }

        $data = [
            'label' => strip_tags($this->label),
            'type' => $this->type,
            'options' => $options ?: null,
            'default_value' => $this->default_value !== '' ? strip_tags($this->default_value) : null,
            'help_text' => $this->help_text !== '' ? strip_tags($this->help_text) : null,
            'max_kb' => $this->max_kb === '' ? null : (int) $this->max_kb,
            'is_required' => (bool) $this->is_required,
            'is_active' => (bool) $this->is_active,
            // Hanya tipe wilayah yang memakainya; tipe lain dibiarkan null
            // supaya berganti tipe tidak meninggalkan sisa setelan lama.
            'wilayah_level' => $this->type === 'wilayah' ? $this->wilayahLevel : null,
        ];

        if ($field) {
            // Tipe field bawaan terkunci: nilainya ditulis ke kolom
            // registrations dengan format tertentu, menggantinya jadi file
            // akan memutus tampilan lama.
            if ($field->is_builtin) {
                $data['type'] = $field->type;
            }

            // Dihitung setelah tipe dikunci — untuk field bawaan, tipe yang
            // berlaku adalah tipe tersimpan, bukan yang dikirim form.
            $data['wilayah_level'] = $data['type'] === 'wilayah' ? $this->wilayahLevel : null;

            $field->update($data);
            $field->sinkronToggleLama();
            session()->flash('success', 'Field "' . $field->label . '" berhasil diperbarui.');
        } else {
            $field = RegistrationField::create(array_merge($data, [
                'eventner_id' => $this->eventnerId,
                'field_key' => $this->uniqueKey($this->label),
                'is_builtin' => false,
                'max_kb' => $data['max_kb'] ?? (in_array($this->type, RegistrationField::FILE_TYPES, true) ? 5120 : null),
                'sort_order' => (int) RegistrationField::where('eventner_id', $this->eventnerId)->max('sort_order') + 1,
            ]));
            $field->sinkronToggleLama();
            session()->flash('success', 'Field baru berhasil ditambahkan.');
        }

        $this->closeFormModal();
    }

    /**
     * Hapus field buatan panitia.
     *
     * Field bawaan tidak bisa dihapus (hanya dinonaktifkan), dan field yang
     * sudah terisi jawaban juga ditolak — menghapusnya berarti membuang data
     * pendaftar yang sudah masuk.
     */
    public function delete($id)
    {
        $field = $this->findField($id);

        if ($field->is_builtin) {
            session()->flash('error', 'Field bawaan tidak bisa dihapus. Nonaktifkan saja bila tidak dipakai.');
            return;
        }

        $terpakai = (int) ($this->usageCounts[$field->id] ?? 0);
        if ($terpakai > 0) {
            session()->flash('error', 'Tidak bisa menghapus: sudah ada ' . $terpakai . ' pendaftar yang mengisi field ini. Nonaktifkan saja.');
            return;
        }

        $field->delete();
        session()->flash('success', 'Field berhasil dihapus.');
    }

    public function toggleActive($id)
    {
        $field = $this->findField($id);

        // Mematikan email memutus pengiriman magic link otomatis ke pendaftar.
        $field->is_active = ! $field->is_active;
        $field->save();

        // Kolom lama (surat_tugas_required / kwitansi_required) masih dibaca
        // halaman pengaturan profil event, jadi ikut dicerminkan.
        $field->sinkronToggleLama();

        session()->flash('success', 'Status field berhasil diubah.');
    }

    public function moveUp($id)
    {
        $this->swapSort($id, 'up');
    }

    public function moveDown($id)
    {
        $this->swapSort($id, 'down');
    }

    private function swapSort($id, $direction)
    {
        $field = $this->findField($id);
        $all = RegistrationField::where('eventner_id', $this->eventnerId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $index = $all->search(fn ($f) => $f->id === $field->id);
        $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || $targetIndex < 0 || $targetIndex >= $all->count()) {
            return;
        }

        $neighbor = $all[$targetIndex];

        $fieldOrder = $field->sort_order;
        $field->update(['sort_order' => $neighbor->sort_order]);
        $neighbor->update(['sort_order' => $fieldOrder]);
    }

    public function closeFormModal()
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset(['label', 'help_text', 'default_value', 'max_kb', 'optionsText', 'isEditMode', 'editingId']);
        $this->type = 'text';
        $this->wilayahLevel = 'auto';
        $this->is_required = false;
        $this->is_active = true;
        $this->resetValidation();
    }

    private function findField($id): RegistrationField
    {
        return RegistrationField::where('eventner_id', $this->eventnerId)->findOrFail($id);
    }

    /** "Nilai | Label" per baris; label opsional (default = nilainya). */
    private function parseOptions(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $this->optionsText))
            ->map(fn ($baris) => trim($baris))
            ->filter()
            ->map(function ($baris) {
                [$value, $label] = array_pad(array_map('trim', explode('|', $baris, 2)), 2, null);
                $value = strip_tags($value);

                return [
                    'value' => $value,
                    'label' => strip_tags($label ?: $value),
                ];
            })
            ->unique('value')
            ->values()
            ->all();
    }

    /** field_key unik per event, diturunkan dari label. */
    private function uniqueKey(string $label): string
    {
        $dasar = Str::slug($label, '_');
        $dasar = $dasar !== '' ? Str::limit($dasar, 40, '') : 'field';
        $key = $dasar;
        $n = 2;

        while (RegistrationField::where('eventner_id', $this->eventnerId)->where('field_key', $key)->exists()) {
            $key = $dasar . '_' . $n++;
        }

        return $key;
    }

    public function render()
    {
        return view('livewire.eventner.registration-field.index')
            ->title('Field Pendaftaran - ' . app_name());
    }
}
