@php
    $data = json_decode($section?->content ?? 'null', true) ?? [];
    $title = $data['title'] ?? 'Harga & Paket';
    $subtitle = $data['subtitle'] ?? 'Kelola perlombaan sekolah dengan gratis. Aktifkan fitur premium sekali bayar per event — tanpa langganan bulanan.';

    $plans = \App\Support\Pricing::plans();

    // CTA per kartu — auth eventner free → upgrade, lainnya → daftar
    $user = auth()->user();
    $eventner = $user && $user->role === 'Eventner' ? $user->eventner : null;
    $hasPaid = $eventner && $eventner->hasActivePlan();

    // Kelas kolom ditulis sebagai literal, bukan dirakit dari count().
    // Nama kelas yang disusun saat render tidak terlihat pemindai Tailwind,
    // jadi dulu `lg:grid-cols-2` hanya kebetulan ada di CSS hasil build karena
    // dipakai berkas lain — jumlah paket yang berbeda akan menghasilkan nama
    // kelas yang tidak pernah ada di CSS sama sekali.
    $jumlahPaket = count($plans);
    $kolomGrid = match (true) {
        $jumlahPaket >= 3 => 'md:grid-cols-2 lg:grid-cols-3',
        $jumlahPaket === 2 => 'md:grid-cols-2',
        default => '',
    };

    // Dua kartu kalau dibiarkan selebar 1280px jadi dua kotak melebar yang
    // isinya menggantung di kiri. Dibatasi supaya perbandingannya terbaca.
    $lebarGrid = $jumlahPaket <= 2 ? 'mx-auto max-w-4xl' : '';
@endphp

