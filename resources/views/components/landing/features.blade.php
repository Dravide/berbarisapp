@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $title = $data['title'] ?? 'Fitur Lengkap untuk Event Sukses';
    $items = $data['items'] ?? [
        ['icon' => 'icon3.png', 'title' => 'Manajemen Pendaftaran', 'description' => 'Kelola pendaftaran peserta secara digital dengan verifikasi otomatis dan tracking status real-time.'],
        ['icon' => 'icon6.png', 'title' => 'E-Tiket & Pembayaran', 'description' => 'Jual tiket event secara online dengan integrasi gateway pembayaran dan QR code check-in.'],
        ['icon' => 'icon5.png', 'title' => 'Voting Online', 'description' => 'Fitur voting online terintegrasi dengan pembayaran digital untuk penghargaan favorit penonton.'],
        ['icon' => 'icon4.png', 'title' => 'Penilaian Juri Digital', 'description' => 'Sistem penilaian digital dengan format kustom, perhitungan otomatis, dan rekap nilai instan.'],
        ['icon' => 'icon7.png', 'title' => 'Live Scoreboard', 'description' => 'Papan skor real-time yang bisa dipancarkan ke layar proyektor untuk transparansi penilaian.'],
        ['icon' => 'icon8.png', 'title' => 'Drawing & Undian', 'description' => 'Sistem undian digital untuk menentukan urutan tampil peserta dengan animasi menarik.'],
    ];
    $iconMap = [
        'icon3.png' => 'ti-chart-bar',
        'icon4.png' => 'ti-shield-lock',
        'icon5.png' => 'ti-clipboard-list',
        'icon6.png' => 'ti-device-mobile',
        'icon7.png' => 'ti-cash',
        'icon8.png' => 'ti-plug-connected',
    ];
@endphp

<section id="features" class="section-pad bg-surface">
    <div class="container-landing">
        <div class="mx-auto max-w-2xl text-center">
            <span class="overline justify-center">Fitur</span>
            <h2 class="mt-4 text-3xl font-bold md:text-4xl">{{ $title }}</h2>
            <p class="mt-4 text-on-surface-variant">Semua yang Anda butuhkan untuk menyelenggarakan event &amp; kompetisi dalam satu platform terpadu.</p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($items as $index => $item)
            <div class="surface-card surface-card-hover group p-7" wire:key="feature-{{ $index }}">
                <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary transition group-hover:bg-secondary group-hover:text-deep-slate">
                    @if(!empty($item['icon_custom']))
                        <img src="{{ Storage::url($item['icon_custom']) }}" alt="{{ $item['title'] ?? '' }}" class="h-7 w-7 object-contain">
                    @else
                        <i class="ti {{ $iconMap[$item['icon'] ?? ''] ?? 'ti-star' }} text-2xl"></i>
                    @endif
                </div>
                <h3 class="text-lg font-bold text-deep-slate">{{ $item['title'] ?? '' }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-on-surface-variant">{{ $item['description'] ?? '' }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>
