@php
    /** @var \App\Models\Eventner $eventner */
    $tanggalSelesai = $eventner->tanggal_akhir ?: $eventner->tanggal;
    $sudahTerlaksana = $tanggalSelesai && \Carbon\Carbon::parse($tanggalSelesai)->endOfDay()->isPast();
@endphp
{{-- Kartu penyelenggara — dipakai section landing (#eventners) dan halaman
     daftar /eventner. Satu sumber tampilan: dulu kartunya disalin ke halaman
     daftar, dan setiap restyling landing harus dikerjakan dua kali. --}}
<div class="surface-card surface-card-hover group flex flex-col overflow-hidden p-0 {{ $sudahTerlaksana ? 'card-past' : '' }}" {{ $sudahTerlaksana ? 'data-past=true' : '' }}>
    {{-- Poster banner --}}
    <a href="{{ event_url($eventner, 'detail') }}" class="card-past-media relative block aspect-[3/4] overflow-hidden bg-surface-container-high">
        @if($eventner->poster)
            <img src="{{ Storage::url($eventner->poster) }}" alt="{{ $eventner->nama_event }}" class="h-full w-full object-contain">
        @else
            <div class="flex h-full w-full items-center justify-center bg-surface-container-high">
                <span class="font-display text-4xl font-extrabold text-primary/50">
                    {{ strtoupper(substr($eventner->nama_event ?? '?', 0, 1)) }}
                </span>
            </div>
        @endif
        @if($eventner->tingkat_perlombaan)
        <span class="absolute left-3 top-3 rounded-full bg-deep-slate/85 px-3 py-1 text-xs font-semibold text-white backdrop-blur">{{ $eventner->tingkat_perlombaan }}</span>
        @endif
        @if($sudahTerlaksana)
        {{-- Ditandai di kanan supaya tidak menutup chip tingkat perlombaan. --}}
        <span class="absolute right-3 top-3 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-deep-slate backdrop-blur">Terlaksana</span>
        @endif
    </a>

    {{-- Body --}}
    <div class="flex flex-1 flex-col p-5">
        <h3 class="card-past-title text-base font-bold leading-snug text-deep-slate transition-colors duration-200 group-hover:text-primary">{{ $eventner->nama_event }}</h3>
        <p class="mt-1 flex items-center gap-1 text-xs text-on-surface-variant">
            <span>{{ $eventner->diselenggarakan_oleh }}</span>
            @if($eventner->is_verified)
                <x-verified-badge size="xs" />
            @endif
        </p>

        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-outline-variant/50 pt-3 text-xs text-on-surface-variant">
            @if($eventner->tanggal)
            <span class="inline-flex items-center gap-1">
                <i class="ti ti-calendar-event text-primary"></i>
                {{-- Rentang ditulis sekali: event sehari tidak perlu mengulang bulan. --}}
                {{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d M Y') }}@if($eventner->tanggal_akhir)<span class="text-on-surface-variant/60">&ndash;{{ \Carbon\Carbon::parse($eventner->tanggal_akhir)->translatedFormat('d M Y') }}</span>@endif
            </span>
            @endif
            @if($eventner->lokasi)
            <span class="inline-flex items-center gap-1">
                <i class="ti ti-map-pin text-primary"></i>
                {{ \Illuminate\Support\Str::limit($eventner->lokasi, 15) }}
            </span>
            @endif
            <span class="inline-flex items-center gap-1">
                <i class="ti ti-users text-on-surface-variant"></i>
                {{ $eventner->registrations_count ?? $eventner->registrations->count() }} peserta
            </span>
        </div>

        <a href="{{ event_url($eventner, 'detail') }}" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-primary transition-colors group-hover:underline">
            {{ $sudahTerlaksana ? 'Lihat Hasil' : 'Lihat Event' }}
        </a>
    </div>
</div>
