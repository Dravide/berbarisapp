@php
    $data = json_decode($section?->content ?? 'null', true) ?? [];
    $title = $data['title'] ?? 'Harga & Paket';
    $subtitle = $data['subtitle'] ?? 'Kelola perlombaan sekolah dengan gratis. Aktifkan fitur premium sekali bayar per event — tanpa langganan bulanan.';

    $planPrice = \App\Support\Pricing::planPrice();
    $regFee = \App\Support\Pricing::registrationFee();
    $premiumFeatures = \App\Support\Pricing::premiumFeatures();

    // CTA per kartu — auth eventner free → upgrade, lainnya → daftar
    $user = auth()->user();
    $isFreeEventner = $user && $user->role === 'Eventner' && $user->eventner && $user->eventner->plan !== 'paid';
    $freeCtaUrl = $user ? route('dashboard') : route('register.eventner') . '?plan=free';
    $freeCtaLabel = $user ? 'Ke Dashboard' : 'Daftar Gratis';
    $paidCtaUrl = $isFreeEventner ? route('eventner.billing.upgrade') : route('register.eventner');
@endphp

<section id="pricing" class="section-pad bg-surface">
    <div class="container-landing">
        <div class="mx-auto max-w-2xl text-center">
            <span class="overline justify-center">Harga</span>
            <h2 class="mt-4 text-3xl font-bold md:text-4xl">{{ $title }}</h2>
            <p class="mt-4 text-on-surface-variant">{{ $subtitle }}</p>
        </div>

        <div class="mt-12 grid grid-cols-1 items-stretch justify-center gap-6 md:grid-cols-2">
            {{-- Paket Gratis --}}
            <div class="surface-card flex flex-col p-8">
                <h3 class="text-lg font-bold text-deep-slate">Gratis</h3>
                <p class="mt-1 text-sm text-on-surface-variant">Untuk mulai mengelola lomba</p>
                <div class="mt-5">
                    <span class="text-4xl font-extrabold text-deep-slate">Rp 0</span>
                </div>
                <ul class="mt-6 flex flex-1 flex-col gap-3 text-sm">
                    <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> Dashboard event & profil</li>
                    <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> Kategori lomba & pendaftaran peserta</li>
                    <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> Manajemen juri & input nilai</li>
                    <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> Rekap nilai & scoreboard publik</li>
                    <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> QR check-in peserta</li>
                </ul>
                <div class="mt-8">
                    <a href="{{ $freeCtaUrl }}" class="btn-ghost w-full justify-center">{{ $freeCtaLabel }}</a>
                </div>
            </div>

            {{-- Paket Event Penuh --}}
            <div class="surface-card relative flex flex-col overflow-hidden border-2 border-secondary p-8">
                <span class="absolute top-4 right-4 rounded-full bg-secondary px-3 py-1 text-xs font-bold text-deep-slate">Rekomendasi</span>
                <h3 class="text-lg font-bold text-deep-slate">Event Penuh</h3>
                <p class="mt-1 text-sm text-on-surface-variant">Bayar sekali, aktif selama event</p>
                <div class="mt-5">
                    <span class="text-4xl font-extrabold text-primary">Rp {{ number_format($planPrice, 0, ',', '.') }}</span>
                    @if($regFee > 0)
                        <p class="mt-1 text-xs text-on-surface-variant">+ biaya pendaftaran Rp {{ number_format($regFee, 0, ',', '.') }}</p>
                    @endif
                </div>
                <ul class="mt-6 flex flex-1 flex-col gap-3 text-sm">
                    <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> Semua fitur paket gratis</li>
                    @foreach($premiumFeatures as $feature)
                        <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> {{ $feature['label'] }}</li>
                    @endforeach
                    <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> Aktivasi otomatis setelah bayar</li>
                </ul>
                <div class="mt-8">
                    @if($isFreeEventner)
                        <a href="{{ $paidCtaUrl }}" class="btn-primary w-full justify-center"><i class="ti ti-bolt"></i> Upgrade Sekarang</a>
                    @elseif($user && $user->role === 'Eventner')
                        <span class="btn-primary w-full justify-center opacity-60 pointer-events-none"><i class="ti ti-circle-check"></i> Sudah Aktif</span>
                    @else
                        <a href="{{ $paidCtaUrl }}" class="btn-primary w-full justify-center">Mulai Sekarang</a>
                    @endif
                </div>
            </div>
        </div>

        <p class="mt-8 text-center text-sm text-on-surface-variant">
            <i class="ti ti-info-circle me-1"></i> Bayar via QRIS — aktivasi otomatis, tanpa verifikasi manual, tanpa biaya tersembunyi.
        </p>
    </div>
</section>