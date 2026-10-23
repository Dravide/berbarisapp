<div>
    {{-- /alur: tujuh langkah. Wireframe halaman 3.
         Nomornya memang urutan, jadi penomoran di sini bermakna.
         Perkiraan waktu datang dari setelan `eo_alur_waktu`; kalau belum diisi,
         halaman mengatakannya apa adanya alih-alih mengarang angka. --}}

    <section class="eo-section-tight">
        <div class="eo-wrap">
            <div class="max-w-[640px]">
                <span class="eo-eyebrow">Cara Kerja</span>
                <h1 class="eo-h1">Dari satu pengajuan sampai laporan setelah acara.</h1>
                <p class="eo-lede">
                    Tujuh langkah. Yang perlu Anda siapkan di awal hanya nama instansi, jenis lomba, dan perkiraan tanggal.
                </p>
            </div>
        </div>
    </section>

    <hr class="eo-rule">

    <section class="eo-section">
        <div class="eo-wrap">
            <div class="mx-auto max-w-[760px] space-y-4">
                @foreach($alur as $i => $langkah)
                    <div class="eo-step">
                        <span class="eo-step-n">{{ $i + 1 }}</span>
                        <span class="eo-step-nm">
                            {{ $langkah['judul'] }}
                            <span class="eo-step-ket">{{ $langkah['ket'] }}</span>
                        </span>
                    </div>
                @endforeach
            </div>

            {{-- Tiga penanda waktu. Nilainya dari setelan; kosong berarti
                 "menunggu kesepakatan", bukan angka contoh.
                 Disusun satu baris bersekat, bukan tiga kotak sejajar: ini tiga
                 titik pada satu garis waktu yang sama, dan sekat tipis
                 menampakkan urutannya sementara kotak memutusnya. Sekatnya
                 hanya di layar lebar, karena di ponsel ketiganya menumpuk. --}}
            <div class="eo-box eo-box-gold mx-auto mt-8 max-w-[760px]">
                <span class="eo-box-k">Perkiraan waktu</span>
                <div class="flex flex-col gap-3 sm:flex-row sm:gap-0">
                    @foreach([
                        ['kunci' => 'pengajuan', 'label' => 'Balasan pengajuan'],
                        ['kunci' => 'kunci_produksi', 'label' => 'Kunci produksi'],
                        ['kunci' => 'pelaksanaan', 'label' => 'Pelaksanaan'],
                    ] as $i => $p)
                        <div class="sm:flex-1 sm:px-5 sm:first:pl-0 sm:last:pr-0 {{ $i > 0 ? 'sm:border-l' : '' }}"
                             style="border-color: var(--color-gold);">
                            <p class="eo-mono" style="font-size: 11px;">{{ $p['label'] }}</p>
                            @if(!empty($waktu[$p['kunci']]))
                                <p class="text-[13.5px] font-semibold" style="color: var(--color-ink);">{{ $waktu[$p['kunci']] }}</p>
                            @else
                                <p class="eo-small" style="color: var(--color-gold);">menunggu kesepakatan</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="eo-box mx-auto mt-3 max-w-[760px]">
                <p class="eo-small eo-muted">
                    <span class="font-semibold" style="color: var(--color-ink);">Yang perlu disiapkan panitia:</span>
                    surat tugas atau izin kegiatan, daftar kontingen sementara, dan keputusan juri. Sisanya kami bawa.
                </p>
            </div>

            <div class="eo-card eo-card-hi mx-auto mt-6 flex-col items-start gap-4 max-w-[760px] md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="eo-h3">Mulai dari langkah pertama</h2>
                    <p class="eo-small eo-muted mt-1">Formulirnya empat isian wajib. Perkiraan tanggal boleh dikira-kira.</p>
                </div>
                <a href="{{ route('eo.ajukan') }}" class="eo-btn eo-btn-primary shrink-0">Ajukan Event</a>
            </div>
        </div>
    </section>
</div>
