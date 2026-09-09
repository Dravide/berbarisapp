@php
    $data = json_decode($section?->content ?? 'null', true) ?? [];
    $title = $data['title'] ?? 'Harga & Paket';
    $subtitle = $data['subtitle'] ?? 'Kelola perlombaan sekolah dengan gratis. Aktifkan fitur premium sekali bayar per event — tanpa langganan bulanan.';

    $plans = \App\Support\Pricing::plans();

    // CTA per kartu — auth eventner free → upgrade, lainnya → daftar
    $user = auth()->user();
    $eventner = $user && $user->role === 'Eventner' ? $user->eventner : null;
    $hasPaid = $eventner && ($eventner->plan === 'paid' || $eventner->registration_paid_at);
@endphp

<section id="pricing" class="section-pad bg-surface">
    <div class="container-landing">
        <div class="mx-auto max-w-2xl text-center">
            <span class="overline justify-center">Harga</span>
            <h2 class="mt-4 text-3xl font-bold md:text-4xl">{{ $title }}</h2>
            <p class="mt-4 text-on-surface-variant">{{ $subtitle }}</p>
        </div>

        <div class="mt-12 grid grid-cols-1 items-stretch justify-center gap-6 md:grid-cols-2 lg:grid-cols-{{ count($plans) > 3 ? 3 : count($plans) }}">
            @foreach($plans as $plan)
                @php
                    $isOwned = $eventner && $eventner->saas_plan_id === $plan['id'];
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
                            <span class="text-4xl font-extrabold text-deep-slate">Rp 0</span>
                        @else
                            <span class="text-4xl font-extrabold text-primary">Rp {{ number_format($plan['price'], 0, ',', '.') }}</span>
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
                            <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> Aktivasi otomatis setelah bayar</li>
                        @endif
                    </ul>
                    <div class="mt-8">
                        @auth
                            @if($eventner)
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
                        @else
                            <a href="{{ route('register.eventner') }}{{ $plan['is_free'] ? '?plan=free' : '?plan=' . $plan['slug'] }}"
                                class="{{ $plan['highlight'] ? 'btn-primary' : 'btn-ghost' }} w-full justify-center">
                                {{ $plan['is_free'] ? 'Daftar Gratis' : 'Mulai Sekarang' }}
                            </a>
                        @endauth
                    </div>
                </div>
            @endforeach
        </div>

        <p class="mt-8 text-center text-sm text-on-surface-variant">
            <i class="ti ti-info-circle me-1"></i> Bayar via QRIS — aktivasi otomatis, tanpa verifikasi manual, tanpa biaya tersembunyi.
        </p>
    </div>
</section>
