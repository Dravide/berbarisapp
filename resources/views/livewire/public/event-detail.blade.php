<div class="min-h-screen bg-surface">

    {{-- ========== HEADER BANNER — landscape wide image, separate from A4 poster ========== --}}
    <div class="container-landing pt-6">
        @if($eventner->header_banner)
            <div class="relative w-full aspect-[21/9] md:aspect-[3/1] rounded-2xl overflow-hidden border border-outline-variant/30 bg-black/5 shadow-[0_1px_3px_rgba(17,24,39,0.07)]">
                <img src="{{ asset('storage/' . $eventner->header_banner) }}" alt="Banner {{ $eventner->nama_event }}" class="w-full h-full object-cover">
            </div>
        @elseif($eventner->poster)
            {{-- Fallback: pakai poster A4 — klik untuk perbesar / zoom --}}
            <div class="relative w-full aspect-[21/9] md:aspect-[3/1] rounded-2xl overflow-hidden border border-outline-variant/30 bg-black/5 shadow-[0_1px_3px_rgba(17,24,39,0.07)]">
                <button type="button"
                        @click="$dispatch('open-poster-zoom', { src: '{{ asset('storage/' . $eventner->poster) }}', title: 'Poster {{ addslashes($eventner->nama_event) }}' })"
                        class="w-full h-full block text-left group focus:outline-none relative cursor-zoom-in"
                        title="Klik untuk memperbesar poster">
                    <img src="{{ asset('storage/' . $eventner->poster) }}" alt="Poster {{ $eventner->nama_event }}" class="w-full h-full object-cover group-hover:scale-[1.01] transition-transform duration-200">
                    <div class="absolute bottom-3 right-3 inline-flex items-center gap-1.5 rounded-lg bg-slate-900/80 backdrop-blur-sm px-3 py-1.5 text-xs font-semibold text-white shadow-sm opacity-90 group-hover:opacity-100 transition-opacity">
                        <i class="ti ti-zoom-in text-sm"></i>
                        <span>Perbesar Poster</span>
                    </div>
                </button>
            </div>
        @else
            {{-- Tanpa aset visual: kanvas netral polos. Dulu gradien biru-ke-sian
                 lengkap dengan dua orb blur dekoratif (R-01/R-13) — sekarang
                 permukaan sistem, sama seperti placeholder poster di landing. --}}
            <div class="h-36 w-full rounded-2xl bg-surface-container-high md:h-48"></div>
        @endif
    </div>

    {{-- ========== HEADER INFO BLOCK ========== --}}
    <div class="container-landing pt-6">
        <div class="grid gap-6 md:grid-cols-3 items-center">
            {{-- Logo & Title --}}
            <div class="md:col-span-2 flex items-start gap-4">
                @if($eventner->logo_event)
                    <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="h-16 w-16 md:h-20 md:w-20 rounded-2xl object-cover shadow-sm border border-outline-variant/30 shrink-0" alt="{{ $eventner->nama_event }}">
                @else
                    <div class="flex h-16 w-16 md:h-20 md:w-20 items-center justify-center rounded-2xl bg-primary/10 text-primary border border-outline-variant/30 shrink-0">
                        <i class="ti ti-calendar-event text-3xl"></i>
                    </div>
                @endif
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        @if($eventner->tingkat_perlombaan)
                            {{-- Chip tingkat: label kategori, bukan dekorasi — ikon medal dihapus --}}
                            <span class="inline-flex items-center rounded-full bg-primary/10 px-3 py-0.5 text-[10px] font-bold uppercase tracking-wider text-primary border border-primary/20">
                                {{ $eventner->tingkat_perlombaan }}
                            </span>
                        @endif
                        @if($eventner->link_livestreaming)
                            <a href="{{ $eventner->link_livestreaming }}" target="_blank" class="badge-live text-decoration-none">
                                Live Streaming
                            </a>
                        @endif
                    </div>
                    <h1 class="font-display text-2xl font-extrabold tracking-tight text-deep-slate leading-tight sm:text-3xl">
                        {{ $eventner->nama_event }}
                    </h1>
                    <p class="mt-2 text-sm text-on-surface-variant flex items-center gap-1.5 flex-wrap">
                        <span>Diselenggarakan oleh</span>
                        <span class="font-semibold text-deep-slate inline-flex items-center gap-1">
                            {{ $eventner->diselenggarakan_oleh }}
                            @if($eventner->is_verified)
                                <x-verified-badge size="sm" />
                            @endif
                        </span>
                    </p>
                    {{-- Tautan sosial: satu pill netral untuk semua (dulu tiga warna
                         brand berbeda — pink/hitam/hijau — R-29 palet menyebar). --}}
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        @if($eventner->link_instagram)
                            <a href="{{ $eventner->link_instagram }}" target="_blank" class="inline-flex items-center gap-1 rounded-full bg-surface-container px-3 py-1 text-[11px] font-semibold text-deep-slate border border-outline-variant/60 hover:border-primary/40 transition-colors text-decoration-none">
                                <i class="ti ti-brand-instagram"></i> Instagram
                            </a>
                        @endif
                        @if($eventner->link_tiktok)
                            <a href="{{ $eventner->link_tiktok }}" target="_blank" class="inline-flex items-center gap-1 rounded-full bg-surface-container px-3 py-1 text-[11px] font-semibold text-deep-slate border border-outline-variant/60 hover:border-primary/40 transition-colors text-decoration-none">
                                <i class="ti ti-brand-tiktok"></i> TikTok
                            </a>
                        @endif
                        @if($eventner->link_whatsapp)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $eventner->link_whatsapp) }}" target="_blank" class="inline-flex items-center gap-1 rounded-full bg-surface-container px-3 py-1 text-[11px] font-semibold text-deep-slate border border-outline-variant/60 hover:border-primary/40 transition-colors text-decoration-none">
                                <i class="ti ti-brand-whatsapp"></i> WhatsApp
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Countdown Timer --}}
            @if($eventner->tanggal && \Carbon\Carbon::parse($eventner->tanggal_akhir ?? $eventner->tanggal)->isFuture())
                <div class="md:col-span-1" x-data="countdown('{{ \Carbon\Carbon::parse($eventner->tanggal_akhir ?? $eventner->tanggal)->toIso8601String() }}')">
                    <div class="surface-card p-4">
                        <span class="mb-3 block text-center text-[10px] font-semibold uppercase tracking-wider text-on-surface-variant">Menuju Hari H Perlombaan</span>
                        <div class="grid grid-cols-4 gap-2 max-w-[280px] mx-auto">
                            <div class="flex flex-col bg-surface-container-low rounded-xl p-1.5 min-w-0 items-center justify-center">
                                <span class="font-display text-base font-extrabold text-deep-slate leading-tight" x-text="days"></span>
                                <span class="text-[8px] text-on-surface-variant uppercase font-semibold tracking-wider mt-0.5">Hari</span>
                            </div>
                            <div class="flex flex-col bg-surface-container-low rounded-xl p-1.5 min-w-0 items-center justify-center">
                                <span class="font-display text-base font-extrabold text-deep-slate leading-tight" x-text="hours"></span>
                                <span class="text-[8px] text-on-surface-variant uppercase font-semibold tracking-wider mt-0.5">Jam</span>
                            </div>
                            <div class="flex flex-col bg-surface-container-low rounded-xl p-1.5 min-w-0 items-center justify-center">
                                <span class="font-display text-base font-extrabold text-deep-slate leading-tight" x-text="minutes"></span>
                                <span class="text-[8px] text-on-surface-variant uppercase font-semibold tracking-wider mt-0.5">Mnt</span>
                            </div>
                            <div class="flex flex-col bg-surface-container-low rounded-xl p-1.5 min-w-0 items-center justify-center">
                                <span class="font-display text-base font-extrabold text-deep-slate leading-tight" x-text="seconds"></span>
                                <span class="text-[8px] text-on-surface-variant uppercase font-semibold tracking-wider mt-0.5">Det</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ========== QUICK INFO CARD ========== --}}
    {{-- Satu tempat untuk seluruh jadwal & lokasi: hari H, technical meeting,
         batas pendaftaran, tempat, tingkat, dan kuota. Dulu empat medan ini
         terbagi ke dua kartu berbeda — kartu ini dan "Informasi Jadwal" di
         sidebar — sehingga pengunjung harus menggabungkan sendiri. Di mobile
         sidebar menumpuk paling bawah, jadi batas pendaftaran beserta hitung
         mundurnya justru muncul terakhir, padahal itu yang paling menentukan
         keputusan mendaftar. --}}
    {{-- Tingkat yang bisa dipilih: anak, plus induk lama tanpa anak. Induk
         beranak tidak punya kuota sendiri, jadi menjumlahkannya cuma
         menggandakan. Dipakai kartu ini dan bagian "Kategori Lomba". --}}
    @php
        $parents = $eventner->competitionCategories->whereNull('parent_id')->sortBy('sort_order');
        $children = $eventner->competitionCategories->whereNotNull('parent_id');
        $daftarTingkat = $children->merge($parents->where(fn ($p) => $p->children->isEmpty()));
        $totalKuota = $daftarTingkat->sum('kuota');
        $totalReg = $daftarTingkat->sum(fn ($c) => $c->registrations->count());
    @endphp
    <div class="container-landing pt-6">
        <div class="surface-card p-6">
            <div class="grid gap-6 sm:grid-cols-2 md:grid-cols-3">
                @if($eventner->tanggal)
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-surface-container-low text-on-surface-variant border border-outline-variant/40">
                            <i class="ti ti-calendar text-xl"></i>
                        </div>
                        <div>
                            <span class="block text-[10px] font-semibold uppercase tracking-wider text-on-surface-variant">Tanggal</span>
                            <span class="text-sm font-bold text-deep-slate">
                                @if($eventner->tanggal_akhir)
                                    {{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d M') }} - {{ \Carbon\Carbon::parse($eventner->tanggal_akhir)->translatedFormat('d F Y') }}
                                @else
                                    {{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d F Y') }}
                                @endif
                            </span>
                        </div>
                    </div>
                @endif

                @if($eventner->lokasi)
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-surface-container-low text-on-surface-variant border border-outline-variant/40">
                            <i class="ti ti-map-pin text-xl"></i>
                        </div>
                        <div>
                            <span class="block text-[10px] font-semibold uppercase tracking-wider text-on-surface-variant">Lokasi</span>
                            <span class="text-sm font-bold text-deep-slate block truncate max-w-[180px]" title="{{ $eventner->lokasi }}">{{ $eventner->lokasi }}</span>
                        </div>
                    </div>
                @endif

                @if($eventner->technical_meeting)
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-surface-container-low text-on-surface-variant border border-outline-variant/40">
                            <i class="ti ti-presentation text-xl"></i>
                        </div>
                        <div>
                            <span class="block text-[10px] font-semibold uppercase tracking-wider text-on-surface-variant">Technical Meeting</span>
                            <span class="text-sm font-bold text-deep-slate">
                                {{ \Carbon\Carbon::parse($eventner->technical_meeting)->translatedFormat('d F Y, H:i') }} WIB
                            </span>
                        </div>
                    </div>
                @endif

                @if($eventner->tanggal_pendaftaran)
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-surface-container-low text-on-surface-variant border border-outline-variant/40">
                            <i class="ti ti-calendar-plus text-xl"></i>
                        </div>
                        <div>
                            <span class="block text-[10px] font-semibold uppercase tracking-wider text-on-surface-variant">Batas Pendaftaran</span>
                            <span class="text-sm font-bold text-deep-slate">
                                {{ \Carbon\Carbon::parse($eventner->tanggal_pendaftaran)->translatedFormat('d F Y') }}
                            </span>
                        </div>
                    </div>
                @endif

                @if($eventner->tingkat_perlombaan)
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-surface-container-low text-on-surface-variant border border-outline-variant/40">
                            <i class="ti ti-trophy text-xl"></i>
                        </div>
                        <div>
                            <span class="block text-[10px] font-semibold uppercase tracking-wider text-on-surface-variant">Tingkat</span>
                            <span class="text-sm font-bold text-deep-slate">{{ $eventner->tingkat_perlombaan }}</span>
                        </div>
                    </div>
                @endif

                @if($totalKuota)
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-surface-container-low text-on-surface-variant border border-outline-variant/40">
                            <i class="ti ti-users text-xl"></i>
                        </div>
                        <div>
                            <span class="block text-[10px] font-semibold uppercase tracking-wider text-on-surface-variant">Kuota Pendaftar</span>
                            <span class="text-sm font-bold text-deep-slate">{{ $totalReg }} / {{ $totalKuota }} Pasukan</span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Hitung mundur pendaftaran, menempel pada tanggal batasnya.
                 Merah hanya di angka — sisi merah dipertahankan karena
                 deadline adalah status nyata, bukan dekorasi (R-31).

                 Setelah deadline lewat, empat sel itu berhenti di "00" dan
                 labelnya tetap berbunyi "Ditutup Dalam" — angka nol yang
                 menunggu selamanya, padahal yang terjadi justru sebaliknya.
                 Percabangannya di sisi SERVER, bukan di Alpine: hitung mundur
                 yang dibiarkan menggantung akan tampil nol selama satu detik
                 penuh sebelum skripnya sempat menyembunyikannya.

                 Ambangnya `endOfDay()`, sama persis dengan sasaran hitung
                 mundurnya — jadi nolnya berhenti tepat saat sel ini berganti,
                 tanpa satu detik pun yang menampilkannya. (Status pendaftaran
                 di DB memakai batas yang lebih ketat, awal hari; yang dijaga
                 di sini cuma "jangan pernah menggambar nol".) --}}
            @if($eventner->tanggal_pendaftaran)
                {{-- Ditulis sebagai blok @php…@endphp, bukan @php(...):
                     Blade memungut blok dengan /(?<!@)@php(.*?)@endphp/s
                     dan bentuk kurungnya tak menutup apa pun, jadi ia
                     menelan seluruh isi berkas sampai @endphp berikutnya. --}}
                @php $batasPendaftaran = \Carbon\Carbon::parse($eventner->tanggal_pendaftaran)->endOfDay(); @endphp

                @if($batasPendaftaran->isPast())
                    <div class="mt-5 rounded-xl border border-outline-variant/50 bg-surface-container-low p-4">
                        <div class="flex items-center justify-center gap-2">
                            <i class="ti ti-lock text-base text-on-surface-variant" aria-hidden="true"></i>
                            <span class="text-sm font-semibold text-deep-slate">Pendaftaran Telah Ditutup</span>
                        </div>
                        <span class="mt-1 block text-center text-[11px] text-on-surface-variant">
                            Batas pendaftaran {{ $batasPendaftaran->translatedFormat('d F Y') }}
                        </span>
                    </div>
                @else
                    <div class="mt-5 rounded-xl border border-red-500/25 bg-white p-4" x-data="countdown('{{ $batasPendaftaran->toIso8601String() }}')">
                        <span class="mb-2 block text-center text-[10px] font-semibold uppercase tracking-wider text-on-surface-variant">Pendaftaran Ditutup Dalam</span>
                        <div class="grid grid-cols-4 gap-2 max-w-[280px] mx-auto">
                            <div class="rounded-lg p-1.5 text-center bg-surface-container-low">
                                <span class="block text-base font-extrabold leading-tight text-red-600" x-text="days"></span>
                                <span class="text-[8px] font-semibold uppercase text-on-surface-variant">Hari</span>
                            </div>
                            <div class="rounded-lg p-1.5 text-center bg-surface-container-low">
                                <span class="block text-base font-extrabold leading-tight text-red-600" x-text="hours"></span>
                                <span class="text-[8px] font-semibold uppercase text-on-surface-variant">Jam</span>
                            </div>
                            <div class="rounded-lg p-1.5 text-center bg-surface-container-low">
                                <span class="block text-base font-extrabold leading-tight text-red-600" x-text="minutes"></span>
                                <span class="text-[8px] font-semibold uppercase text-on-surface-variant">Mnt</span>
                            </div>
                            <div class="rounded-lg p-1.5 text-center bg-surface-container-low">
                                <span class="block text-base font-extrabold leading-tight text-red-600" x-text="seconds"></span>
                                <span class="text-[8px] font-semibold uppercase text-on-surface-variant">Det</span>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            {{-- Peta: hanya muncul kalau penyelenggara mengisi koordinat di
                 pengaturan event. Medan lokasi teks tidak bisa dipetakan
                 otomatis karena tidak ada geocoding di sisi server. --}}
            @if($eventner->latitude && $eventner->longitude)
                <div class="mt-5 rounded-xl overflow-hidden border border-outline-variant/50">
                    <iframe
                        width="100%"
                        height="200"
                        style="border:0"
                        loading="lazy"
                        allowfullscreen
                        title="Peta lokasi {{ $eventner->nama_event }}"
                        src="https://maps.google.com/maps?q={{ $eventner->latitude }},{{ $eventner->longitude }}&hl=id&z=15&output=embed">
                    </iframe>
                </div>
            @endif
        </div>
    </div>

    {{-- ========== TOP VOTER PER KATEGORI (FADE CYCLING) ========== --}}
    @php $leaderboardData = collect($this->voteLeaderboard())->filter(fn($item) => $item['top']->isNotEmpty())->values(); @endphp
    @if($leaderboardData->isNotEmpty())
        <div class="container-landing pt-6" x-data="{ li: 0 }" x-init="setInterval(() => { li = (li + 1) % {{ $leaderboardData->count() }} }, 5000)">
            <div class="surface-card p-6 overflow-hidden relative">
                <div class="flex items-center justify-between mb-4">
                    {{-- Judul section polos: dulu tiap judul kartu diawali ikon
                         tabrakan beraksen biru — aksen menyebar tanpa informasi
                         (R-29/R-31); teks judulnya sudah cukup. --}}
                    <h3 class="font-display text-lg font-bold text-deep-slate">
                        Voter Tertinggi per Kategori
                    </h3>
                    <span class="text-[10px] font-semibold text-on-surface-variant uppercase tracking-wider" x-text="'{{ $leaderboardData->count() }} kategori'"></span>
                </div>

                <div class="grid">
                    @foreach($leaderboardData as $idx => $item)
                        @php $cat = $item['category']; $topReg = $item['top']->first(); @endphp
                        <div x-show="li === {{ $idx }}"
                             x-transition:enter="transition-opacity duration-500 ease-out"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition-opacity duration-300 ease-in"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="overflow-hidden"
                             style="grid-area: 1 / 1">
                            @if($topReg)
                                <div class="flex items-center gap-4 p-4 rounded-xl bg-surface-container-low border border-outline-variant/30">
                                    <div class="shrink-0 relative">
                                        @if($topReg['logo_sekolah'])
                                            <img src="{{ asset('storage/' . $topReg['logo_sekolah']) }}" class="h-16 w-16 rounded-full object-cover border border-outline-variant/40">
                                        @else
                                            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-surface-container-high text-on-surface-variant"><i class="ti ti-school text-2xl"></i></span>
                                        @endif
                                        {{-- Lencana peringkat 1: sisi amber dipertahankan —
                                             menandai status papan atas yang nyata. --}}
                                        <span class="absolute -top-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-amber-400 text-white text-[10px] font-extrabold">
                                            <i class="ti ti-crown-filled text-[11px]"></i>
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <span class="text-[10px] font-semibold text-on-surface-variant uppercase tracking-wider block mb-0.5">{{ $cat->full_name ?? $cat->name }}</span>
                                        <h4 class="text-base font-bold text-deep-slate truncate">{{ $topReg['display_name'] }}</h4>
                                        <div class="flex items-center gap-3 mt-1">
                                            <span class="font-display text-lg font-bold text-deep-slate">{{ number_format($topReg['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                            <span class="text-[11px] font-semibold text-on-surface-variant">suara</span>
                                        </div>
                                        <a href="{{ event_url($eventner, 'vote', ['selectedCategoryId' => $cat->id]) }}" class="btn-primary mt-3 py-2 px-3 text-xs font-bold inline-flex items-center justify-center gap-1.5 text-decoration-none w-full sm:w-auto">
                                            Vote Kategori Ini
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Dot indicators --}}
                <div class="flex justify-center gap-1.5 mt-3">
                    @foreach($leaderboardData as $idx => $item)
                        <button type="button" class="h-1.5 rounded-full transition-all duration-300 border-0 cursor-pointer"
                                :class="li === {{ $idx }} ? 'w-4 bg-primary' : 'w-1.5 bg-outline-variant'"
                                @click="li = {{ $idx }}"></button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ========== TAB CONTENT: INFO ========== --}}
    <div class="container-landing py-8">
        <div class="grid gap-8 md:grid-cols-3">
            {{-- Main Column --}}
            <div class="md:col-span-2 flex flex-col gap-8">
                {{-- Kategori Lomba — kuota, biaya, tempat, tanggal, juri --}}
                @if($eventner->competitionCategories->count() > 0)
                    <div class="surface-card p-6">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                            <h3 class="font-display text-lg font-bold text-deep-slate">
                                Kategori Lomba
                            </h3>
                            <span class="text-[10px] font-semibold text-on-surface-variant uppercase tracking-wider">
                                {{ $daftarTingkat->count() }} kategori
                                @if($daftarTingkat->sum('kuota'))
                                    &middot; total kuota {{ $daftarTingkat->sum('kuota') }}
                                @endif
                            </span>
                        </div>
                        <div class="flex flex-col gap-2.5">
                            @foreach($parents as $parent)
                                {{-- Lipatan hanya berguna kalau isinya memang panjang:
                                     satu jenis lomba dengan satu tingkat langsung terbuka. --}}
                                <div x-data="{ open: {{ $parent->children->count() > 2 ? 'false' : 'true' }} }">
                                    @if($parent->children->isNotEmpty())
                                        {{-- Jenis lomba: judul bisa dilipat, isinya kartu tiap tingkat --}}
                                        <button type="button"
                                                class="w-full flex items-center justify-between gap-2 text-sm font-bold text-deep-slate bg-surface-container-low/60 border border-outline-variant/30 rounded-xl px-3 py-2 cursor-pointer hover:border-primary/30 transition-colors"
                                                @click="open = !open"
                                                :class="open && 'rounded-b-none border-b-transparent'">
                                            <span class="inline-flex items-center gap-2 min-w-0">
                                                <span class="truncate">{{ $parent->name }}</span>
                                                <span class="text-[10px] font-semibold text-on-surface-variant uppercase tracking-wider shrink-0">
                                                    {{ $parent->children->count() }} tingkat
                                                </span>
                                            </span>
                                            <i class="ti ti-chevron-down text-on-surface-variant transition-transform duration-200 shrink-0"
                                               :class="open && 'rotate-180'"></i>
                                        </button>
                                        <div x-show="open" x-collapse x-cloak class="border border-t-0 border-outline-variant/30 rounded-b-xl p-2.5">
                                            {{-- Satu kolom di layar sempit, dua kolom begitu ada ruang. --}}
                                            <div class="grid gap-2.5 sm:grid-cols-2">
                                                @foreach($parent->children->sortBy('sort_order') as $child)
                                                    @include('livewire.public.partials._competition-category-card', ['category' => $child])
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        {{-- Data lama: induk tanpa tingkat, tetap tampil apa adanya --}}
                                        @include('livewire.public.partials._competition-category-card', ['category' => $parent])
                                    @endif
                                </div>
                            @endforeach

                            {{-- Orphan children (data lama tanpa parent) --}}
                            @php $orphans = $children->whereNotIn('parent_id', $parents->pluck('id')); @endphp
                            @foreach($orphans as $orphan)
                                @include('livewire.public.partials._competition-category-card', ['category' => $orphan])
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Tentang Acara --}}
                <div class="surface-card p-6">
                    <h3 class="font-display text-lg font-bold text-deep-slate mb-4">
                        Tentang Acara
                    </h3>
                    <div class="text-sm text-on-surface-variant leading-relaxed whitespace-pre-line font-sans">
                        {{ $eventner->deskripsi ?? 'Detail deskripsi acara belum ditambahkan oleh penyelenggara.' }}
                    </div>
                </div>

                {{-- Juknis & Dokumen unggahan panitia --}}
                @php $documents = \App\Models\EventDocument::where('eventner_id', $eventner->id)->orderBy('sort_order')->orderBy('id')->get(); @endphp

                @if($documents->isNotEmpty())
                    <div class="surface-card p-6">
                        <h3 class="font-display text-lg font-bold text-deep-slate mb-4">
                            Juknis &amp; Dokumen
                        </h3>
                        <div class="flex flex-col gap-2">
                            @foreach($documents as $d)
                                {{-- Seluruh baris adalah tautan: target klik besar,
                                     tidak perlu membidik tombol kecil di layar HP.
                                     PDF & gambar terbuka di tab baru (browser
                                     merendernya sendiri); Office tetap diunduh
                                     karena browser tak bisa menampilkannya. --}}
                                <a href="{{ event_url($eventner, 'documents.download', ['document' => $d->id]) }}"
                                   @if($d->bisa_dipratinjau) target="_blank" rel="noopener" title="Buka {{ $d->title }}" @else title="Unduh {{ $d->title }}" @endif
                                   class="group flex items-start gap-3 rounded-xl border border-outline-variant/30 p-3 text-decoration-none hover:border-primary/40 transition">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-surface-container-low text-on-surface-variant border border-outline-variant/40">
                                        <i class="ti {{ $d->ikon }} text-xl"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-bold text-deep-slate">
                                            {{ $d->title }}
                                            @if($d->is_primary)
                                                <span class="ms-1 rounded-md bg-primary/10 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-primary">Utama</span>
                                            @endif
                                        </span>
                                        @if($d->description)
                                            <span class="mt-0.5 block text-xs leading-relaxed text-on-surface-variant">{{ $d->description }}</span>
                                        @endif
                                        <span class="mt-1 block text-[10px] font-semibold uppercase tracking-wider text-on-surface-variant">
                                            {{ $d->extension }} &middot; {{ $d->ukuran_terbaca }}
                                        </span>
                                    </span>
                                    <span class="flex shrink-0 items-center gap-1 text-primary transition">
                                        @if($d->bisa_dipratinjau)
                                            <i class="ti ti-eye text-lg"></i>
                                            <span class="text-[10px] font-bold uppercase tracking-wider">Buka</span>
                                        @else
                                            <i class="ti ti-download text-lg"></i>
                                            <span class="text-[10px] font-bold uppercase tracking-wider">Unduh</span>
                                        @endif
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Galeri Foto --}}
                @php $galleries = \App\Models\EventGallery::where('eventner_id', $eventner->id)->orderBy('sort_order')->latest()->get(); @endphp

                @if($galleries->isNotEmpty())
                    <div class="surface-card p-6">
                        <h3 class="font-display text-lg font-bold text-deep-slate mb-4">
                            Galeri Foto
                        </h3>
                        <div class="grid gap-2 grid-cols-3">
                            @foreach($galleries->take(6) as $gal)
                                <a href="{{ asset('storage/' . $gal->image) }}" target="_blank" class="rounded-lg overflow-hidden border border-outline-variant/30 hover:opacity-90 transition">
                                    <img src="{{ asset('storage/' . $gal->image) }}" class="w-full h-24 object-cover" alt="{{ $gal->caption ?? 'Foto Event' }}" loading="lazy">
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- FAQ --}}
                @php $faqs = \App\Models\EventFaq::where('eventner_id', $eventner->id)->orderBy('sort_order')->get(); @endphp
                @if($faqs->isNotEmpty())
                    <div class="surface-card p-6">
                        <h3 class="font-display text-lg font-bold text-deep-slate mb-4">
                            Tanya Jawab (FAQ)
                        </h3>
                        <div class="flex flex-col gap-2">
                            @foreach($faqs as $faq)
                                <details class="group border border-outline-variant/30 rounded-xl p-4 hover:border-primary/30 transition">
                                    <summary class="text-sm font-bold text-deep-slate cursor-pointer list-none flex justify-between items-center">
                                        {{ $faq->question }}
                                        <i class="ti ti-chevron-down text-on-surface-variant group-open:hidden"></i>
                                        <i class="ti ti-chevron-up text-primary hidden group-open:inline"></i>
                                    </summary>
                                    <p class="text-sm text-on-surface-variant mt-3 leading-relaxed">{{ $faq->answer }}</p>
                                </details>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Dewan Juri --}}
                {{-- Dua bentuk, satu sumber data, satu sumbu pemisah: LEBAR
                     layar. Kartu potret begitu ruangnya cukup untuk berdiri
                     berdampingan (lg ke atas), baris kompak di bawahnya —
                     kartu potret yang ditumpuk di ponsel memakan tiga layar
                     untuk informasi yang sama.

                     Jumlah juri tidak lagi menentukan bentuk, dan itu bukan
                     penyederhanaan gaya: ambang jumlah (dulu `count() > 6`)
                     bocor tepat di rentang yang paling butuh penjagaan.
                     `auto-fit` menurunkan kolomnya sendiri saat kartunya tak
                     muat, jadi pada 820 px delapan juri jadi tiga kolom
                     (672 px), sementara pada 800 px — dua puluh piksel lebih
                     sempit — ia jatuh ke dua kolom dan tingginya berlipat jadi
                     1136 px; dan ambang `count() > 6` tak menyala selama
                     jurinya tujuh. Lebar adalah satu-satunya sumbu yang benar-
                     benar menentukan, jadi lebarlah yang dibaca. --}}
                @php $dewan = $this->dewanJuri; @endphp

                @if($dewan->isNotEmpty())
                    <div class="surface-card p-6">
                        <div class="mb-5 flex items-center gap-2">
                            <i class="ti ti-user-star text-primary text-lg"></i>
                            <h3 class="font-display text-lg font-bold text-deep-slate">Dewan Juri</h3>
                            <span class="ml-auto chip bg-primary/10 text-primary py-0.5 px-2.5 text-xs font-bold leading-normal">
                                {{ $dewan->count() }} Juri
                            </span>
                        </div>

                        {{-- Satu aturan untuk kedua bentuk, ditulis sekali di
                             sini: baris kompak di bawah `lg`, potret di atas
                             `lg`. Tak ada lagi jalur yang tampil di dua
                             rentang berbeda, jadi tak ada lagi bentuk yang
                             bergantung pada berapa banyak jurinya. --}}
                        <div class="lg:hidden">
                            @include('livewire.public.partials._dewan-juri-rows', ['dewan' => $dewan])
                        </div>

                        <div class="hidden lg:block">
                            @include('livewire.public.partials._dewan-juri-portraits', ['dewan' => $dewan])
                        </div>
                    </div>
                @endif

                {{-- Tenant / Bazaar --}}
                @if($eventner->tenants->count() > 0)
                    <div class="surface-card p-6">
                        <h3 class="font-display text-lg font-bold text-deep-slate mb-6">
                            Tenant &amp; Bazaar
                        </h3>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach($eventner->tenants as $tenant)
                                <div class="flex items-center gap-4 p-4 rounded-xl bg-surface-container-low border border-outline-variant/30">
                                    @if($tenant->logo)
                                        <img src="{{ asset('storage/' . $tenant->logo) }}" alt="{{ $tenant->name }}" class="h-12 w-12 rounded-lg object-cover">
                                    @else
                                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-surface-container-high text-on-surface-variant">
                                            <i class="ti ti-store text-xl"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <h4 class="text-sm font-bold text-deep-slate mb-0.5">{{ $tenant->name }}</h4>
                                        <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider block">
                                            @if($tenant->type === 'culinary') Kuliner
                                            @elseif($tenant->type === 'beverage') Minuman
                                            @elseif($tenant->type === 'souvenir') Souvenir
                                            @elseif($tenant->type === 'bazaar') Bazaar
                                            @else Lainnya @endif
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Sidebar Column --}}
            {{-- sticky: kolom utama jauh lebih tinggi (6 kartu vs 2), jadi tanpa
                 ini separuh kanan layar kosong begitu pengunjung mulai scroll.
                 `self-start` wajib — tanpa itu item grid melar setinggi baris
                 dan sticky tidak punya ruang untuk bergerak. top-24 = header
                 64px + jeda 32px.
                 max-h + overflow-y: kalau penyelenggara mengisi poster, TM,
                 rundown, peta, dan kontak sekaligus, sidebar bisa lebih tinggi
                 dari layar — tanpa batas ini bagian bawahnya terpotong dan tak
                 bisa dijangkau selama menempel. --}}
            <div class="flex flex-col gap-5 self-start lg:sticky lg:top-24 lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto">
                {{-- Poster Acara Card — small, sidebar, klik perbesar/zoom gambar --}}
                @if($eventner->poster)
                    <div class="surface-card p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-display text-sm font-bold text-deep-slate m-0">
                                Poster Acara
                            </h3>
                            <button type="button"
                                    @click="$dispatch('open-poster-zoom', { src: '{{ asset('storage/' . $eventner->poster) }}', title: 'Poster {{ addslashes($eventner->nama_event) }}' })"
                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary hover:text-primary/80 transition-colors focus:outline-none"
                                    title="Perbesar Poster">
                                <i class="ti ti-zoom-in text-sm"></i>
                                <span>Zoom</span>
                            </button>
                        </div>
                        <div class="flex justify-center">
                            <button type="button"
                                    @click="$dispatch('open-poster-zoom', { src: '{{ asset('storage/' . $eventner->poster) }}', title: 'Poster {{ addslashes($eventner->nama_event) }}' })"
                                    class="group relative block w-full rounded-lg overflow-hidden border border-outline-variant/30 focus:outline-none focus:ring-2 focus:ring-primary/40 text-left bg-surface-container-low cursor-zoom-in"
                                    title="Klik untuk zoom poster">
                                <img src="{{ asset('storage/' . $eventner->poster) }}" alt="Poster {{ $eventner->nama_event }}"
                                     class="w-full h-auto object-contain transition-transform duration-200 group-hover:scale-[1.02]"
                                     style="max-height: 280px;"
                                     loading="lazy">
                                <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1.5 text-white text-xs font-semibold backdrop-blur-[1px]">
                                    <i class="ti ti-zoom-in text-base"></i>
                                    <span>Klik untuk Zoom</span>
                                </div>
                            </button>
                        </div>
                        <div class="flex items-center justify-between mt-2.5 pt-2 border-t border-outline-variant/30 text-[11px]">
                            <button type="button"
                                    @click="$dispatch('open-poster-zoom', { src: '{{ asset('storage/' . $eventner->poster) }}', title: 'Poster {{ addslashes($eventner->nama_event) }}' })"
                                    class="text-on-surface-variant hover:text-primary font-medium inline-flex items-center gap-1 transition-colors">
                                <i class="ti ti-zoom-in text-xs"></i>
                                <span>Perbesar tampilan</span>
                            </button>
                            <a href="{{ asset('storage/' . $eventner->poster) }}" target="_blank" class="text-on-surface-variant hover:text-deep-slate transition-colors text-decoration-none inline-flex items-center gap-1" title="Buka gambar di tab baru">
                                <span>Tab baru</span>
                                <i class="ti ti-external-link text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                @endif

                {{-- Rundown Acara --}}
                @php $rundowns = \App\Models\EventRundown::with('sourceCategory.parent')->where('eventner_id', $eventner->id)->orderBy('sort_order')->take(3)->get(); @endphp
                @if($rundowns->isNotEmpty())
                    <div class="surface-card p-5">
                        <h3 class="font-display text-base font-bold text-deep-slate mb-3">
                            Rundown Acara
                        </h3>
                        <div class="flex flex-col gap-3">
                            @foreach($rundowns as $item)
                                <div class="flex gap-3 items-start border-b border-outline-variant/30 pb-3 last:border-0 last:pb-0">
                                    <div class="shrink-0 rounded-lg bg-surface-container-high text-deep-slate px-3 py-1.5 font-mono text-xs font-bold leading-normal text-center min-w-[92px]">
                                        {{ \Carbon\Carbon::parse($item->start_time)->format('H:i') }}
                                        @if($item->end_time)
                                            <span class="text-on-surface-variant/70">–</span> {{ \Carbon\Carbon::parse($item->end_time)->format('H:i') }}
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-bold text-deep-slate text-sm leading-normal">{{ $item->title }}</span>
                                        @if($item->description)
                                            <p class="text-xs text-on-surface-variant leading-normal mt-0.5">{{ $item->description }}</p>
                                        @endif
                                        @if($item->sourceCategory)
                                            <span class="inline-flex items-center rounded-md bg-surface-container-high px-1.5 py-0.5 text-[10px] font-semibold text-on-surface-variant border border-outline-variant/40 mt-1">
                                                {{ $item->sourceCategory->parent?->name ? $item->sourceCategory->parent->name . ' — ' . $item->sourceCategory->name : $item->sourceCategory->name }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <a href="{{ event_url($eventner, 'rundown') }}"
                            class="mt-3 w-full py-2 px-3 rounded-lg bg-primary/10 hover:bg-primary/15 border border-primary/20 text-primary text-xs font-bold leading-normal inline-flex items-center justify-center gap-1.5 text-decoration-none transition-colors">
                            <i class="ti ti-list-details"></i> Lihat Rundown Lengkap
                        </a>
                    </div>
                @endif

                {{-- Hubungi Penyelenggara --}}
                @if($eventner->link_whatsapp)
                    <div class="surface-card p-5">
                        <h3 class="font-display text-base font-bold text-deep-slate mb-3">
                            Hubungi Penyelenggara
                        </h3>
                        @php $waNumber = preg_replace('/[^0-9]/', '', $eventner->link_whatsapp); @endphp
                        {{-- Kartu WA: sisi hijau dipertahankan — memanggil brand
                             WhatsApp yang dikenali pengguna (R-04), bukan dekorasi. --}}
                        <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode('Halo, saya ingin bertanya tentang event ' . $eventner->nama_event) }}"
                            target="_blank" class="flex items-center gap-3 p-3 rounded-xl bg-emerald-500/5 hover:bg-emerald-500/10 border border-emerald-500/20 text-decoration-none transition-colors">
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-500 text-white shrink-0">
                                <i class="ti ti-brand-whatsapp text-lg"></i>
                            </div>
                            <div>
                                <span class="font-bold text-deep-slate text-sm block">Chat WhatsApp</span>
                                <span class="text-xs text-on-surface-variant block">Tanya langsung ke Admin</span>
                            </div>
                        </a>

                        @if($eventner->link_instagram || $eventner->link_tiktok)
                            <div class="flex gap-2 mt-3">
                                @if($eventner->link_instagram)
                                    <a href="{{ $eventner->link_instagram }}" target="_blank" class="btn-ghost flex-1 py-2 px-3 text-xs font-bold leading-normal inline-flex items-center justify-center gap-1.5 text-decoration-none">
                                        <i class="ti ti-brand-instagram text-base"></i> Instagram
                                    </a>
                                @endif
                                @if($eventner->link_tiktok)
                                    <a href="{{ $eventner->link_tiktok }}" target="_blank" class="btn-ghost flex-1 py-2 px-3 text-xs font-bold leading-normal inline-flex items-center justify-center gap-1.5 text-decoration-none">
                                        <i class="ti ti-brand-tiktok text-base"></i> TikTok
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Bagikan Event --}}
                <div class="surface-card p-5">
                    <h3 class="font-display text-base font-bold text-deep-slate mb-3">
                        Bagikan Event
                    </h3>
                    <div class="flex gap-2">
                        <a href="https://wa.me/?text={{ urlencode($eventner->nama_event . ' - ' . event_url($eventner, 'detail')) }}"
                            target="_blank" class="btn-ghost flex-1 py-2 px-3 text-xs font-bold leading-normal inline-flex items-center justify-center gap-1.5 text-decoration-none">
                            <i class="ti ti-brand-whatsapp text-base"></i> WhatsApp
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(event_url($eventner, 'detail')) }}"
                            target="_blank" class="btn-ghost flex-1 py-2 px-3 text-xs font-bold leading-normal inline-flex items-center justify-center gap-1.5 text-decoration-none">
                            <i class="ti ti-brand-facebook text-base"></i> Facebook
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sponsor & Media Partner (1 card, tiered logo size) --}}
        @if($eventner->sponsors->count() > 0)
            @php
                $sGrouped = $eventner->sponsors->groupBy('type');
                $typeOrders = ['sponsor', 'gold', 'silver', 'bronze', 'medpart', 'partner', 'supporting'];
                $typeLabels = [
                    'sponsor' => 'Sponsor Utama',
                    'gold' => 'Sponsor Gold',
                    'silver' => 'Sponsor Silver',
                    'bronze' => 'Sponsor Bronze',
                    'medpart' => 'Media Partner',
                    'partner' => 'Event Partner',
                    'supporting' => 'Supporting'
                ];
                $tierSizes = [
                    'sponsor' => 'h-20 md:h-24',
                    'gold' => 'h-16 md:h-20',
                    'silver' => 'h-14 md:h-16',
                    'bronze' => 'h-12 md:h-14',
                    'medpart' => 'h-10 md:h-12',
                    'partner' => 'h-10 md:h-12',
                    'supporting' => 'h-8 md:h-10',
                ];
            @endphp
            <div class="mt-8">
                <div class="surface-card p-8">
                    <div class="text-center mb-8">
                        <h3 class="font-display text-lg font-bold text-deep-slate">Sponsor & Media Partner</h3>
                        <p class="text-sm text-on-surface-variant mt-1">Terimakasih kepada para sponsor dan media partner yang telah mendukung acara ini.</p>
                    </div>
                    <div class="flex flex-col items-center gap-8">
                        @foreach($typeOrders as $t)
                            @if(isset($sGrouped[$t]) && $sGrouped[$t]->count() > 0)
                                <div class="w-full">
                                    <span class="block text-center mb-4 text-xs font-semibold uppercase tracking-[0.15em] text-on-surface-variant">{{ $typeLabels[$t] ?? $t }}</span>
                                    <div class="flex flex-wrap items-center justify-center gap-8 md:gap-12">
                                        @foreach($sGrouped[$t] as $sponsor)
                                            @if($sponsor->link)
                                                <a href="{{ $sponsor->link }}" target="_blank" class="transition hover:opacity-80">
                                            @endif
                                            @if($sponsor->logo)
                                                <img src="{{ asset('storage/' . $sponsor->logo) }}" class="{{ $tierSizes[$t] ?? 'h-12' }} w-auto object-contain max-w-[200px]" alt="{{ $sponsor->name }}" loading="lazy">
                                            @else
                                                <span class="text-sm font-bold text-on-surface-variant uppercase tracking-wider">{{ $sponsor->name }}</span>
                                            @endif
                                            @if($sponsor->link)
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                                @if(!$loop->last)
                                    {{-- Pemisah tipis selebar konten — dulu garis 24px di tengah
                                         yang tampak seperti ornamen. --}}
                                    <hr class="w-full max-w-md border-outline-variant/30">
                                @endif
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>


    {{-- ========== FLOATING REGISTER CTA (Fixed Bottom) ========== --}}
    {{--
        Di bawah md, CTA ini duduk di ATAS bottom-nav mobile, bukan di
        belakangnya. Sebelumnya ia `bottom-6` — 1.5rem dari dasar layar —
        sementara nav-nya `h-16` (4rem) dan sama-sama z-40; karena nav
        dirender belakangan di DOM, tumpukan z yang seri membuat nav menang
        dan tombolnya tertutup. `4.25rem` = tinggi nav (4rem) + 0.25rem
        celah, ditambah safe-area yang juga dinaikkan nav-nya — pola yang
        sama dengan form vote di event-vote.blade.php.

        Dari md ke atas nav-nya `md:hidden`, jadi jarak aslinya (bottom-6)
        dipakai kembali.
    --}}
    @if(($eventner->registration_status ?? 'open') != 'closed')
        <div class="fixed bottom-[calc(4.25rem_+_env(safe-area-inset-bottom))] md:bottom-6 left-1/2 -translate-x-1/2 z-40 max-w-sm w-[calc(100%-2rem)]">
            <a href="{{ event_url($eventner, 'register') }}" class="btn-primary py-3.5 px-6 font-bold text-sm w-full text-center inline-flex justify-center text-decoration-none">
                <i class="ti ti-clipboard-list text-base"></i>
                {{ ($eventner->registration_status ?? 'open') == 'booking' ? 'Booking Slot Sekarang' : 'Daftar Sekarang' }}
            </a>
        </div>
    @endif

    {{-- ========== POSTER ZOOM MODAL (LIGHTBOX) ========== --}}
    @if($eventner->poster)
        <div x-data="posterZoomViewer()"
             x-show="isOpen"
             x-cloak
             @open-poster-zoom.window="open($event.detail)"
             @keydown.escape.window="close()"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex flex-col justify-between overflow-hidden bg-slate-950/92 backdrop-blur-md select-none"
             role="dialog"
             aria-modal="true"
             aria-label="Penampil Zoom Poster Acara">

            {{-- Top Bar --}}
            <div class="relative z-20 flex items-center justify-between px-4 py-3 bg-gradient-to-b from-black/80 via-black/40 to-transparent">
                <div class="flex items-center gap-2 min-w-0 pr-4">
                    <i class="ti ti-photo text-white/70 text-lg shrink-0"></i>
                    <h4 class="text-sm font-semibold text-white/95 truncate m-0" x-text="title"></h4>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <a :href="src"
                       target="_blank"
                       class="p-2 rounded-lg text-white/75 hover:text-white hover:bg-white/10 transition-colors text-decoration-none inline-flex items-center justify-center"
                       title="Buka gambar di tab baru">
                        <i class="ti ti-external-link text-lg"></i>
                    </a>
                    <button type="button"
                            @click="close()"
                            class="p-2 rounded-lg text-white/75 hover:text-white hover:bg-white/10 transition-colors inline-flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-white/30"
                            title="Tutup (Esc)"
                            aria-label="Tutup penampil poster">
                        <i class="ti ti-x text-xl"></i>
                    </button>
                </div>
            </div>

            {{-- Main Scrollable Viewport --}}
            <div x-ref="viewport"
                 class="flex-1 w-full h-full overflow-y-auto overflow-x-auto overscroll-contain select-none focus:outline-none"
                 :class="cursorClass"
                 tabindex="0"
                 @mousedown="startPan($event)"
                 @mousemove.window="onPan($event)"
                 @mouseup.window="stopPan()"
                 @wheel="handleWheel($event)"
                 @click="handleBackdropClick($event)">

                <div x-ref="wrapper"
                     class="min-h-full min-w-full flex justify-center p-4 md:p-8"
                     :class="mode === 'fit-screen' ? 'items-center' : 'items-start'">

                    <img :src="src"
                         :alt="title"
                         draggable="false"
                         @dblclick.stop="toggleFit()"
                         :style="imageStyle"
                         :class="imageClass"
                         class="select-none rounded shadow-2xl transition-[width,max-width] duration-150" />
                </div>
            </div>

            {{-- Bottom Controls Toolbar --}}
            <div class="relative z-20 pb-4 px-4 flex flex-col items-center gap-2 pointer-events-none">
                <div class="pointer-events-auto inline-flex items-center gap-1 rounded-full bg-slate-900/90 border border-white/15 px-3 py-1.5 shadow-2xl backdrop-blur-md text-white">
                    <button type="button"
                            @click.stop="zoomOut()"
                            class="p-1.5 rounded-full hover:bg-white/15 transition-colors inline-flex items-center justify-center focus:outline-none"
                            title="Perkecil (Zoom Out)"
                            aria-label="Perkecil">
                        <i class="ti ti-zoom-out text-base"></i>
                    </button>

                    <button type="button"
                            @click.stop="toggleFit()"
                            class="px-2.5 py-1 text-xs font-semibold hover:bg-white/15 rounded-full transition-colors focus:outline-none inline-flex items-center gap-1"
                            :title="mode === 'fit-screen' ? 'Klik untuk Mode Baca (Lebar Penuh & Scroll)' : 'Klik untuk Pas Layar'"
                            aria-label="Ganti mode tampilan">
                        <i :class="mode === 'fit-screen' ? 'ti ti-arrows-vertical' : 'ti ti-arrows-maximize'" class="text-xs"></i>
                        <span x-text="modeText"></span>
                    </button>

                    <button type="button"
                            @click.stop="zoomIn()"
                            class="p-1.5 rounded-full hover:bg-white/15 transition-colors inline-flex items-center justify-center focus:outline-none"
                            title="Perbesar (Zoom In)"
                            aria-label="Perbesar">
                        <i class="ti ti-zoom-in text-base"></i>
                    </button>

                    <span class="h-4 w-px bg-white/20 mx-0.5"></span>

                    <button type="button"
                            @click.stop="resetZoom()"
                            class="p-1.5 rounded-full hover:bg-white/15 transition-colors inline-flex items-center justify-center focus:outline-none"
                            title="Reset Ukuran dan Posisi"
                            aria-label="Reset ukuran">
                        <i class="ti ti-rotate text-base"></i>
                    </button>

                    <a :href="src"
                       download
                       class="p-1.5 rounded-full hover:bg-white/15 text-white text-decoration-none transition-colors inline-flex items-center justify-center focus:outline-none"
                       title="Unduh Poster Asli"
                       aria-label="Unduh poster">
                        <i class="ti ti-download text-base"></i>
                    </a>
                </div>

                <p class="text-[11px] text-white/70 text-center font-medium drop-shadow m-0 hidden sm:block">
                    Scroll mouse untuk geser ke atas/bawah &bull; Tarik gambar untuk pan &bull; Dobel-klik untuk ganti mode &bull; Esc untuk tutup
                </p>
                <p class="text-[10px] text-white/70 text-center font-medium drop-shadow m-0 sm:hidden">
                    Geser atas/bawah untuk membaca &bull; Dobel-ketuk untuk ganti ukuran &bull; Tutup via &times;
                </p>
            </div>
        </div>
    @endif

