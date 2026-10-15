@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $title = $data['title'] ?? 'Fitur Lengkap untuk Event Sukses';
    $items = $data['items'] ?? [
        ['icon' => 'icon3.png', 'title' => 'Manajemen Pendaftaran', 'description' => 'Kelola pendaftaran peserta secara digital dengan verifikasi otomatis dan tracking status real-time.'],
        ['icon' => 'icon4.png', 'title' => 'Penilaian Juri Digital', 'description' => 'Sistem penilaian digital dengan format kustom, perhitungan otomatis, dan rekap nilai instan.'],
        ['icon' => 'icon5.png', 'title' => 'Voting Online', 'description' => 'Fitur voting online terintegrasi dengan pembayaran digital untuk penghargaan favorit penonton.'],
        ['icon' => 'icon6.png', 'title' => 'E-Tiket & Check-in QR', 'description' => 'Jual tiket event secara online dengan integrasi gateway pembayaran dan QR code check-in.'],
        ['icon' => 'icon7.png', 'title' => 'Live Scoreboard', 'description' => 'Papan skor real-time yang bisa dipancarkan ke layar proyektor untuk transparansi penilaian.'],
        ['icon' => 'icon8.png', 'title' => 'Drawing & Undian', 'description' => 'Sistem undian digital untuk menentukan urutan tampil peserta dengan animasi menarik.'],
        ['icon' => 'icon9.png', 'title' => 'Sertifikat Digital', 'description' => 'Cetak sertifikat peserta dan juara otomatis lengkap dengan tanda tangan digital penyelenggara.'],
        ['icon' => 'icon10.png', 'title' => 'Livestream Overlay', 'description' => 'Overlay skor dan nama peserta untuk siaran langsung di YouTube maupun Facebook.'],
        ['icon' => 'icon11.png', 'title' => 'Sponsor & Tenant', 'description' => 'Kelola sponsor, stan tenant, dan peta lokasi venue dalam satu halaman publik event.'],
    ];

    // Ikon dipilih dari arti fiturnya. Tiga entri terakhir sempat hilang dan
    // membuat Sertifikat/Livestream/Sponsor semuanya jatuh ke `ti-star` yang
    // sama; `icon7` juga dulu `ti-scoreboard`, yang tidak ada glifnya di font
    // Tabler lokal yang dipakai halaman ini — hasilnya ikon kosong begitu
    // CDN gagal dimuat. `ti-podium` ada di font lokal maupun CDN.
    $iconMap = [
        'icon3.png' => 'ti-forms',          // pendaftaran = formulir
        'icon4.png' => 'ti-award',          // penilaian = penghargaan
        'icon5.png' => 'ti-star',           // voting = bintang
        'icon6.png' => 'ti-ticket',         // tiket = tiket
        'icon7.png' => 'ti-podium',         // skor = podium
        'icon8.png' => 'ti-list-numbers',   // undian = nomor urut
        'icon9.png' => 'ti-certificate',    // sertifikat
        'icon10.png' => 'ti-broadcast',     // overlay siaran
        'icon11.png' => 'ti-building-store', // sponsor & tenant = stan
    ];

    // Unggulan ditandai per ITEM (`featured`), bukan per indeks: indeks
    // bergeser tiap admin menghapus satu baris (removeFeatureItem memanggil
    // array_values), jadi penanda berbasis indeks akan menunjuk item yang
    // salah setelah satu penghapusan. Dibandingkan lewat KUNCI indeks, bukan
    // lewat isi item: dua item bisa saja punya judul+deskripsi+ikon identik
    // (admin menduplikasi), dan pencocokan isi akan membuang yang salah.
    // Data lama belum punya kunci ini sama sekali — kalau tidak ada yang
    // ditandai, dua item pertama yang jadi unggulan, sehingga halaman depan
    // membaik tanpa admin menyentuh apa pun.
    $featuredKeys = collect($items)->filter(fn ($i) => !empty($i['featured']))->keys()->take(2);
    if ($featuredKeys->isEmpty()) {
        $featuredKeys = collect($items)->keys()->take(2);
    }
    $compactKeys  = collect($items)->keys()->diff($featuredKeys);
    $compactCount = $compactKeys->count();

    // Poin dari section "Tentang" yang dibuang. Dulu tiga blok ceklis
    // tersendiri beberapa layar di bawah section ini dan mengulang hal yang
    // sama; sekarang jadi satu baris chip di bawah judul.
    $about = json_decode(\App\Models\Setting::get('landing_about') ?? 'null', true) ?? [];
    $poin = collect($about['points'] ?? [])
        ->pluck('title')
        ->filter()
        ->take(4);
