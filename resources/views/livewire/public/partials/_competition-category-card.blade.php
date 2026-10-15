{{--
    Satu kartu kategori lomba untuk halaman detail event publik.
    Bentuknya sengaja padat: nama + badge, satu blok meta kecil (tanggal,
    tempat, biaya, juri, batas per sekolah), lalu bilah kuota. Versi pertama
    memakai grid 2x2 berlabel sehingga tiap kartu setinggi ~180px dan daftar
    kategori yang panjang jadi berhalaman sendiri.

    Dipakai untuk tingkat (anak), induk lama tanpa anak, dan anak yatim.
--}}
@php
    $terisi = $category->registrations->where('status_berkas', '!=', 'dibatalkan')->count();
    $kuota = $category->kuota;
    $pct = $kuota ? min(100, round($terisi / $kuota * 100)) : 0;
    $sisa = $kuota ? max(0, $kuota - $terisi) : null;
    $penuh = $kuota && $sisa === 0;
@endphp
<div class="border border-outline-variant/30 rounded-xl p-3.5">
    <div class="flex items-start justify-between gap-2">
        <div class="text-sm font-extrabold text-deep-slate leading-snug min-w-0">{{ $category->name }}</div>
        @if($penuh)
            {{-- Merah di sini status kuota sungguhan, bukan dekorasi (R-31). --}}
            <span class="inline-flex items-center gap-1 rounded-full bg-red-500/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-red-600 border border-red-500/20 shrink-0">
                <i class="ti ti-lock"></i> Penuh
            </span>
        @elseif($kuota)
            <span class="inline-flex items-center gap-1 rounded-full bg-surface-container-high px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-deep-slate border border-outline-variant/50 shrink-0">
                <i class="ti ti-door-enter"></i> Sisa {{ $sisa }}
            </span>
        @else
            <span class="inline-flex items-center gap-1 rounded-full bg-surface-container-high px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant border border-outline-variant/50 shrink-0">
                <i class="ti ti-infinity"></i> Tanpa Batas
            </span>
        @endif
    </div>

    {{-- Meta baris 1: tanggal · tempat · biaya. Ikon meta netral — dulu tiap
         ikon biru primer sehingga aksen menyebar (R-29); label cukup. --}}
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1.5 text-[11px] font-semibold text-on-surface-variant">
        @if($category->tanggal_pelaksanaan)
            <span class="inline-flex items-center gap-1">
                <i class="ti ti-calendar-event"></i>
                {{ \Carbon\Carbon::parse($category->tanggal_pelaksanaan)->translatedFormat('d M Y') }}
            </span>
        @endif

        <span class="inline-flex items-center gap-1 min-w-0" title="{{ $category->venue?->label ?? 'Tempat belum ditentukan' }}">
            <i class="ti ti-map-pin"></i>
            @if($category->venue)
                <span class="truncate max-w-[190px]">{{ $category->venue->name }}</span>
                @if($category->venue->alamat)
                    <span class="text-on-surface-variant/70 truncate max-w-[190px]">— {{ $category->venue->alamat }}</span>
                @endif
            @else
                <span>Belum ditentukan</span>
            @endif
        </span>

        @php
            // Harga yang ditagih hari ini + angka termurah yang mungkin
            // didapat. Bila ada gelombang yang lebih murah nanti, kartu
            // menyebut "mulai dari" supaya pendaftar tahu ada harga lebih
            // rendah tanpa perlu tabel gelombang penuh di sini.
            $biayaKini = $category->biayaPada();
            $biayaTermurah = $category->biayaTerendah();
        @endphp
        <span class="inline-flex items-center gap-1">
            @if($biayaKini !== null && $biayaKini > 0)
                <i class="ti ti-cash"></i>
                <span class="text-deep-slate">Rp {{ number_format($biayaKini, 0, ',', '.') }}</span>
                <span class="text-on-surface-variant/70">/ pasukan</span>
                @if($biayaTermurah !== null && $biayaTermurah < $biayaKini)
                    <span class="text-on-surface-variant/70">
                        &middot; mulai dari Rp {{ number_format($biayaTermurah, 0, ',', '.') }}
                    </span>
                @endif
            @else
                <i class="ti ti-cash text-[#5a7d00]"></i>
                <span class="text-[#5a7d00]">Gratis</span>
            @endif
        </span>
    </div>

    {{-- Meta baris 2: juri · batas per sekolah --}}
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-[11px] font-semibold text-on-surface-variant">
        <span class="inline-flex items-center gap-1 min-w-0" title="Juri: {{ $category->judges->pluck('name')->join(', ') ?: 'belum ditentukan' }}">
            <i class="ti ti-gavel"></i>
            @if($category->judges->isNotEmpty())
                <span class="truncate max-w-[220px]">{{ $category->judges->pluck('name')->join(', ') }}</span>
            @else
                <span>Juri belum ditentukan</span>
            @endif
        </span>
        <span class="inline-flex items-center gap-1">
            <i class="ti ti-users-group"></i>
            Maks. {{ $category->max_registrations_per_school ?? 1 }} pasukan/sekolah
        </span>
    </div>

    {{-- Progres kuota --}}
    <div class="mt-2.5 pt-2 border-t border-outline-variant/20">
        <div class="flex justify-between items-center mb-1 text-[11px] font-bold">
            <span class="text-on-surface-variant">Pendaftar</span>
            <span class="text-deep-slate">{{ $terisi }} / {{ $kuota ?? '∞' }} Pasukan</span>
        </div>
        @if($kuota)
            <div class="h-1.5 bg-surface-container rounded-full overflow-hidden">
                <div class="h-full rounded-full {{ $penuh ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-primary') }}" style="width: {{ $pct }}%"></div>
            </div>
        @endif
    </div>
</div>
