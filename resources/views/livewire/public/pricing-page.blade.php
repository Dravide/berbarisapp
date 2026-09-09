<div class="min-h-screen bg-surface">
    <div class="container-landing py-12 md:py-16">
        {{-- Heading --}}
        <div class="mx-auto max-w-2xl text-center">
            <span class="overline justify-center">Harga</span>
            <h1 class="mt-4 font-display text-3xl font-bold md:text-4xl">Harga &amp; Paket</h1>
            <p class="mt-4 text-on-surface-variant">
                Kelola perlombaan sekolah dengan gratis. Aktifkan fitur premium sekali bayar per event — tanpa langganan bulanan.
            </p>
        </div>

        {{-- Kartu Paket --}}
        <div class="mt-12 grid grid-cols-1 items-stretch justify-center gap-6 md:grid-cols-2 lg:grid-cols-{{ count($plans) > 3 ? 3 : count($plans) }}">
            @foreach($plans as $plan)
                @php
                    $eventner = auth()->user()?->role === 'Eventner' ? auth()->user()->eventner : null;
                    $isOwned = $eventner && $eventner->saas_plan_id === $plan['id'];
                    $hasPaid = $eventner && ($eventner->plan === 'paid' || $eventner->registration_paid_at);
                @endphp
                <div class="surface-card relative flex flex-col overflow-hidden p-8 {{ $plan['highlight'] ? 'border-2 border-secondary' : '' }}">
                    @if($plan['highlight'])
                        <span class="absolute top-4 right-4 rounded-full bg-secondary px-3 py-1 text-xs font-bold text-deep-slate">Rekomendasi</span>
                    @endif
                    <h3 class="text-lg font-bold text-deep-slate">{{ $plan['name'] }}</h3>
                    @if($plan['description'])
                        <p class="mt-1 text-sm text-on-surface-variant">{{ $plan['description'] }}</p>
                    @endif
                    <div class="mt-5">
                        @if($plan['is_free'])
                            <span class="font-display text-4xl font-extrabold text-deep-slate">Rp 0</span>
                        @elseif($plan['is_contact'])
                            <span class="font-display text-4xl font-extrabold text-primary">Kustom</span>
                            <p class="mt-1 text-xs text-on-surface-variant">Harga disepakati bersama admin</p>
                        @else
                            <span class="font-display text-4xl font-extrabold text-primary">Rp {{ number_format($plan['price'], 0, ',', '.') }}</span>
                            @if($plan['registration_fee'] > 0)
                                <p class="mt-1 text-xs text-on-surface-variant">+ biaya pendaftaran Rp {{ number_format($plan['registration_fee'], 0, ',', '.') }}</p>
                            @endif
                        @endif
                    </div>
                    <ul class="mt-6 flex flex-1 flex-col gap-3 text-sm">
                        @if($plan['is_free'])
                            <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> Dashboard event & profil</li>
                            <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> Kategori lomba & pendaftaran peserta</li>
                            <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> Manajemen juri & input nilai</li>
                            <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> Rekap nilai & scoreboard publik</li>
                            <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> QR check-in peserta</li>
                        @else
                            <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> Semua fitur paket gratis</li>
                            @foreach($plan['features'] as $featureKey)
                                @php $label = config("eventner_features.{$featureKey}.label", $featureKey); @endphp
                                <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> {{ $label }}</li>
                            @endforeach
                            <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> {{ $plan['is_contact'] ? 'Aktivasi oleh admin setelah konfirmasi' : 'Aktivasi otomatis setelah bayar' }}</li>
                        @endif
                    </ul>
                    <div class="mt-8">
                        @if($plan['is_contact'])
                            <a href="{{ $plan['contact_url'] ?: '#contact' }}" target="_blank" rel="noopener"
                                class="{{ $plan['highlight'] ? 'btn-primary' : 'btn-ghost' }} w-full justify-center">
                                <i class="ti ti-message-circle"></i> Hubungi Admin
                            </a>
                        @elseif(!auth()->check())
                            <a href="{{ route('register.eventner') }}{{ $plan['is_free'] ? '?plan=free' : '?plan=' . $plan['slug'] }}"
                                class="{{ $plan['highlight'] ? 'btn-primary' : 'btn-ghost' }} w-full justify-center">
                                {{ $plan['is_free'] ? 'Daftar Gratis' : 'Mulai Sekarang' }}
                            </a>
                        @elseif($eventner)
                            @if($isOwned && $hasPaid)
                                <span class="btn-primary pointer-events-none w-full justify-center opacity-60"><i class="ti ti-circle-check"></i> Paket Anda</span>
                            @elseif($isOwned)
                                <a href="{{ route('eventner.billing.upgrade') }}" class="btn-primary w-full justify-center"><i class="ti ti-bolt"></i> Aktifkan Sekarang</a>
                            @elseif($hasPaid)
                                <a href="{{ route('dashboard') }}" class="btn-ghost w-full justify-center">Ke Dashboard</a>
                            @else
                                <a href="{{ route('eventner.billing.upgrade') }}" class="{{ $plan['highlight'] ? 'btn-primary' : 'btn-ghost' }} w-full justify-center">Pilih Paket Ini</a>
                            @endif
                        @else
                            <a href="{{ route('dashboard') }}" class="{{ $plan['highlight'] ? 'btn-primary' : 'btn-ghost' }} w-full justify-center">Ke Dashboard</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Info pembayaran --}}
        <div class="mx-auto mt-10 max-w-2xl">
            <div class="surface-card flex items-start gap-3 p-5 border border-primary/20">
                <i class="ti ti-info-circle shrink-0 text-xl text-primary"></i>
                <div class="text-sm text-on-surface-variant">
                    <span class="font-bold text-deep-slate">Cara kerja pembayaran:</span>
                    Pilih paket → scan QRIS → aktivasi otomatis dalam hitungan detik setelah pembayaran terkonfirmasi. Tidak ada verifikasi manual, tidak ada biaya tersembunyi. Satu kali bayar berlaku untuk satu event sampai selesai.
                </div>
            </div>
        </div>
    </div>
</div>
