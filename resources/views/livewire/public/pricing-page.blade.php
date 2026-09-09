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
        <div class="mt-12 grid grid-cols-1 items-stretch justify-center gap-6 md:grid-cols-2">
            {{-- ================= Paket Gratis ================= --}}
            <div class="surface-card flex flex-col p-8">
                <h3 class="text-lg font-bold text-deep-slate">Gratis</h3>
                <p class="mt-1 text-sm text-on-surface-variant">Untuk mulai mengelola lomba</p>
                <div class="mt-5">
                    <span class="font-display text-4xl font-extrabold text-deep-slate">Rp 0</span>
                </div>
                <ul class="mt-6 flex flex-1 flex-col gap-3 text-sm">
                    <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> Dashboard event & profil</li>
                    <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> Kategori lomba & pendaftaran peserta</li>
                    <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> Manajemen juri & input nilai</li>
                    <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> Rekap nilai & scoreboard publik</li>
                    <li class="flex items-center gap-2"><i class="ti ti-check text-secondary"></i> QR check-in peserta</li>
                </ul>
                <div class="mt-8">
                    @auth
                        @if(auth()->user()->role === 'Eventner' && auth()->user()->eventner?->plan !== 'paid')
                            <a href="{{ route('eventner.billing.upgrade') }}" class="btn-ghost w-full justify-center">Kelola Paket</a>
                        @else
                            <a href="{{ route('dashboard') }}" class="btn-ghost w-full justify-center">Ke Dashboard</a>
                        @endif
                    @else
                        <a href="{{ route('register.eventner') }}?plan=free" class="btn-ghost w-full justify-center">Daftar Gratis</a>
                    @endauth
                </div>
            </div>

            {{-- ================= Paket Event Penuh ================= --}}
            <div class="surface-card relative flex flex-col overflow-hidden border-2 border-secondary p-8">
                <span class="absolute top-4 right-4 rounded-full bg-secondary px-3 py-1 text-xs font-bold text-deep-slate">Rekomendasi</span>
                <h3 class="text-lg font-bold text-deep-slate">Event Penuh</h3>
                <p class="mt-1 text-sm text-on-surface-variant">Bayar sekali, aktif selama event</p>
                <div class="mt-5">
                    <span class="font-display text-4xl font-extrabold text-primary">Rp {{ number_format($planPrice, 0, ',', '.') }}</span>
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
                    @auth
                        @if(auth()->user()->role === 'Eventner' && auth()->user()->eventner?->plan !== 'paid')
                            <a href="{{ route('eventner.billing.upgrade') }}" class="btn-primary w-full justify-center"><i class="ti ti-bolt"></i> Upgrade Sekarang</a>
                        @elseif(auth()->user()->role === 'Eventner')
                            <span class="btn-primary pointer-events-none w-full justify-center opacity-60"><i class="ti ti-circle-check"></i> Sudah Aktif</span>
                        @else
                            <a href="{{ route('dashboard') }}" class="btn-primary w-full justify-center">Ke Dashboard</a>
                        @endif
                    @else
                        <a href="{{ route('register.eventner') }}" class="btn-primary w-full justify-center">Mulai Sekarang</a>
                    @endauth
                </div>
            </div>
        </div>

        {{-- Info pembayaran --}}
        <div class="mx-auto mt-10 max-w-2xl">
            <div class="surface-card flex items-start gap-3 p-5 border border-primary/20">
                <i class="ti ti-info-circle shrink-0 text-xl text-primary"></i>
                <div class="text-sm text-on-surface-variant">
                    <span class="font-bold text-deep-slate">Cara kerja pembayaran:</span>
                    Pilih upgrade → scan QRIS → aktivasi otomatis dalam hitungan detik setelah pembayaran terkonfirmasi. Tidak ada verifikasi manual, tidak ada biaya tersembunyi. Satu kali bayar berlaku untuk satu event sampai selesai.
                </div>
            </div>
        </div>
    </div>
</div>