</div>

{{-- Alpine Component Definitions --}}
<script>
(function() {
    function registerAlpineComponents() {
        if (!window.Alpine) return;

        if (!window.Alpine._hasCountdownData) {
            window.Alpine._hasCountdownData = true;
            Alpine.data('countdown', (targetDate) => ({
                days: '00', hours: '00', minutes: '00', seconds: '00',
                init() {
                    this.update();
                    setInterval(() => this.update(), 1000);
                },
                update() {
                    const target = new Date(targetDate).getTime();
                    const now = Date.now();
                    const diff = target - now;
                    if (diff <= 0) {
                        this.days = '00'; this.hours = '00'; this.minutes = '00'; this.seconds = '00';
                        return;
                    }
                    this.days = String(Math.floor(diff / 86400000)).padStart(2, '0');
                    this.hours = String(Math.floor((diff % 86400000) / 3600000)).padStart(2, '0');
                    this.minutes = String(Math.floor((diff % 3600000) / 60000)).padStart(2, '0');
                    this.seconds = String(Math.floor((diff % 60000) / 1000)).padStart(2, '0');
                }
            }));
        }

        if (!window.Alpine._hasPosterZoomViewer) {
            window.Alpine._hasPosterZoomViewer = true;
            Alpine.data('posterZoomViewer', () => ({
                isOpen: false,
                src: '',
                title: '',
                mode: 'fit-width',
                scale: 1,
                isTall: true,
                isPanning: false,
                hasMoved: false,
                startX: 0,
                startY: 0,
                scrollLeftStart: 0,
                scrollTopStart: 0,
                baseWidth: 768,

                get imageStyle() {
                    if (this.mode === 'custom') {
                        const width = Math.round(this.baseWidth * this.scale);
                        return `width: ${width}px; max-width: none; height: auto;`;
                    }
                    return '';
                },

                get imageClass() {
                    if (this.mode === 'fit-screen') {
                        return 'max-h-[calc(100vh-8.5rem)] max-w-[92vw] w-auto h-auto object-contain';
                    }
                    if (this.mode === 'fit-width') {
                        return 'w-full max-w-2xl md:max-w-3xl h-auto object-contain';
                    }
                    return 'h-auto object-contain';
                },

                get modeText() {
                    if (this.mode === 'fit-screen') return 'Pas Layar';
                    if (this.mode === 'fit-width') return 'Lebar Penuh';
                    return Math.round(this.scale * 100) + '%';
                },

                get cursorClass() {
                    if (this.isPanning) return 'cursor-grabbing';
                    if (this.mode === 'fit-screen') return 'cursor-zoom-in';
                    return 'cursor-grab';
                },

                open(detail) {
                    if (!detail || !detail.src) return;
                    this.src = detail.src;
                    this.title = detail.title || 'Poster Acara';
                    this.mode = 'fit-width';
                    this.scale = 1;
                    this.isPanning = false;
                    this.hasMoved = false;
                    this.isOpen = true;
                    document.body.classList.add('overflow-hidden');

                    const img = new Image();
                    img.onload = () => {
                        this.isTall = img.naturalHeight > img.naturalWidth;
                        if (!this.isTall) {
                            this.mode = 'fit-screen';
                        } else {
                            this.mode = 'fit-width';
                        }
                        this.$nextTick(() => {
                            if (this.$refs.viewport) {
                                this.$refs.viewport.scrollTop = 0;
                                this.$refs.viewport.scrollLeft = 0;
                            }
                        });
                    };
                    img.src = this.src;

                    this.$nextTick(() => {
                        if (this.$refs.viewport) {
                            this.$refs.viewport.scrollTop = 0;
                            this.$refs.viewport.scrollLeft = 0;
                        }
                    });
                },

                close() {
                    this.isOpen = false;
                    document.body.classList.remove('overflow-hidden');
                },

                resetZoom() {
                    this.mode = this.isTall ? 'fit-width' : 'fit-screen';
                    this.scale = 1;
                    this.$nextTick(() => {
                        if (this.$refs.viewport) {
                            this.$refs.viewport.scrollTop = 0;
                            this.$refs.viewport.scrollLeft = 0;
                        }
                    });
                },

                setMode(newMode) {
                    this.mode = newMode;
                    this.scale = 1;
                },

                toggleFit() {
                    if (this.mode === 'fit-width') {
                        this.setMode('fit-screen');
                    } else {
                        this.setMode('fit-width');
                    }
                },

                zoomIn() {
                    if (this.mode === 'fit-screen') {
                        this.mode = 'fit-width';
                        this.scale = 1;
                    } else if (this.mode === 'fit-width') {
                        this.mode = 'custom';
                        this.scale = 1.25;
                    } else {
                        this.scale = Math.min(3.0, +(this.scale + 0.25).toFixed(2));
                    }
                },

                zoomOut() {
                    if (this.mode === 'fit-width') {
                        this.mode = 'fit-screen';
                        this.scale = 1;
                    } else if (this.mode === 'custom') {
                        if (this.scale <= 1.0) {
                            this.mode = 'fit-screen';
                            this.scale = 1;
                        } else {
                            this.scale = Math.max(0.4, +(this.scale - 0.25).toFixed(2));
                        }
                    } else {
                        this.mode = 'custom';
                        this.scale = 0.75;
                    }
                },

                handleWheel(e) {
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        if (e.deltaY < 0) {
                            this.zoomIn();
                        } else {
                            this.zoomOut();
                        }
                    }
                    // Tanpa tombol Ctrl: biarkan scroll native peramban berjalan ke atas/bawah secara alami
                },

                startPan(e) {
                    if (e.button !== 0 || e.target.closest('button, a')) return;
                    this.isPanning = true;
                    this.hasMoved = false;
                    this.startX = e.clientX;
                    this.startY = e.clientY;
                    if (this.$refs.viewport) {
                        this.scrollLeftStart = this.$refs.viewport.scrollLeft;
                        this.scrollTopStart = this.$refs.viewport.scrollTop;
                    }
                },

                onPan(e) {
                    if (!this.isPanning || !this.$refs.viewport) return;
                    const dx = e.clientX - this.startX;
                    const dy = e.clientY - this.startY;
                    if (Math.abs(dx) > 4 || Math.abs(dy) > 4) {
                        this.hasMoved = true;
                    }
                    this.$refs.viewport.scrollLeft = this.scrollLeftStart - dx;
                    this.$refs.viewport.scrollTop = this.scrollTopStart - dy;
                },

                stopPan() {
                    this.isPanning = false;
                },

                handleBackdropClick(e) {
                    if (this.hasMoved) {
                        this.hasMoved = false;
                        return;
                    }
                    if (e.target === this.$refs.viewport || e.target === this.$refs.wrapper) {
                        this.close();
                    }
                }
            }));
        }
    }

    if (window.Alpine) {
        registerAlpineComponents();
    } else {
        document.addEventListener('alpine:init', registerAlpineComponents);
    }
})();
</script>
