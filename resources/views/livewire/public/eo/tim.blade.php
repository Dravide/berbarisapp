<div>
    {{-- /tim — enam belas peran dalam empat kelompok. Wireframe halaman 5.
         Kartu TANPA nama tetap dirender, dengan slot "menyusul". Ini disengaja:
         peran adalah bagian dari penawaran, dan menunggu semua nama terisi
         berarti halaman ini kosong sampai entah kapan.
         Jangan diubah memfilter `nama !== null` — itu membuat seluruh section
         hilang dan janji "ada timnya" ikut hilang. --}}

    <section class="eo-section-tight">
        <div class="eo-wrap">
            <div class="max-w-[640px]">
                <span class="eo-eyebrow">Tim</span>
                <h1 class="eo-h1">{{ $jumlahPeran }} peran, satu pintu keputusan.</h1>
                <p class="eo-lede">
                    Lomba baris-berbaris gagal bukan karena satu orang keliru, tapi karena tidak jelas siapa memutuskan apa. Jadi tiap peran punya tugas tertulis.
                </p>
            </div>
        </div>
    </section>

    <hr class="eo-rule">

    <section class="eo-section">
        <div class="eo-wrap space-y-9">
            @foreach($kelompok as $grup)
                <div>
                    <div class="flex flex-wrap items-baseline gap-2.5">
                        <span class="eo-chip eo-chip-gold">{{ $grup['kelompok'] }}</span>
                        <span class="eo-mono">{{ count($grup['peran']) }} peran</span>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($grup['peran'] as $peran)
                            <article class="eo-role">
                                <h2 class="eo-role-t">{{ $peran['peran'] }}</h2>
                                <p class="eo-role-d">{{ $peran['tugas'] }}</p>

                                @if($peran['nama'])
                                    <p class="eo-nm-slot" style="border-style: solid; border-color: var(--color-accent); color: var(--color-ink);">
                                        {{ $peran['nama'] }}
                                    </p>
                                @else
                                    {{-- Slot kosong, bukan kartu tersembunyi. --}}
                                    <p class="eo-nm-slot flex justify-between gap-2">
                                        <span>Nama</span><span>menyusul</span>
                                    </p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            @endforeach

            {{-- Jujur soal statusnya. Halaman yang menampilkan semua kartu seolah
                 lengkap padahal belum diisi akan terbaca sebagai contoh. --}}
            @if($jumlahTerisi < $jumlahPeran)
                <div class="eo-box eo-box-warn">
                    <span class="eo-box-k">Nama, foto, dan kontak belum lengkap</span>
                    <p class="eo-small eo-muted">
                        Susunan tim sedang difinalkan — {{ $jumlahTerisi }} dari {{ $jumlahPeran }} peran sudah terisi nama.
                        Peran dan tugasnya sudah berlaku; yang menyusul hanya namanya.
                    </p>
                </div>
            @endif
        </div>
    </section>

    <hr class="eo-rule">

    <section class="eo-section">
        <div class="eo-wrap">
            <div class="max-w-[640px]">
                <span class="eo-eyebrow">Cara Kami Bekerja</span>
                <h2 class="eo-h2">Tiga hal yang kami pegang</h2>
            </div>
            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                @foreach([
                    ['Satu pintu keputusan', 'Panitia bicara dengan Direktur Event, bukan dengan sepuluh orang berbeda.'],
                    ['Perubahan tercatat', 'Perubahan jadwal dan jumlah kategori masuk berita acara, bukan lewat pesan yang hilang.'],
                    ['Angka dari sistem', 'Jumlah peserta dan hasil juara diambil dari aplikasinya, bukan dihitung ulang di kertas.'],
                ] as [$judul, $ket])
                    <div class="eo-card eo-card-flat">
                        <h3 class="eo-h3">{{ $judul }}</h3>
                        <p class="eo-small eo-muted">{{ $ket }}</p>
                    </div>
                @endforeach
            </div>

            <div class="eo-card eo-card-hi mt-6 flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
                <div class="max-w-[560px]">
                    <h2 class="eo-h3">Ingin tim ini menangani lomba Anda?</h2>
                    <p class="eo-small eo-muted mt-1">Sebutkan jenis lomba dan perkiraan tanggalnya — kami susun komposisi tim yang dibutuhkan.</p>
                </div>
                <a href="{{ route('eo.ajukan') }}" class="eo-btn eo-btn-primary shrink-0">Ajukan Event</a>
            </div>
        </div>
    </section>
</div>
