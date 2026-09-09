<?php

namespace App\Livewire\Public;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\User;
use App\Models\Eventner;
use App\Models\SaasPlan;
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

    public function mount()
    {
        // Pre-select dari query param (?plan=slug)
        if (request()->query('plan')) {
            $this->plan = request()->query('plan');
        }
    }

    public function rules(): array
    {
        $slugs = array_merge(
            ['free'],
            SaasPlan::where('is_active', true)->where('is_free', false)->where('is_contact', false)->pluck('slug')->all()
        );

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username', 'regex:/^[a-z0-9_]+$/'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'nama_event' => ['required', 'string', 'max:255'],
            'lokasi' => ['required', 'string', 'max:255'],
            'plan' => ['required', Rule::in($slugs)],
            'agreeTerms' => ['accepted'],
        ];
    }

    private function selectedPlan(): ?SaasPlan
    {
        if ($this->plan === 'free') {
            return null;
        }

        return SaasPlan::where('is_active', true)->where('is_free', false)->where('is_contact', false)->where('slug', $this->plan)->first();
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function save()
    {
        $this->validate();

        $paidPlan = $this->selectedPlan();
        $fee = $paidPlan?->registration_fee ?? 0;

        $user = User::create([
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => 'Eventner',
            'is_active' => $paidPlan === null, // free langsung aktif, paid nanti setelah bayar
        ]);

        $eventner = Eventner::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'plan' => $paidPlan ? 'paid' : 'free',
            'saas_plan_id' => $paidPlan?->id,
            'trial_ends_at' => $paidPlan === null ? now()->addDays(3) : null,
            'registration_source' => 'self',
            'nama_event' => $this->nama_event,
            'diselenggarakan_oleh' => $this->name,
            'lokasi' => $this->lokasi,
            'tanggal' => now()->addMonth()->toDateString(),
        ]);

        if ($paidPlan && $fee > 0) {
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

        if ($paidPlan === null) {
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
        return view('livewire.public.eventner-register', [
            'plans' => SaasPlan::with('features')->where('is_active', true)->where('is_contact', false)->orderBy('sort_order')->get(),
        ]);
    }
}
