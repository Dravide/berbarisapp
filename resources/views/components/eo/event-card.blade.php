@php
    /** @var \App\Models\Eventner $eventner */
    $tanggalSelesai = $eventner->tanggal_akhir ?: $eventner->tanggal;
    $sudahTerlaksana = $tanggalSelesai && \Carbon\Carbon::parse($tanggalSelesai)->endOfDay()->isPast();
@endphp
{{-- Kartu event versi EO.
     Komponen landing (`components/landing/eventner-card`) TIDAK dipakai di sini:
     ia memakai kelas landing.css (surface-card, chip, card-past) yang tidak
     dimuat di bundel EO, jadi kartunya akan tampil tanpa gaya sama sekali.
     Bentuk kartu EO juga berbeda: keterangan ditaruh sebagai label monospace,
     bukan chip neon. --}}
<article class="eo-card eo-card-flat">
    <a href="{{ event_url($eventner, 'detail') }}" class="block no-underline" style="color: inherit;">
        <div class="relative overflow-hidden" style="aspect-ratio: 16/9; border: 1px solid var(--color-line-soft); border-radius: 6px; background: var(--color-panel-2);">
            @if($eventner->poster)
                <img src="{{ Storage::url($eventner->poster) }}" alt="{{ $eventner->nama_event }}" class="h-full w-full object-cover">
            @else
                <div class="flex h-full w-full items-center justify-center">
                    <span class="font-mono text-2xl font-medium" style="color: var(--color-line);">
                        {{ strtoupper(substr($eventner->nama_event ?? '?', 0, 1)) }}
                    </span>
                </div>
            @endif
        </div>
    </a>

    <div class="flex flex-wrap items-center gap-1.5">
        @if($sudahTerlaksana)
            <span class="eo-chip">Terlaksana</span>
        @else
            <span class="eo-chip eo-chip-acc">Dibuka</span>
        @endif
        @if($eventner->tingkat_perlombaan)
            <span class="eo-chip">{{ $eventner->tingkat_perlombaan }}</span>
        @endif
    </div>

    <h3 class="text-[13.5px] font-semibold leading-snug" style="color: var(--color-ink);">
        <a href="{{ event_url($eventner, 'detail') }}" class="no-underline hover:underline" style="color: inherit;">{{ $eventner->nama_event }}</a>
    </h3>

    <p class="eo-mono" style="font-size: 11px;">{{ $eventner->diselenggarakan_oleh }}</p>

    <dl class="mt-auto grid grid-cols-2 gap-x-3 gap-y-1 border-t pt-2.5" style="border-color: var(--color-line-soft);">
        @if($eventner->tanggal)
            <div>
                <dt class="eo-mono" style="font-size: 10px;">Tanggal</dt>
                <dd class="text-[12px]" style="color: var(--color-ink);">
                    {{-- Rentang ditulis sekali: event sehari tidak perlu mengulang bulan. --}}
                    {{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d M Y') }}@if($eventner->tanggal_akhir)&ndash;{{ \Carbon\Carbon::parse($eventner->tanggal_akhir)->translatedFormat('d M Y') }}@endif
                </dd>
            </div>
        @endif
        @if($eventner->lokasi)
            <div>
                <dt class="eo-mono" style="font-size: 10px;">Lokasi</dt>
                <dd class="text-[12px]" style="color: var(--color-ink);">{{ \Illuminate\Support\Str::limit($eventner->lokasi, 20) }}</dd>
            </div>
        @endif
        <div>
            <dt class="eo-mono" style="font-size: 10px;">Peserta</dt>
            <dd class="text-[12px]" style="color: var(--color-ink);">{{ $eventner->registrations_count ?? $eventner->registrations->count() }}</dd>
        </div>
    </dl>
</article>
