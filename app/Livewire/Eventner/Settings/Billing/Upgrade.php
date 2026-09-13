<?php

namespace App\Livewire\Eventner\Settings\Billing;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Eventner;
use App\Models\SaasPlan;
use App\Models\Setting;
use App\Services\AutoGoPay;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.admin')]
class Upgrade extends Component
{
    public Eventner $eventner;
    public bool $showPayment = false;
    public ?string $paymentQrUrl = null;
    public int $paymentAmount = 0;
    public ?string $paymentTransactionId = null;
    public ?SaasPlan $selectedPlan = null;

    /** Paket sudah aktif — halaman ini jadi ringkasan, bukan pembelian. */
    public bool $isActive = false;

    /**
     * Halaman ini melayani dua hal: menyelesaikan pembayaran paket yang belum
     * dibayar, dan menjelaskan paket yang sudah aktif.
     *
     * Sumber kebenaran "sudah aktif" adalah registration_paid_at, BUKAN plan —
     * lihat catatan yang sama di AutoGoPayWebhookController::handleEventnerSettlement().
     * Eventner berbayar ber-plan 'paid' sejak mendaftar, sebelum membayar; kalau
     * penjagaannya memakai plan, mereka tidak akan pernah sampai ke QRIS.
     */
    public function mount()
    {
        $eventner = Auth::user()->eventner;

        // Admin (atau akun tanpa data event) tidak punya apa pun untuk
        // di-upgrade. Sebelumnya di sini abort(403), tapi akun admin memang
        // tidak punya eventner, sementara properti $eventner bertipe
        // non-nullable — halaman ini berakhir 500 (TypeError saat menugaskan
        // null), bukan 403. Dialihkan saja.
        if (!$eventner) {
            return redirect()->route('dashboard');
        }

        $this->eventner = $eventner;

        // Sudah aktif: dibayar sendiri, atau paketnya diberikan admin
        // (assignPlan mengisi registration_paid_at). Tampilkan ringkasan —
        // bukan redirect, karena tautan "Ubah paket" di dashboard menunjuk ke
        // sini dan redirect membuat tautan itu terasa mati.
        if ($this->eventner->registration_paid_at !== null) {
            $this->isActive = true;
            return;
        }

        // Legacy: plan='paid' tanpa paket dan tanpa transaksi (pemberian manual
        // dari sebelum modul paket ada). Akses penuh sudah aktif, jadi tidak ada
        // yang bisa dibeli — halaman ini hanya menjelaskan.
        if ($this->eventner->plan === 'paid' && $this->eventner->saas_plan_id === null) {
            $this->isActive = true;
            return;
        }

        // Transaksi pending sebelumnya? Tampilkan QR-nya lagi (belum settle)
        if ($this->eventner->autogopay_transaction_id && !$this->eventner->registration_paid_at) {
            try {
                $status = app(AutoGoPay::class)->checkStatus($this->eventner->autogopay_transaction_id);
                if (($status['success'] ?? false) && ($status['data']['transaction_status'] ?? '') !== 'expire') {
                    $this->paymentTransactionId = $this->eventner->autogopay_transaction_id;
                    $this->paymentQrUrl = $this->eventner->qr_url;
                    $this->paymentAmount = $this->eventner->saasPlan?->price
                        ?? (int) Setting::get('eventner_plan_price', 150000);
                    $this->selectedPlan = $this->eventner->saasPlan
                        ?? SaasPlan::where('is_active', true)->where('is_free', false)->orderBy('sort_order')->first();
                    $this->showPayment = true;
                }
            } catch (\Throwable $e) {
                Log::warning('Upgrade: resume payment check failed', ['error' => $e->getMessage()]);
            }
        }
    }

    public function generatePayment(?int $planId = null)
    {
        // Paket sudah aktif — jangan buat QRIS. Dulu dijaga pada plan === 'paid'
        // saja, sehingga eventner ber-paket gratis yang memanggil ulang method
        // ini bisa dipindahkan ke paket berbayar pertama tanpa disadari:
        // generatePayment menulis saas_plan_id ke eventner.
        if ($this->isActive || $this->eventner->registration_paid_at !== null) {
            return;
        }

        $plan = $planId
            ? SaasPlan::where('is_active', true)->where('is_free', false)->where('is_contact', false)->findOrFail($planId)
            : SaasPlan::where('is_active', true)->where('is_free', false)->where('is_contact', false)->orderBy('sort_order')->first();

        $price = $plan?->price ?? (int) Setting::get('eventner_plan_price', 150000);

        try {
            $result = app(AutoGoPay::class)->generateQris($price);

            if ($result['success'] ?? false) {
                $data = $result['data'];
                $this->eventner->update([
                    'saas_plan_id' => $plan?->id,
                    'autogopay_transaction_id' => $data['transaction_id'],
                    'qr_url' => $data['qr_url'] ?? null,
                    'qr_string' => $data['qr_string'] ?? null,
                ]);

                $this->selectedPlan = $plan;
                $this->paymentTransactionId = $data['transaction_id'];
                $this->paymentQrUrl = $data['qr_url'] ?? null;
                $this->paymentAmount = (int) ($data['amount'] ?? $price);
                $this->showPayment = true;
            } else {
                session()->flash('error', 'Gagal membuat QRIS. Silakan coba lagi.');
            }
        } catch (\Throwable $e) {
            Log::error('Upgrade: QRIS generation failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Gagal membuat QRIS. Silakan coba lagi nanti.');
        }
    }

    public function checkPayment()
    {
        if (!$this->paymentTransactionId) {
            return;
        }

        try {
            $autoGoPay = app(AutoGoPay::class);
            $status = $autoGoPay->checkStatus($this->paymentTransactionId);

            if ($status['success'] ?? false) {
                $txStatus = $status['data']['transaction_status'] ?? '';

                if ($txStatus === 'settlement') {
                    // Settle di sini juga — jangan bergantung webhook saja.
                    // Idempotent via registration_paid_at (plan 'paid' sudah ter-set sejak daftar).
                    if (!$this->eventner->registration_paid_at) {
                        $wasPending = $this->eventner->status !== 'approved';
                        $this->eventner->update([
                            'plan' => 'paid',
                            'status' => 'approved',
                            'approved_at' => $this->eventner->approved_at ?? now(),
                            'registration_paid_at' => now(),
                        ]);
                        $this->eventner->user->update(['is_active' => true]);

                        if ($wasPending) {
                            try {
                                app(\App\Services\MailyService::class)->sendEventnerApproved(
                                    $this->eventner->user->email,
                                    $this->eventner->user->name,
                                    $this->eventner->nama_event
                                );
                            } catch (\Exception $e) {
                                Log::warning('Maily.id: sendEventnerApproved failed (upgrade)', [
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    }

                    session()->flash('success', 'Pembayaran berhasil! Fitur premium paket terpilih sudah aktif.');
                    return redirect()->route('dashboard');
                }

                if ($txStatus === 'expire') {
                    $this->showPayment = false;
                    $this->paymentTransactionId = null;
                    session()->flash('error', 'QRIS kadaluarsa. Buat QR baru untuk mencoba lagi.');
                }
            }
        } catch (\Throwable $e) {
            Log::error('Upgrade: checkPayment failed', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.eventner.settings.billing.upgrade', [
            'plans' => SaasPlan::with('features')->where('is_active', true)->where('is_contact', false)->orderBy('sort_order')->get(),
        ])->title('Paket & Tagihan - ' . app_name());
    }
}
