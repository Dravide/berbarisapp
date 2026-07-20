<?php

namespace App\Livewire\Public;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\User;
use App\Models\Eventner;
use App\Models\Setting;
use App\Services\AutoGoPay;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

#[Layout('layouts.auth')]
#[Title('Daftar Eventner - BARIS APP')]
class EventnerRegister extends Component
{
    public $name = '';
    public $username = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $nama_event = '';
    public $lokasi = '';
    public $plan = 'free';
    public $agreeTerms = false;

    // Payment state
    public $showPayment = false;
    public $paymentQrUrl = null;
    public $paymentAmount = 0;
    public $eventnerId = null;
    public $paymentTransactionId = null;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username', 'regex:/^[a-z0-9_]+$/'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'nama_event' => ['required', 'string', 'max:255'],
            'lokasi' => ['required', 'string', 'max:255'],
            'plan' => ['required', Rule::in(['free', 'paid'])],
            'agreeTerms' => ['accepted'],
        ];
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function save()
    {
        $this->validate();

        $fee = (int) Setting::get('eventner_registration_fee', 50000);

        $user = User::create([
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => 'Eventner',
            'is_active' => $this->plan === 'free', // free langsung aktif, paid nanti setelah bayar
        ]);

        $eventner = Eventner::create([
            'user_id' => $user->id,
            'status' => $this->plan === 'free' ? 'pending' : 'pending',
            'plan' => $this->plan,
            'trial_ends_at' => $this->plan === 'free' ? now()->addDays(3) : null,
            'registration_source' => 'self',
            'nama_event' => $this->nama_event,
            'diselenggarakan_oleh' => $this->name,
            'lokasi' => $this->lokasi,
            'tanggal' => now()->addMonth()->toDateString(),
        ]);

        if ($this->plan === 'paid' && $fee > 0) {
            // Generate QRIS
            try {
                $autoGoPay = app(AutoGoPay::class);
                $result = $autoGoPay->generateQris($fee);

                if ($result['success'] ?? false) {
                    $data = $result['data'];
                    $eventner->update([
                        'autogopay_transaction_id' => $data['transaction_id'],
                        'qr_url' => $data['qr_url'] ?? null,
                        'qr_string' => $data['qr_string'] ?? null,
                    ]);

                    $this->eventnerId = $eventner->id;
                    $this->paymentQrUrl = $data['qr_url'] ?? null;
                    $this->paymentAmount = (int) ($data['amount'] ?? $fee);
                    $this->paymentTransactionId = $data['transaction_id'];
                    $this->showPayment = true;

                    return; // jangan redirect, tampilkan QRIS
                }
            } catch (\Throwable $e) {
                Log::error('EventnerRegister: QRIS generation failed', [
                    'error' => $e->getMessage(),
                ]);
                // Fallback ke pending manual
            }
        }

        if ($this->plan === 'free') {
            session()->flash('success', 'Pendaftaran berhasil! Silakan login dan lengkapi data event Anda.');
            return $this->redirect(route('login'));
        }

        // Paid plan tapi gagal generate QRIS atau fee = 0 — pending manual
        session()->flash('success', 'Pendaftaran berhasil! Silakan tunggu konfirmasi dari admin.');
        return $this->redirect(route('login'));
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
                    $eventner = Eventner::find($this->eventnerId);
                    if ($eventner && $eventner->status === 'pending') {
                        $eventner->update([
                            'status' => 'approved',
                            'approved_at' => now(),
                            'registration_paid_at' => now(),
                        ]);
                        $eventner->user->update(['is_active' => true]);
                    }

                    session()->flash('success', 'Pembayaran berhasil! Akun Anda sudah aktif.');
                    return $this->redirect(route('login'));
                }

                if ($txStatus === 'expire') {
                    session()->flash('error', 'Pembayaran kadaluarsa. Silakan daftar ulang.');
                    return $this->redirect(route('register.eventner'));
                }
            }
        } catch (\Throwable $e) {
            Log::error('EventnerRegister: checkPayment failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.public.eventner-register');
    }
}