<section id="pricing" class="section-pad bg-surface-container-low">
    <div class="container-landing">
        <div class="mx-auto max-w-2xl text-center">
            <span class="overline justify-center">Harga</span>
            <h2 class="mt-4 text-3xl font-bold md:text-4xl">{{ $title }}</h2>
            <p class="mt-4 text-on-surface-variant">{{ $subtitle }}</p>
        </div>

        <div class="mt-12 grid grid-cols-1 items-stretch gap-6 {{ $kolomGrid }} {{ $lebarGrid }}">
            @foreach($plans as $plan)
                @php
                    $isOwned = $eventner && $eventner->saas_plan_id === $plan['id'];

                    // Kartu rekomendasi memakai panel gelap, bukan sekadar bingkai
                    // tipis: pilihan yang disarankan jadi terbaca sekali lihat.
                    // Warna cek, harga, dan tautan di dalamnya ikut menyesuaikan
                    // supaya tetap punya kontras di atas latar gelap — dan cek
                    // paket gratis memakai biru, bukan lime, karena lime di atas
                    // putih nyaris tidak terbaca.
                    $rekomendasi = $plan['highlight'];
                    $kartu = $rekomendasi
                        ? 'border border-white/10 bg-deep-slate shadow-[0_24px_60px_rgba(17,24,39,0.28)]'
                        : 'surface-card';
                    $judulKelas = $rekomendasi ? 'text-white' : 'text-deep-slate';
                    $hargaKelas = $rekomendasi ? 'text-white' : 'text-primary';
                    $redupKelas = $rekomendasi ? 'text-white/65' : 'text-on-surface-variant';
                    $teksKelas = $rekomendasi ? 'text-white/85' : 'text-on-surface';
                    $cekKelas = $rekomendasi ? 'bg-secondary/20 text-secondary' : 'bg-primary/10 text-primary';
                    $tautanKelas = $rekomendasi ? 'text-secondary' : 'text-primary';
                    $garisKelas = $rekomendasi ? 'border-white/15' : 'border-outline-variant/50';
                    $tombolRedup = $rekomendasi ? 'btn-ghost-light' : 'btn-ghost';

                    // Daftar fitur dipadatkan: sisanya disembunyikan di balik toggle
                    // supaya kartu tidak memanjang ke bawah saat semua fitur aktif.
                    if ($plan['is_free']) {
                        $features = [
                            'Dashboard event & profil',
                            'Kategori lomba & pendaftaran peserta',
                            'Manajemen juri & input nilai',
                            'Rekap nilai & scoreboard publik',
                            'QR check-in peserta',
                        ];
                    } else {
                        $features = array_merge(
                            ['Semua fitur paket gratis'],
                            array_map(
                                fn ($key) => config("eventner_features.{$key}.label", $key),
                                $plan['features']
                            ),
                            [$plan['is_contact'] ? 'Aktivasi oleh admin setelah konfirmasi' : 'Aktivasi otomatis setelah bayar']
                        );
                    }

                    $visibleFeatures = array_slice($features, 0, 5);
                    $hiddenFeatures = array_slice($features, 5);
                @endphp
                <div class="{{ $kartu }} relative flex flex-col rounded-2xl p-7 md:p-8">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-bold {{ $judulKelas }}">{{ $plan['name'] }}</h3>
                            @if($plan['description'])
                                <p class="mt-1 text-sm {{ $redupKelas }}">{{ $plan['description'] }}</p>
                            @endif
                        </div>
                        @if($rekomendasi)
                            <span class="shrink-0 rounded-full bg-secondary px-3 py-1 text-xs font-bold text-deep-slate">Rekomendasi</span>
                        @endif
                    </div>

                    <div class="mt-6 border-b pb-6 {{ $garisKelas }}">
                        @if($plan['is_free'])
                            <span class="text-4xl font-extrabold {{ $judulKelas }}">Rp 0</span>
                        @elseif($plan['is_contact'])
                            <span class="text-4xl font-extrabold {{ $hargaKelas }}">Kustom</span>
                            <p class="mt-1 text-xs {{ $redupKelas }}">Harga disepakati bersama admin</p>
                        @else
                            <span class="text-4xl font-extrabold {{ $hargaKelas }}">Rp {{ number_format($plan['price'], 0, ',', '.') }}</span>
                            <p class="mt-1 text-xs {{ $redupKelas }}">Sekali bayar per event</p>
                        @endif
                    </div>

                    <ul class="mt-6 flex flex-1 flex-col gap-3 text-sm {{ $teksKelas }}">
                        @foreach($visibleFeatures as $feature)
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full {{ $cekKelas }}"><i class="ti ti-check text-xs"></i></span>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>

                    @if($hiddenFeatures)
                        <details class="pricing-more mt-4 text-sm {{ $teksKelas }}">
                            <summary class="inline-flex cursor-pointer list-none items-center gap-1 font-semibold {{ $tautanKelas }} hover:underline">
                                <i class="ti ti-chevron-down transition-transform"></i>
                                <span class="pricing-more-open">Lihat {{ count($hiddenFeatures) }} fitur lain</span>
                                <span class="pricing-more-close">Sembunyikan fitur</span>
                            </summary>
                            <ul class="mt-3 flex flex-col gap-3">
                                @foreach($hiddenFeatures as $feature)
                                    <li class="flex items-start gap-3">
                                        <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full {{ $cekKelas }}"><i class="ti ti-check text-xs"></i></span>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif

                    <div class="mt-8">
                        @if($plan['is_contact'])
                            <a href="{{ $plan['contact_url'] ?: '#contact' }}" target="_blank" rel="noopener"
                                class="{{ $rekomendasi ? 'btn-primary' : 'btn-ghost' }} w-full justify-center">
                                <i class="ti ti-message-circle"></i> Hubungi Admin
                            </a>
                        @elseif(!auth()->check())
                            <a href="{{ route('register.eventner') }}?plan={{ urlencode($plan['slug']) }}"
                                class="{{ $rekomendasi ? 'btn-primary' : 'btn-ghost' }} w-full justify-center">
                                {{ $plan['is_free'] ? 'Daftar Gratis' : 'Mulai Sekarang' }}
                            </a>
                        @elseif($eventner)
                            @if($isOwned && $hasPaid)
                                <span class="btn-primary pointer-events-none w-full justify-center opacity-60"><i class="ti ti-circle-check"></i> Paket Anda</span>
                            @elseif($isOwned)
                                <a href="{{ route('eventner.billing.upgrade') }}" class="btn-primary w-full justify-center"><i class="ti ti-bolt"></i> Aktifkan Sekarang</a>
                            @elseif($hasPaid)
                                <a href="{{ route('dashboard') }}" class="{{ $tombolRedup }} w-full justify-center">Ke Dashboard</a>
                            @else
                                <a href="{{ route('eventner.billing.upgrade') }}" class="{{ $rekomendasi ? 'btn-primary' : 'btn-ghost' }} w-full justify-center">Pilih Paket Ini</a>
                            @endif
                        @else
                            <a href="{{ route('dashboard') }}" class="{{ $rekomendasi ? 'btn-primary' : 'btn-ghost' }} w-full justify-center">Ke Dashboard</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-10 flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-sm text-on-surface-variant">
            <span class="inline-flex items-center gap-2"><i class="ti ti-qrcode text-primary"></i> Bayar via QRIS</span>
            <span class="inline-flex items-center gap-2"><i class="ti ti-bolt text-primary"></i> Aktivasi otomatis setelah pembayaran</span>
            <span class="inline-flex items-center gap-2"><i class="ti ti-shield-check text-primary"></i> Tanpa verifikasi manual &amp; biaya tersembunyi</span>
        </div>
    </div>
</section>
