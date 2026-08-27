<?php

namespace App\Livewire\Eventner\Settings\Billing;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Eventner;
use App\Models\Setting;
use App\Services\AutoGoPay;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.admin')]
#[Title('Paket & Tagihan - BARIS APP')]
class Upgrade extends Component
{
    public Eventner $eventner;
    public bool $showPayment = false;
    public ?string $paymentQrUrl = null;
    public int $paymentAmount = 0;
    public ?string $paymentTransactionId = null;

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;
        if (!$this->eventner) {
            abort(403);
        }

        // Sudah paid — tidak ada yang perlu di-upgrade
        if ($this->eventner->plan === 'paid') {
            return redirect()->route('dashboard');
        }

        // Transaksi pending sebelumnya? Tampilkan QR-nya lagi (belum settle)
        if ($this->eventner->autogopay_transaction_id && !$this->eventner->registration_paid_at) {
            try {
                $status = app(AutoGoPay::class)->checkStatus($this->eventner->autogopay_transaction_id);
                if (($status['success'] ?? false) && ($status['data']['transaction_status'] ?? '') !== 'expire') {
                    $this->paymentTransactionId = $this->eventner->autogopay_transaction_id;
                    $this->paymentQrUrl = $this->eventner->qr_url;
                    $this->paymentAmount = (int) Setting::get('eventner_plan_price', 150000);
                    $this->showPayment = true;
                }
            } catch (\Throwable $e) {
                Log::warning('Upgrade: resume payment check failed', ['error' => $e->getMessage()]);
            }
        }
    }

    public function generatePayment()
    {
        if ($this->eventner->plan === 'paid') {
            return;
        }

        $price = (int) Setting::get('eventner_plan_price', 150000);

        try {
            $result = app(AutoGoPay::class)->generateQris($price);

            if ($result['success'] ?? false) {
                $data = $result['data'];
                $this->eventner->update([
                    'autogopay_transaction_id' => $data['transaction_id'],
                    'qr_url' => $data['qr_url'] ?? null,
                    'qr_string' => $data['qr_string'] ?? null,
                ]);

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
            $status = app(AutoGoPay::class)->checkStatus($this->paymentTransactionId);

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

                    session()->flash('success', 'Pembayaran berhasil! Semua fitur premium sudah aktif.');
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
        return view('livewire.eventner.settings.billing.upgrade');
    }
}
