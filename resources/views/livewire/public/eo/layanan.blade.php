<div>
    {{-- /layanan: lima kelompok kerja utuh. Wireframe halaman 2.
         Dua kolom: A–B di kiri (pekerjaan desain & sistem), C–E di kanan
         (lapangan, dokumentasi, administrasi). Tanpa harga: biaya jasa dihitung
         per acara, jadi yang ditulis adalah apa yang menentukan biayanya. --}}

    <section class="eo-section-tight">
        <div class="eo-wrap">
            <div class="max-w-[720px]">
                <span class="eo-eyebrow">Lingkup Kerja</span>
                <h1 class="eo-h1">Lima kelompok kerja, dari logo sampai rekap.</h1>
                <p class="eo-lede">
                    Tiap kelompok bisa dipesan terpisah. Banyak sekolah sudah punya panitia sendiri dan hanya butuh sistem penilaiannya, atau hanya butuh desainnya.
                </p>
            </div>
        </div>
    </section>

    <hr class="eo-rule">

    <section class="eo-section">
        <div class="eo-wrap">
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
                {{-- Kolom kiri: A & B, kelompok dengan butir terbanyak, disusun
                     dua kolom di dalamnya supaya tingginya seimbang. --}}
                <div class="space-y-3">
                    @foreach(array_slice($layanan, 0, 2) as $grup)
                        <article class="eo-card">
                            <div class="flex items-center gap-2.5">
                                <span class="eo-chip eo-chip-gold">{{ $grup['kode'] }}</span>
                                <h2 class="eo-h3">{{ $grup['judul'] }}</h2>
                            </div>
                            <p class="eo-small eo-muted">{{ $grup['ringkas'] }}</p>
                            <div class="mt-1 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @foreach($grup['butir'] as $butir)
                                    <div class="eo-fx">
                                        <span class="eo-fx-ic" aria-hidden="true"></span>
                                        <span class="eo-fx-nm">
                                            {{ $butir['nama'] }}
                                            <span class="eo-fx-ket">{{ $butir['ket'] }}</span>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>

                {{-- Kolom kanan: C, D, E. --}}
                <div class="space-y-3">
                    @foreach(array_slice($layanan, 2) as $grup)
                        <article class="eo-card">
                            <div class="flex items-center gap-2.5">
                                <span class="eo-chip eo-chip-gold">{{ $grup['kode'] }}</span>
                                <h2 class="eo-h3">{{ $grup['judul'] }}</h2>
                            </div>
                            <p class="eo-small eo-muted">{{ $grup['ringkas'] }}</p>
                            <div class="mt-1 space-y-2">
                                @foreach($grup['butir'] as $butir)
                                    <div class="eo-fx">
                                        <span class="eo-fx-ic" aria-hidden="true"></span>
                                        <span class="eo-fx-nm">
                                            {{ $butir['nama'] }}
                                            <span class="eo-fx-ket">{{ $butir['ket'] }}</span>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>

            {{-- Yang menentukan biaya. Ditulis terbuka supaya pertanyaan pertama
                 di rapat ("berapa?") bisa dijawab dengan daftar, bukan angka.
                 Bentuknya daftar pasangan istilah-keterangan, bukan tiga kotak
                 sejajar: ketiganya bagian dari satu daftar yang sama, dan
                 memecahnya jadi tiga kartu membuatnya terbaca sebagai tiga hal
                 yang terpisah. --}}
            <div class="eo-box mt-6">
                <span class="eo-box-k">Yang menentukan besar biayanya</span>
                <dl>
                    @foreach([
                        ['Jumlah kategori lomba', 'Tiap kategori punya rubrik, jadwal, dan jurinya sendiri.'],
                        ['Jumlah peserta & kontingen', 'Menentukan meja daftar ulang, ID card, dan durasi arena.'],
                        ['Hari, lokasi & kebutuhan cetak', 'Acara luar kota dan backdrop besar menambah biaya produksi.'],
                    ] as $i => [$judul, $ket])
                        <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-[minmax(0,210px)_minmax(0,1fr)] sm:gap-4"
                             @if($i > 0) style="border-top: 1px solid var(--color-line-soft);" @endif>
                            <dt class="text-[13.5px] font-semibold" style="color: var(--color-ink);">{{ $judul }}</dt>
                            <dd class="eo-small eo-muted">{{ $ket }}</dd>
                        </div>
                    @endforeach
                </dl>
                <p class="eo-small eo-muted mt-4">
                    Setelah pengajuan, kami menyusun proposal dan RAB per baris, bukan satu angka bulat yang tidak bisa diperiksa.
                </p>
            </div>

            <div class="eo-card eo-card-hi mt-6 flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
                <div class="max-w-[560px]">
                    <h2 class="eo-h3">Sudah tahu lingkup yang dibutuhkan?</h2>
                    <p class="eo-small eo-muted mt-1">Sebutkan jenis lomba dan perkiraan tanggalnya. Sisanya kami tanyakan lewat WhatsApp.</p>
                </div>
                <a href="{{ route('eo.ajukan') }}" class="eo-btn eo-btn-primary shrink-0">Ajukan Event</a>
            </div>
        </div>
    </section>
</div>
