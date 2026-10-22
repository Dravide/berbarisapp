<div>
    {{-- /event — portofolio. Wireframe halaman 4.
         Dibaca dari `eventners` approved, bukan ditulis tangan: setiap event di
         sini punya halaman publik sendiri (hasil, papan skor, galeri), jadi
         kartunya bisa dibuka dan diperiksa. --}}

    <section class="eo-section-tight">
        <div class="eo-wrap">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="max-w-[560px]">
                    <span class="eo-eyebrow">Event</span>
                    <h1 class="eo-h1">Acara yang sedang dibuka, dan yang sudah selesai.</h1>
                    <p class="eo-lede">
                        Kalau Anda ingin ikut lomba, kartu yang akan datang berisi jadwal dan pendaftaran. Kalau Anda ingin menyewa penyelenggara, yang sudah terlaksana berisi hasil dan rekapnya.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach([
                        ['kunci' => 'semua', 'label' => 'Semua'],
                        ['kunci' => 'akan-datang', 'label' => 'Sedang dibuka'],
                        ['kunci' => 'terlaksana', 'label' => 'Sudah terlaksana'],
                    ] as $tab)
                        <button type="button"
                                wire:click="setSaring('{{ $tab['kunci'] }}')"
                                class="eo-chip cursor-pointer {{ $saring === $tab['kunci'] ? 'eo-chip-acc' : '' }}"
                                aria-pressed="{{ $saring === $tab['kunci'] ? 'true' : 'false' }}">
                            {{ $tab['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <hr class="eo-rule">

    @if($saring !== 'terlaksana')
        <section class="eo-section">
            <div class="eo-wrap">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="eo-h2">Akan datang</h2>
                    <span class="eo-mono">{{ $akanDatang->count() }} event</span>
                </div>

                @if($akanDatang->isEmpty())
                    <div class="eo-box mt-5">
                        <p class="eo-small eo-muted">
                            Belum ada event yang dibuka untuk pendaftaran saat ini.
                            <a href="{{ route('eo.ajukan') }}" class="font-semibold">Ajukan event Anda</a> — jadwalnya bisa kita susun bersama.
                        </p>
                    </div>
                @else
                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($akanDatang as $event)
                            <x-eo.event-card :eventner="$event" />
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if($saring !== 'akan-datang')
        @if($saring !== 'terlaksana')
            <hr class="eo-rule">
        @endif
        <section class="eo-section">
            <div class="eo-wrap">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="eo-h2">Sudah terlaksana</h2>
                    <span class="eo-mono">{{ $terlaksana->count() }} event</span>
                </div>

                @if($terlaksana->isEmpty())
                    <div class="eo-box mt-5">
                        <p class="eo-small eo-muted">Belum ada event yang tercatat selesai.</p>
                    </div>
                @else
                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($terlaksana as $event)
                            <x-eo.event-card :eventner="$event" />
                        @endforeach
                    </div>

                    {{-- Satu kartu yang menegaskan bahwa event di atas bisa
                         diperiksa sendiri — bukan sekadar gambar. --}}
                    <div class="eo-box mt-4">
                        <span class="eo-box-k">Yang bisa dibuka dari setiap kartu</span>
                        <div class="flex flex-wrap gap-2">
                            <span class="eo-chip eo-chip-gold">Halaman event</span>
                            <span class="eo-chip eo-chip-gold">Hasil &amp; papan skor</span>
                            <span class="eo-chip eo-chip-gold">Galeri</span>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif

    <hr class="eo-rule">

    <section class="eo-section">
        <div class="eo-wrap">
            <div class="eo-card eo-card-hi flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
                <div class="max-w-[560px]">
                    <h2 class="eo-h2">Ingin acara Anda tercatat di sini?</h2>
                    <p class="eo-small eo-muted mt-2">
                        Setiap event yang kami tangani mendapat halaman publiknya sendiri: jadwal, pendaftaran, hasil, dan galeri.
                    </p>
                </div>
                <a href="{{ route('eo.ajukan') }}" class="eo-btn eo-btn-primary shrink-0">Ajukan Event</a>
            </div>
        </div>
    </section>
</div>
