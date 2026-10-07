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

// Halaman tamu memakai layout ringan berbasis Tailwind (sama bahasa desain
// dengan landing/pricing/login), bukan layouts.auth milik template admin.
#[Layout('layouts.auth-clean')]
class EventnerRegister extends Component
{
    public $name = '';
    public $username = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $nama_event = '';
    public $lokasi = '';
    public $no_hp = '';
    public $plan = '';
    public $agreeTerms = false;

    // Payment state
    public $showPayment = false;
    public $paymentQrUrl = null;
    public $paymentAmount = 0;
    public $eventnerId = null;
    public $paymentTransactionId = null;

    public function mount()
    {
        // Paket gratis dipilih dari DB, bukan dari literal 'free': slug paket
        // gratis bisa apa saja (produksi memakai 'gratis'), dan literal 'free'
        // membuat Rule::in menolaknya — "plan yang dipilih tidak valid".
        $this->plan = $this->freePlan()?->slug ?? '';

        // Pre-select dari query param (?plan=slug)
        if (request()->query('plan')) {
            $this->plan = request()->query('plan');
        }
    }

    /** Paket yang menggratiskan pendaftaran (is_free), kalau admin punya. */
    private function freePlan(): ?SaasPlan
    {
        return SaasPlan::where('is_active', true)->where('is_free', true)->orderBy('sort_order')->first();
    }

    public function rules(): array
    {
        $slugs = array_merge(
            array_filter([$this->freePlan()?->slug]),
            SaasPlan::where('is_active', true)->where('is_free', false)->where('is_contact', false)->pluck('slug')->all()
        );

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username', 'regex:/^[a-z0-9_]+$/'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            // Wajib, tapi tidak harus unik: satu orang boleh punya lebih dari
            // satu akun event (dua lomba berbeda), dan menolak nomor kedua
            // membuat pemiliknya mengarang nomor palsu. Yang dijaga adalah
            // bentuknya — sudah dalam bentuk normalisasi: 08 + 8..11 digit
            // (nomor seluler Indonesia 10-13 digit).
            'no_hp' => ['required', 'string', 'regex:/^08[0-9]{8,11}$/'],
            'password' => ['required', 'min:8', 'confirmed'],
            'nama_event' => ['required', 'string', 'max:255'],
            'lokasi' => ['required', 'string', 'max:255'],
            'plan' => ['required', Rule::in($slugs)],
            'agreeTerms' => ['accepted'],
        ];
    }

    /**
     * Paket berbayar yang dipilih; null berarti paket gratis.
     *
     * Dicocokkan lewat is_free, bukan lewat slug: pemilik event boleh menamai
     * paket gratisnya apa saja, dan yang menentukan gratis adalah centang
     * is_free di halaman Paket Harga.
     */
    private function selectedPlan(): ?SaasPlan
    {
        return SaasPlan::where('is_active', true)
            ->where('is_contact', false)
            ->where('slug', $this->plan)
            ->where('is_free', false)
            ->first();
    }

    public function updated($propertyName)
    {
        // Nomor dirapikan DULU supaya yang divalidasi (dan yang tampil kembali
        // di layar) adalah bentuk simpannya. Tanpa ini, "0812-3456-7890" yang
        // diketik wajar ditolak regex yang menuntut digit polos.
        if ($propertyName === 'no_hp') {
            $this->no_hp = normalisasi_no_hp($this->no_hp) ?? '';
        }

        $this->validateOnly($propertyName);
    }

    public function save()
    {
        $this->no_hp = normalisasi_no_hp($this->no_hp) ?? '';

        $this->validate();

        $paidPlan = $this->selectedPlan();

        // Satu-satunya harga paket = kolom price. Dulu di sini ditagih
        // registration_fee (50.000) sementara webhook memvalidasi ke price
        // (150.000), jadi settlement selalu ditolak diam-diam dan akun
        // menggantung sampai pengguna menekan "Cek Pembayaran".
        $fee = $paidPlan?->price ?? 0;

        $user = User::create([
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'no_hp' => $this->no_hp,
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
            // Voting opt-in: penyelenggara menyalakannya sendiri di
            // Pengaturan Vote, jangan dibuka otomatis tanpa jadwal.
            'vote_active' => false,
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
        ])->title('Daftar Eventner - ' . app_name());
    }
}
