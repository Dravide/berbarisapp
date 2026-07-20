<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Services\AutoGoPay;

#[Layout('layouts.auth')]
#[Title('Dashboard - BARIS APP')]
class Index extends Component
{
    public $eventner = null;
    public $paymentQrUrl = null;
    public $paymentAmount = 0;
    public $paymentTransactionId = null;

    public function mount()
    {
        $user = auth()->user();

        // Unpaid paid plan — render di layout auth (tanpa sidebar)
        if (!$user->is_active && $user->role === 'Eventner') {
            $this->eventner = $user->eventner;
            if ($this->eventner && $this->eventner->plan === 'paid' && $this->eventner->status === 'pending') {
                $this->paymentAmount = (int) \App\Models\Setting::get('eventner_registration_fee', 50000);
            }
            return;
        }

        if (!$user->is_active) {
            return;
        }

        if ($user->role === 'Admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === 'Eventner') {
            return redirect()->route('eventner.dashboard');
        }
    }

    public function generatePayment()
    {
        if (!$this->eventner) return;

        try {
            $autoGoPay = app(AutoGoPay::class);
            $fee = (int) \App\Models\Setting::get('eventner_registration_fee', 50000);
            $result = $autoGoPay->generateQris($fee);

            if ($result['success'] ?? false) {
                $data = $result['data'];
                $this->eventner->update([
                    'autogopay_transaction_id' => $data['transaction_id'],
                    'qr_url' => $data['qr_url'] ?? null,
                    'qr_string' => $data['qr_string'] ?? null,
                ]);

                $this->paymentQrUrl = $data['qr_url'] ?? null;
                $this->paymentTransactionId = $data['transaction_id'];
                $this->paymentAmount = (int) ($data['amount'] ?? $fee);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Dashboard: generatePayment failed', [
                'error' => $e->getMessage(),
            ]);
            session()->flash('payment_error', 'Gagal membuat QRIS. Silakan coba lagi.');
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
                    $this->eventner->update([
                        'status' => 'approved',
                        'approved_at' => now(),
                        'registration_paid_at' => now(),
                    ]);
                    $this->eventner->user->update(['is_active' => true]);

                    return redirect()->route('eventner.dashboard');
                }

                if ($txStatus === 'expire') {
                    // Hapus QR lama, user bisa klik Bayar untuk generate baru
                    $this->eventner->update([
                        'autogopay_transaction_id' => null,
                        'qr_url' => null,
                        'qr_string' => null,
                    ]);
                    $this->paymentQrUrl = null;
                    $this->paymentTransactionId = null;
                    session()->flash('payment_expired', true);
                    return;
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Dashboard: checkPayment failed', [
                'error' => $e->getMessage(),
            ]);
        }

        session()->flash('payment_error', 'Gagal mengecek pembayaran. Silakan coba lagi.');
    }

    public function render()
    {
        $user = auth()->user();

        // Inactive — pakai layout auth (tanpa sidebar)
        if (!$user->is_active) {
            return view('livewire.dashboard.inactive')
                ->with('showPayment', $user->role === 'Eventner' && $this->eventner)
                ->with('eventner', $this->eventner)
                ->with('paymentQrUrl', $this->paymentQrUrl)
                ->with('paymentAmount', $this->paymentAmount)
                ->with('paymentTransactionId', $this->paymentTransactionId);
        }

        // Active — redirect ke dashboard masing-masing role
        // (tidak akan sampai sini karena mount() redirect duluan)
        return view('livewire.dashboard.index');
    }
}