@endphp

<section id="features" class="section-pad bg-surface">
    <div class="container-landing">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold md:text-4xl">{{ $title }}</h2>
            <p class="mt-4 text-on-surface-variant">Semua yang Anda butuhkan untuk menyelenggarakan event &amp; kompetisi dalam satu platform terpadu.</p>

            @if($poin->isNotEmpty())
            <ul class="mt-6 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm font-medium text-deep-slate">
                @foreach($poin as $p)
                <li class="inline-flex items-center gap-1.5">
                    <i class="ti ti-check text-secondary"></i>
                    {{ $p }}
                </li>
                @endforeach
            </ul>
            @endif
        </div>

        {{-- Bento enam kolom: unggulan 3+3, padat 2+2+2, kartu terakhir yang
             sendirian melebar penuh (6). Kelas span ditulis LITERAL di tiap
             cabang — Tailwind memindai berkas dan tidak pernah menjalankan
             Blade, jadi kelas yang dirakit dari variabel tidak masuk CSS
             hasil build (pelajaran yang sudah dibayar di komponen harga). --}}
        <div class="mt-12 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-6">
            @foreach($featuredKeys as $index)
            @php $item = $items[$index] ?? []; @endphp
            <div class="surface-card surface-card-hover p-6 sm:col-span-2 lg:col-span-3" wire:key="feature-{{ $index }}">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        @if(!empty($item['icon_custom']))
                            <img src="{{ Storage::url($item['icon_custom']) }}" alt="{{ $item['title'] ?? '' }}" class="h-6 w-6 object-contain">
                        @else
                            <i class="ti {{ $iconMap[$item['icon'] ?? ''] ?? 'ti-star' }} text-xl"></i>
                        @endif
                    </div>
                    <span class="chip">Paling dipakai</span>
                </div>

                <x-landing.feature-miniature :item="$item" />

                <h3 class="text-lg font-bold text-deep-slate">{{ $item['title'] ?? '' }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-on-surface-variant">{{ $item['description'] ?? '' }}</p>
            </div>
            @endforeach

            @foreach($compactKeys as $index)
            @php
                $item = $items[$index] ?? [];
                // Sisa kartu padat yang habis dibagi tiga menyisakan satu:
                // yang terakhir melebar penuh, bukan menggantung separuh baris.
                $sendiri = $loop->last && $compactCount % 3 === 1;
            @endphp
            @if($sendiri)
            <div class="surface-card surface-card-hover p-6 sm:col-span-2 lg:col-span-6" wire:key="feature-{{ $index }}">
            @else
            <div class="surface-card surface-card-hover p-6 sm:col-span-1 lg:col-span-2" wire:key="feature-{{ $index }}">
            @endif
                <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    @if(!empty($item['icon_custom']))
                        <img src="{{ Storage::url($item['icon_custom']) }}" alt="{{ $item['title'] ?? '' }}" class="h-6 w-6 object-contain">
                    @else
                        <i class="ti {{ $iconMap[$item['icon'] ?? ''] ?? 'ti-star' }} text-xl"></i>
                    @endif
                </div>

                <x-landing.feature-miniature :item="$item" />

                <h3 class="text-lg font-bold text-deep-slate">{{ $item['title'] ?? '' }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-on-surface-variant">{{ $item['description'] ?? '' }}</p>
            </div>
            @endforeach
        </div>

        {{-- Landing cuma memuat sembilan fitur; sisanya hidup di /fitur. --}}
        <div class="mt-10 text-center">
            <a href="{{ route('public.features') }}" class="btn-ghost">
                Lihat Semua Fitur
                <i class="ti ti-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
