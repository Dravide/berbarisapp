{{--
    Satu kartu kategori lomba untuk halaman detail event publik.
    Menampilkan kuota, biaya, tempat, tanggal, dan juri sekaligus supaya
    pendaftar tidak perlu menebak dari nama tingkat saja.

    Dipakai untuk tingkat (anak), induk lama tanpa anak, dan anak yatim.
--}}
@php
    $terisi = $category->registrations->where('status_berkas', '!=', 'dibatalkan')->count();
    $kuota = $category->kuota;
    $pct = $kuota ? min(100, round($terisi / $kuota * 100)) : 0;
    $sisa = $kuota ? max(0, $kuota - $terisi) : null;
    $penuh = $kuota && $sisa === 0;
@endphp
<div class="border border-outline-variant/30 rounded-xl p-4">
    <div class="flex flex-wrap items-start justify-between gap-2 mb-3">
        <div class="min-w-0">
            <div class="text-sm font-extrabold text-deep-slate leading-snug">{{ $category->name }}</div>
            @if($category->tanggal_pelaksanaan)
                <span class="text-[11px] font-semibold text-on-surface-variant inline-flex items-center gap-1 mt-0.5">
                    <i class="ti ti-calendar-event"></i>
                    {{ \Carbon\Carbon::parse($category->tanggal_pelaksanaan)->translatedFormat('d F Y') }}
                </span>
            @endif
        </div>
        @if($penuh)
            <span class="inline-flex items-center gap-1 rounded-full bg-red-500/10 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-red-600 border border-red-500/20 shrink-0">
                <i class="ti ti-lock"></i> Kuota Penuh
            </span>
        @elseif($kuota)
            <span class="inline-flex items-center gap-1 rounded-full bg-[#5a7d00]/10 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-[#5a7d00] border border-[#5a7d00]/20 shrink-0">
                <i class="ti ti-door-enter"></i> Sisa {{ $sisa }} Slot
            </span>
        @else
            <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-primary border border-primary/20 shrink-0">
                <i class="ti ti-infinity"></i> Tanpa Batas
            </span>
        @endif
    </div>

    {{-- Detail: tempat, biaya, batas per sekolah, juri --}}
    <div class="grid gap-2.5 sm:grid-cols-2">
        <div class="flex gap-2.5">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 text-primary shrink-0">
                <i class="ti ti-map-pin text-base"></i>
            </span>
            <div class="min-w-0">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider block">Tempat Lomba</span>
                @if($category->venue)
                    <span class="text-xs font-bold text-deep-slate leading-snug block">{{ $category->venue->name }}</span>
                    @if($category->venue->alamat)
                        <span class="text-[11px] text-on-surface-variant leading-snug block">{{ $category->venue->alamat }}</span>
                    @endif
                @else
                    <span class="text-xs font-semibold text-on-surface-variant leading-snug block">Belum ditentukan</span>
                @endif
            </div>
        </div>

        <div class="flex gap-2.5">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 shrink-0">
                <i class="ti ti-cash text-base"></i>
            </span>
            <div class="min-w-0">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider block">Biaya Pendaftaran</span>
                @if($category->registration_fee)
                    <span class="text-xs font-extrabold text-deep-slate leading-snug block">
                        Rp {{ number_format((float) $category->registration_fee, 0, ',', '.') }}
                        <span class="font-semibold text-on-surface-variant">/ pasukan</span>
                    </span>
                @else
                    <span class="text-xs font-bold text-[#5a7d00] leading-snug block">Gratis</span>
                @endif
            </div>
        </div>

        <div class="flex gap-2.5">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 text-primary shrink-0">
                <i class="ti ti-users-group text-base"></i>
            </span>
            <div class="min-w-0">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider block">Batas per Sekolah</span>
                <span class="text-xs font-bold text-deep-slate leading-snug block">
                    Maks. {{ $category->max_registrations_per_school ?? 1 }} pasukan
                </span>
            </div>
        </div>

        <div class="flex gap-2.5">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 text-primary shrink-0">
                <i class="ti ti-gavel text-base"></i>
            </span>
            <div class="min-w-0">
                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider block">Juri</span>
                @if($category->judges->isNotEmpty())
                    <span class="text-xs font-bold text-deep-slate leading-snug block">
                        {{ $category->judges->pluck('name')->join(', ') }}
                    </span>
                @else
                    <span class="text-xs font-semibold text-on-surface-variant leading-snug block">Belum ditentukan</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Progres kuota --}}
    <div class="mt-3.5 pt-3 border-t border-outline-variant/20">
        <div class="flex justify-between items-center mb-1.5 text-[11px] font-bold">
            <span class="text-on-surface-variant">Pendaftar</span>
            <span class="text-deep-slate">{{ $terisi }} / {{ $kuota ?? '∞' }} Pasukan</span>
        </div>
        @if($kuota)
            <div class="h-1.5 bg-surface-container rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500 {{ $penuh ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-primary') }}" style="width: {{ $pct }}%"></div>
            </div>
        @endif
    </div>
</div>
