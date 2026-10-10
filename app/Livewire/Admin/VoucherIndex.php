<?php

namespace App\Livewire\Admin;

use App\Models\RegistrationVoucher;
use App\Models\SaasPlan;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Kode promo pendaftaran eventner — potongan biaya paket SaaS saat
 * pendaftaran akun event (/register/eventner). Dibuat admin platform:
 * pendaftar belum punya event, jadi tidak ada tempat lain untuk mengelola.
 *
 * Voucher yang sudah dipakai eventner tidak boleh dihapus — kolom
 * eventners.registration_voucher_id kehilangan jejak snapshot potongannya.
 * Yang disediakan adalah toggle aktif/nonaktif.
 */
#[Layout('layouts.admin')]
class VoucherIndex extends Component
{
    public bool $showModal = false;
    public ?int $voucherId = null;

    // Form
    public string $code = '';
    public string $type = 'percent';
    public $value = 0;
    public $max_discount = '';
    public $max_uses = '';
    public $saas_plan_id = '';
    public $starts_at = '';
    public $ends_at = '';
    public bool $is_active = true;

    private function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:50',
                // Unique kecuali dirinya sendiri saat edit.
                \Illuminate\Validation\Rule::unique('registration_vouchers', 'code')
                    ->ignore($this->voucherId),
            ],
            'type' => 'required|in:percent,flat',
            // Persen dibatasi 90 — QRIS menolak nominal 0, jadi voucher
            // tidak boleh menggratiskan pendaftaran sepenuhnya.
            'value' => 'required|integer|min:1' . ($this->type === 'percent' ? '|max:90' : ''),
            'max_discount' => 'nullable|integer|min:1',
            'max_uses' => 'nullable|integer|min:1',
            'saas_plan_id' => 'nullable',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ];
    }

    private function messages(): array
    {
        return [
            'code.required' => 'Kode promo wajib diisi.',
            'code.unique' => 'Kode promo sudah dipakai.',
            'value.required' => 'Nilai potongan wajib diisi.',
            'value.max' => 'Diskon persen maksimal 90% — pendaftaran tidak boleh gratis penuh.',
            'ends_at.after' => 'Berakhir harus setelah mulai.',
        ];
    }

    public function createVoucher()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editVoucher(int $id)
    {
        $voucher = RegistrationVoucher::findOrFail($id);

        $this->voucherId = $voucher->id;
        $this->code = $voucher->code;
        $this->type = $voucher->type;
        $this->value = $voucher->value;
        $this->max_discount = $voucher->max_discount ?? '';
        $this->max_uses = $voucher->max_uses ?? '';
        $this->saas_plan_id = (string) ($voucher->saas_plan_id ?? '');
        $this->starts_at = $voucher->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->ends_at = $voucher->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->is_active = $voucher->is_active;

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate($this->rules(), $this->messages());

        // Voucher boleh dibatasi ke satu paket berbayar; pilihan di luar
        // daftar (nilai DOM dipalsukan) dianggap "semua paket".
        $planId = SaasPlan::where('is_active', true)->where('is_free', false)
            ->where('id', (int) $this->saas_plan_id)->exists()
            ? (int) $this->saas_plan_id
            : null;

        $payload = [
            'code' => $this->code,
            'type' => $this->type,
            'value' => (int) $this->value,
            'max_discount' => $this->type === 'percent' && $this->max_discount !== '' ? (int) $this->max_discount : null,
            'max_uses' => $this->max_uses !== '' ? (int) $this->max_uses : null,
            'saas_plan_id' => $planId,
            'starts_at' => $this->starts_at ?: null,
            'ends_at' => $this->ends_at ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->voucherId) {
            RegistrationVoucher::findOrFail($this->voucherId)->update($payload);
        } else {
            RegistrationVoucher::create($payload);
        }

        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', 'Kode promo tersimpan.');
    }

    /** Nonaktifkan/aktifkan — bukan hapus (lihat docblock kelas). */
    public function toggle(int $id)
    {
        $voucher = RegistrationVoucher::findOrFail($id);
        $voucher->update(['is_active' => ! $voucher->is_active]);
    }

    public function delete(int $id)
    {
        // Baris yang sudah dipakai eventner tidak boleh hilang — snapshot
        // voucher_discount tetap sah, tapi jejak kodenya putus.
        $dipakai = \App\Models\Eventner::where('registration_voucher_id', $id)->exists();
        if ($dipakai) {
            session()->flash('error', 'Kode sudah dipakai pendaftar — nonaktifkan saja, jangan dihapus.');
            return;
        }

        RegistrationVoucher::whereKey($id)->delete();
        session()->flash('success', 'Kode promo dihapus.');
    }

    public function resetForm()
    {
        $this->reset(['voucherId', 'code', 'type', 'value', 'max_discount', 'max_uses', 'saas_plan_id', 'starts_at', 'ends_at']);
        $this->type = 'percent';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.voucher-index', [
            'vouchers' => RegistrationVoucher::orderBy('id', 'desc')->get(),
            'plans' => SaasPlan::where('is_active', true)->where('is_free', false)->orderBy('sort_order')->get(['id', 'name']),
        ])->title('Kode Promo Pendaftaran');
    }
}
