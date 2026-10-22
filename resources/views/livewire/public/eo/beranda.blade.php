<div>
    {{-- Beranda subdomain EO. Struktur mengikuti doc/eo-wireframe.html halaman 1:
         janji → jenis lomba → empat kelompok kerja → event terdekat → ajakan.
         Semua angka dibaca dari basis data atau dari EoProfile; tidak ada angka
         pencapaian yang ditulis tangan. --}}

    <section class="eo-section">
        <div class="eo-wrap">
            <div class="mx-auto max-w-[820px]">
                <span class="eo-eyebrow">Event Organizer Lomba Baris-Berbaris</span>
                <h1 class="eo-h1">Kami menyelenggarakan lombanya, dari poster sampai rekap juara.</h1>
                <p class="eo-lede">
                    Desain, pendaftaran, penilaian juri di lapangan, papan skor, dokumentasi, dan laporan setelah acara — dikerjakan satu tim, di satu sistem.
                </p>

                <div class="mt-7 flex flex-col gap-2.5 sm:flex-row">
                    <a href="{{ route('eo.ajukan') }}" class="eo-btn eo-btn-primary">Ajukan Event</a>
                    <a href="{{ route('eo.layanan') }}" class="eo-btn eo-btn-ghost">Lihat Lingkup Kerja</a>
                </div>

                {{-- Jenis lomba. Istilahnya sama dengan yang dipakai panitia dan
                     yang sudah hidup di kategori lomba aplikasinya (induk – tingkat). --}}
                <div class="mt-7 flex flex-wrap gap-2">
                    @foreach($jenisLomba as $jenis)
                        <span class="eo-chip">{{ $jenis }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <hr class="eo-rule">

    {{-- Empat kelompok kerja pertama. Kelima (Administrasi & Laporan) utuh di /layanan. --}}
    <section class="eo-section">
        <div class="eo-wrap">
            <div class="max-w-[640px]">
                <span class="eo-eyebrow">Lingkup Kerja</span>
                <h2 class="eo-h2">Yang kami ambil alih</h2>
                <p class="eo-lede">
                    Tiap kelompok bisa dipesan terpisah. Kalau panitia sekolah sudah punya sendiri sebagiannya, sisanya tetap bisa kami tangani.
                </p>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($layananSorot as $grup)
                    <article class="eo-card">
                        <span class="eo-chip eo-chip-gold">{{ $grup['kode'] }}</span>
                        <h3 class="eo-h3">{{ $grup['judul'] }}</h3>
                        <p class="eo-small eo-muted">{{ $grup['ringkas'] }}</p>
                        <ul class="mt-1 space-y-1 border-t pt-2.5" style="border-color: var(--color-line-soft);">
                            @foreach($grup['butir'] as $butir)
                                <li class="eo-mono" style="font-size: 11px;">{{ $butir['nama'] }}</li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>

            <p class="mt-6">
                <a href="{{ route('eo.layanan') }}" class="text-[13.5px] font-semibold no-underline hover:underline">
                    Lihat semua {{ $totalLayanan }} kelompok kerja &rarr;
                </a>
            </p>
        </div>
    </section>

    {{-- Event terdekat — hanya kalau ada. Kalau kosong, seluruh section hilang
         (bukan diisi kartu contoh). --}}
    @if($eventner->isNotEmpty())
        <hr class="eo-rule">
        <section class="eo-section">
            <div class="eo-wrap">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <span class="eo-eyebrow">Event Terdekat</span>
                        <h2 class="eo-h2">Yang sedang dibuka</h2>
                    </div>
                    <a href="{{ route('eo.event') }}" class="text-[13.5px] font-semibold no-underline hover:underline">Semua event &rarr;</a>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($eventner as $event)
                        <x-eo.event-card :eventner="$event" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <hr class="eo-rule">

    <section class="eo-section">
        <div class="eo-wrap">
            <div class="eo-card eo-card-hi flex-col items-start gap-5 md:flex-row md:items-center md:justify-between">
                <div class="max-w-[560px]">
                    <h2 class="eo-h2">Lomba berikutnya masih dikelola lewat lima berkas terpisah?</h2>
                    <p class="eo-small eo-muted mt-2">
                        Mulai dari satu pengajuan. Kami balas lewat WhatsApp pada hari kerja berikutnya dengan pertanyaan yang perlu dijawab sebelum menyusun RAB.
                    </p>
                </div>
                <a href="{{ route('eo.ajukan') }}" class="eo-btn eo-btn-primary shrink-0">Ajukan Event</a>
            </div>
        </div>
    </section>
</div>
