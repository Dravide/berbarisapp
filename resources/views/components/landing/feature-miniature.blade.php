@php
    // Miniatur UI fitur — digambar kode, nol aset gambar. Bentuk dan alasan
    // tiap gambar ditulis di doc/landing-fitur-wireframe.html; aturan CSS-nya
    // di `@layer components` landing.css.
    //
    // Kunci miniatur = `icon` item, BUKAN judulnya: judul bisa disunting admin
    // kapan saja, dan memilih lewat judul berarti mengganti satu kata akan
    // mematikan miniatur kartu itu. `icon` sudah tersimpan di DB dan tidak
    // ikut berubah saat judul disunting.
    //
    // `miniature` (katalog /fitur, App\Support\ProductFeatures) menang atas
    // `icon` bila ada. Item landing tidak punya kunci itu sama sekali, jadi
    // sembilan item di DB menyelesaikan persis seperti sebelumnya. Sembilan
    // item katalog yang seninya sudah ada di laman depan menulis
    // `'miniature' => 'icon3.png'` dst. — nilai jelek, tapi nol duplikasi
    // cabang @case, dan yang penting: mode gagalnya tidak senyap (salah
    // ketik jatuh ke @default dan tertangkap tes).
    $judul = $item['title'] ?? 'Fitur';
    $kunci = $item['miniature'] ?? $item['icon'] ?? '';
@endphp

<div class="feat-mini" aria-hidden="true">
    @switch($kunci)
        @case('icon3.png'){{-- Meja verifikasi: daftar peserta yang disisir baris demi baris. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-2/5"></span>
                <span class="feat-pill feat-pill-lime feat-end">Terverifikasi</span>
            </div>
            <div class="feat-mrow"><span class="feat-av"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-pill feat-pill-lime">OK</span></div>
            <div class="feat-card feat-card-row feat-card-hi">
                <span class="feat-av"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-pill feat-pill-accent">Diperiksa</span>
            </div>
            <div class="feat-mrow"><span class="feat-av"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-pill">Menunggu</span></div>
            <div class="feat-mrow"><span class="feat-av"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-pill">Menunggu</span></div>
            <div class="feat-mrow"><span class="feat-av"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-pill feat-pill-lime">OK</span></div>
        @break

        @case('icon4.png'){{-- Lembar nilai juri: peserta di atas, kotak angka di kanan, total di bawah. --}}
            <div class="feat-mrow">
                <span class="feat-sq"></span>
                <span class="feat-bar feat-bar-title w-1/2"></span>
                <span class="feat-pill feat-end">Peserta 05</span>
            </div>
            <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-3/5"></span><span class="feat-numbox feat-end"></span></div>
            <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-3/5"></span><span class="feat-numbox feat-end"></span></div>
            <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-2/5"></span><span class="feat-numbox feat-end"></span></div>
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-sm w-1/4"></span>
                <span class="feat-pill feat-pill-accent feat-end">Total 87,5</span>
            </div>
        @break

        @case('icon5.png'){{-- Papan perolehan: panjang bar sengaja tidak rata supaya terbaca lomba. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-2/5"></span>
                <span class="feat-pill feat-pill-lime feat-end">Terkini</span>
            </div>
            <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-2/5"></span><span class="feat-ballot w-full"></span></div>
            <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-2/5"></span><span class="feat-ballot w-3/5"></span></div>
            <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-2/5"></span><span class="feat-ballot w-2/5"></span></div>
            <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-2/5"></span><span class="feat-ballot w-1/4"></span></div>
        @break

        @case('icon6.png'){{-- Kartu tiket: perforasi + QR. Bentuk ini sudah dikenali tanpa perlu dijelaskan. --}}
            <div class="feat-card">
                <div class="feat-mrow"><span class="feat-sq"></span><span class="feat-bar feat-bar-title w-3/5"></span></div>
                <span class="feat-bar feat-bar-sm w-2/5"></span>
                <span class="feat-perf"></span>
                <div class="feat-mrow">
                    <span class="feat-qr"></span>
                    <span class="feat-mcol">
                        <span class="feat-bar feat-bar-sm w-4/5"></span>
                        <span class="feat-bar feat-bar-sm w-1/2"></span>
                        <span class="feat-pill feat-pill-lime">Masuk</span>
                    </span>
                </div>
            </div>
        @break

        @case('icon7.png'){{-- Papan peringkat: baris pertama lebih tebal, bukan lebih berwarna. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-1/2"></span>
                <span class="feat-pill feat-pill-lime feat-end">Live</span>
            </div>
            <div class="feat-mrow"><span class="feat-rk">1</span><span class="feat-bar feat-bar-lg"></span><span class="feat-pill feat-pill-accent">Juara</span></div>
            <div class="feat-mrow"><span class="feat-rk">2</span><span class="feat-bar feat-bar-sm w-4/5"></span></div>
            <div class="feat-mrow"><span class="feat-rk">3</span><span class="feat-bar feat-bar-sm w-3/5"></span></div>
            <div class="feat-mrow"><span class="feat-rk">4</span><span class="feat-bar feat-bar-sm w-2/5"></span></div>
        @break

        @case('icon8.png'){{-- Hasil undian, bukan prosesnya: roda berputar butuh animasi untuk berarti. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-2/5"></span>
                <span class="feat-pill feat-pill-accent feat-end">Urutan tampil</span>
            </div>
            <div class="feat-g3">
                <span class="feat-cellnum">01</span><span class="feat-cellnum">02</span><span class="feat-cellnum">03</span>
                <span class="feat-cellnum">04</span><span class="feat-cellnum feat-cellnum-sel">05</span><span class="feat-cellnum">06</span>
                <span class="feat-cellnum">07</span><span class="feat-cellnum">08</span><span class="feat-cellnum">09</span>
            </div>
        @break

        @case('icon9.png'){{-- Lembar sertifikat — satu-satunya miniatur rata tengah. --}}
            <div class="feat-card items-center text-center">
                <span class="feat-bar feat-bar-title w-1/2"></span>
                <span class="feat-bar feat-bar-sm w-1/3"></span>
                <span class="feat-bar feat-bar-sm w-4/5 mt-1.5"></span>
                <span class="feat-bar feat-bar-sm w-3/5"></span>
                <div class="feat-mrow mt-2 w-full"><span class="feat-sign"></span><span class="feat-seal"></span></div>
            </div>
        @break

        @case('icon10.png'){{-- Overlay siaran — satu-satunya berlatar gelap, karena siaran memang gelap. --}}
            <div class="feat-stage">
                <div class="feat-lw"><span class="feat-lw-nm"></span><span class="feat-lw-sc"></span></div>
            </div>
            <div class="feat-mrow">
                <span class="feat-pill feat-pill-lime">OBS</span>
                <span class="feat-pill">16:9</span>
                <span class="feat-bar feat-bar-sm w-2/5"></span>
            </div>
        @break

        @case('icon11.png'){{-- Dinding logo + peta stan. Enam kolom di kartu lebar, tiga di kartu sempit. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-1/4"></span>
                <span class="feat-pill feat-pill-accent feat-end">6 sponsor</span>
            </div>
            <div class="feat-wall">
                <span class="feat-logobox feat-logobox-hi"><i></i></span>
                <span class="feat-logobox"><i></i></span>
                <span class="feat-logobox"><i></i></span>
                <span class="feat-logobox"><i></i></span>
                <span class="feat-logobox"><i></i></span>
                <span class="feat-logobox"><i></i></span>
            </div>
            <div class="feat-mrow">
                <span class="feat-pill">Stan</span>
                <span class="feat-bar feat-bar-sm w-1/5"></span>
                <span class="feat-sq" style="width:calc(14*var(--u));height:calc(14*var(--u))"></span>
                <span class="feat-bar feat-bar-sm w-1/5"></span>
                <span class="feat-sq" style="width:calc(14*var(--u));height:calc(14*var(--u))"></span>
                <span class="feat-bar feat-bar-sm w-1/5"></span>
                <span class="feat-pill feat-pill-lime feat-end">A-01</span>
            </div>
        @break

        @case('form-daftar'){{-- Formulir bertahap. Pijakan langkah di atas, isian di bawah. --}}
            <div class="feat-card">
                <div class="feat-mrow">
                    <span class="feat-pill feat-pill-accent">Langkah 2/3</span>
                    <span class="feat-bar feat-bar-sm w-2/5 feat-end"></span>
                </div>
                <span class="feat-bar feat-bar-sm w-4/5"></span>
                <span class="feat-bar feat-bar-sm w-3/5"></span>
                <div class="feat-mrow"><span class="feat-pill feat-pill-lime feat-end">Lanjut</span></div>
            </div>
        @break

        @case('browser-publik'){{-- Magic link: peramban + daftar berkas yang bisa diunduh peserta. --}}
            <div class="feat-card">
                <div class="feat-mrow">
                    <span class="feat-av"></span><span class="feat-av"></span><span class="feat-av"></span>
                    <span class="feat-bar feat-bar-sm"></span>
                </div>
                <div class="feat-mrow"><span class="feat-sq"></span><span class="feat-pill feat-pill-lime feat-end">Tautan aktif</span></div>
                <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-3/5"></span><span class="feat-pill feat-pill-accent feat-end">Invoice</span></div>
                <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-2/5"></span><span class="feat-pill feat-pill-accent feat-end">Sertifikat</span></div>
            </div>
        @break

        @case('form-builder'){{-- Penyusun field: gagang seret, label, jenis isian. --}}
            <div class="feat-card">
                <div class="feat-mrow"><span class="feat-sq"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-pill">Teks</span></div>
                <div class="feat-mrow"><span class="feat-sq"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-pill">Pilih</span></div>
                <div class="feat-card feat-card-row feat-card-hi"><span class="feat-sq"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-pill feat-pill-accent">Berkas</span></div>
            </div>
        @break

        @case('kategori-pohon'){{-- Kategori bertingkat: induk, lalu baris anak yang menjorok ke dalam kartu. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-1/2"></span>
                <span class="feat-pill feat-pill-lime feat-end">3 grup</span>
            </div>
            <div class="feat-mrow"><span class="feat-sq"></span><span class="feat-bar feat-bar-sm"></span></div>
            <div class="feat-card">
                <div class="feat-mrow"><span class="feat-sq"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-pill">Seri A</span></div>
                <div class="feat-mrow"><span class="feat-sq"></span><span class="feat-bar feat-bar-sm w-3/5"></span></div>
            </div>
        @break

        @case('rekap-sekolah'){{-- Rekap per sekolah: satu baris satu sekolah, jumlah peserta di kanan. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-2/5"></span>
                <span class="feat-pill feat-pill-accent feat-end">12 sekolah</span>
            </div>
            <div class="feat-card feat-card-row"><span class="feat-av"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-pill feat-pill-lime">18</span></div>
            <div class="feat-card feat-card-row"><span class="feat-av"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-pill">14</span></div>
            <div class="feat-card feat-card-row"><span class="feat-av"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-pill">11</span></div>
        @break

        @case('venue'){{-- Daftar venue: nama + alamat + kuota, dengan harganya di baris bawah. --}}
            <div class="feat-card">
                <div class="feat-mrow">
                    <span class="feat-sq"></span>
                    <span class="feat-mcol"><span class="feat-bar feat-bar-title w-3/5"></span><span class="feat-bar feat-bar-sm w-2/5"></span></span>
                    <span class="feat-pill feat-pill-lime feat-end">120</span>
                </div>
                <div class="feat-mrow">
                    <span class="feat-sq"></span>
                    <span class="feat-mcol"><span class="feat-bar feat-bar-title w-2/5"></span><span class="feat-bar feat-bar-sm w-1/2"></span></span>
                    <span class="feat-pill feat-end">80</span>
                </div>
            </div>
            <div class="feat-mrow"><span class="feat-pill feat-pill-accent">Rp45.000</span><span class="feat-bar feat-bar-sm w-1/3"></span></div>
        @break

        @case('denah-kunci'){{-- Kunci dua tingkat: satu kotak zona bergembok, kotak di bawahnya masih bebas digeser. Gemboknya ditulis, bukan digambar — ikon di dalam rangka butuh ukuran sendiri yang tak dimiliki .feat-*. --}}
            <div class="feat-card">
                <div class="feat-mrow">
                    <span class="feat-sq"></span>
                    <span class="feat-bar feat-bar-sm"></span>
                    <span class="feat-pill feat-pill-accent feat-end">Terkunci</span>
                </div>
                <div class="feat-mrow">
                    <span class="feat-sq"></span>
                    <span class="feat-bar feat-bar-sm w-2/5"></span>
                    <span class="feat-pill feat-pill-lime feat-end">Bebas</span>
                </div>
            </div>
            <div class="feat-mrow"><span class="feat-pill feat-pill-lime">Denah terkunci</span><span class="feat-bar feat-bar-sm w-1/3"></span></div>
        @break

        @case('denah-alur'){{-- Alur gerak pasukan: titik belok berderet di atas denah, disambung garis putus .feat-perf — satu-satunya elemen bergaris putus di rangka, jadi alurnya tidak tertukar dengan baris isi. --}}
            <div class="feat-card">
                <div class="feat-mrow">
                    <span class="feat-sq"></span>
                    <span class="feat-bar feat-bar-title w-2/5"></span>
                    <span class="feat-pill feat-pill-accent feat-end">Masuk</span>
                </div>
                <span class="feat-perf"></span>
                <div class="feat-mrow"><span class="feat-numbox"></span><span class="feat-bar feat-bar-sm w-1/2"></span></div>
                <span class="feat-perf"></span>
                <div class="feat-mrow"><span class="feat-numbox"></span><span class="feat-bar feat-bar-sm w-1/3"></span></div>
            </div>
        @break

        @case('denah-ukur'){{-- Ukuran meter: papan dan arena masing-masing membawa angka meterannya sendiri — denah berskala saja tidak menjawab "berapa meter", dan dua ukuran itu memang dipatok terpisah. --}}
            <div class="feat-card">
                <div class="feat-mrow">
                    <span class="feat-sq"></span>
                    <span class="feat-bar feat-bar-title w-2/5"></span>
                    <span class="feat-pill feat-pill-lime feat-end">60 × 40 m</span>
                </div>
                <div class="feat-mrow">
                    <span class="feat-sq"></span>
                    <span class="feat-bar feat-bar-sm w-3/5"></span>
                    <span class="feat-pill feat-pill-accent feat-end">14 × 9 m</span>
                </div>
            </div>
        @break

        @case('daftar-ulang'){{-- Meja daftar ulang: kartu dipindai, peserta ditandai hadir. Satu-satunya miniatur yang memakai .feat-tick. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-2/5"></span>
                <span class="feat-pill feat-pill-lime feat-end">Hadir 24</span>
            </div>
            <div class="feat-mrow"><span class="feat-av"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-tick"></span></div>
            <div class="feat-mrow"><span class="feat-av"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-tick"></span></div>
            <div class="feat-card feat-card-row">
                <span class="feat-qr"></span>
                <span class="feat-mcol"><span class="feat-bar feat-bar-sm w-4/5"></span><span class="feat-bar feat-bar-sm w-1/2"></span></span>
            </div>
        @break

        @case('rundown'){{-- Linimasa: jam di kiri, acara di kanan. Baris yang sedang berjalan ditandai. --}}
            <div class="feat-mrow"><span class="feat-pill feat-pill-accent">08.00</span><span class="feat-bar feat-bar-sm"></span></div>
            <div class="feat-mrow"><span class="feat-pill feat-pill-accent">09.30</span><span class="feat-bar feat-bar-sm w-4/5"></span></div>
            <div class="feat-card feat-card-row feat-card-hi"><span class="feat-pill feat-pill-lime">10.15</span><span class="feat-bar feat-bar-sm"></span></div>
            <div class="feat-mrow"><span class="feat-pill">13.00</span><span class="feat-bar feat-bar-sm w-3/5"></span></div>
        @break

        @case('jadwal'){{-- Jadwal pertandingan: dua pihak di kedua sisi, "vs" di tengah. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-1/3"></span>
                <span class="feat-pill feat-pill-accent feat-end">Babak 1</span>
            </div>
            <div class="feat-card feat-card-row"><span class="feat-bar feat-bar-sm"></span><span class="feat-pill">vs</span><span class="feat-bar feat-bar-sm"></span></div>
            <div class="feat-card feat-card-row"><span class="feat-bar feat-bar-sm"></span><span class="feat-pill">vs</span><span class="feat-bar feat-bar-sm"></span></div>
            <div class="feat-mrow"><span class="feat-pill feat-pill-lime">Lapangan A</span><span class="feat-bar feat-bar-sm w-1/4"></span></div>
        @break

        @case('format-nilai'){{-- Rubrik: kriteria dengan panjang bar = bobotnya, persentase di kanan. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-2/5"></span>
                <span class="feat-pill feat-pill-accent feat-end">Bobot</span>
            </div>
            <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-2/5"></span><span class="feat-ballot w-3/5"></span><span class="feat-pill">30%</span></div>
            <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-2/5"></span><span class="feat-ballot w-1/2"></span><span class="feat-pill">25%</span></div>
            <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-2/5"></span><span class="feat-ballot w-1/4"></span><span class="feat-pill">15%</span></div>
        @break

        @case('perbandingan'){{-- Satu peserta, nilai tiga juri berdampingan. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-1/2"></span>
                <span class="feat-pill feat-pill-accent feat-end">3 juri</span>
            </div>
            <div class="feat-mrow"><span class="feat-pill">Juri 1</span><span class="feat-bar feat-bar-sm w-3/5"></span><span class="feat-numbox feat-end"></span></div>
            <div class="feat-mrow"><span class="feat-pill">Juri 2</span><span class="feat-bar feat-bar-sm w-2/5"></span><span class="feat-numbox feat-end"></span></div>
            <div class="feat-mrow"><span class="feat-pill feat-pill-accent">Juri 3</span><span class="feat-bar feat-bar-sm w-1/5"></span><span class="feat-numbox feat-end"></span></div>
        @break

        @case('rekap-nilai'){{-- Tabel nilai: kolom peserta, kotak angka per kriteria, ekspor di bawah. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-1/2"></span>
                <span class="feat-pill feat-pill-accent feat-end">21 peserta</span>
            </div>
            <div class="feat-card">
                <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-1/3"></span><span class="feat-numbox"></span><span class="feat-numbox"></span></div>
                <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-2/5"></span><span class="feat-numbox"></span><span class="feat-numbox"></span></div>
                <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-1/4"></span><span class="feat-numbox"></span><span class="feat-numbox"></span></div>
            </div>
            <div class="feat-mrow"><span class="feat-pill feat-pill-lime">Unduh PDF</span><span class="feat-pill">CSV</span></div>
        @break

        @case('podium'){{-- Kategori juara: peringkat ditetapkan dari data, bukan diketik ulang. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-1/3"></span>
                <span class="feat-pill feat-pill-lime feat-end">Final</span>
            </div>
            <div class="feat-card feat-card-row feat-card-hi"><span class="feat-rk">1</span><span class="feat-bar feat-bar-lg"></span><span class="feat-pill feat-pill-accent">Umum</span></div>
            <div class="feat-card feat-card-row"><span class="feat-rk">2</span><span class="feat-bar feat-bar-sm"></span><span class="feat-pill">Harapan</span></div>
            <div class="feat-card feat-card-row"><span class="feat-rk">3</span><span class="feat-bar feat-bar-sm w-3/5"></span><span class="feat-pill">Harapan</span></div>
        @break

        @case('hall-of-fame'){{-- Juara lintas event: arsip yang tetap berguna setelah acara selesai. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-2/5"></span>
                <span class="feat-pill feat-pill-accent feat-end">2026</span>
            </div>
            <div class="feat-card">
                <div class="feat-mrow"><span class="feat-av"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-pill feat-pill-lime">Juara 1</span></div>
                <div class="feat-mrow"><span class="feat-av"></span><span class="feat-bar feat-bar-sm w-3/5"></span><span class="feat-pill">Juara 2</span></div>
            </div>
            <div class="feat-mrow"><span class="feat-pill">2025</span><span class="feat-bar feat-bar-sm w-1/3"></span></div>
        @break

        @case('videotron'){{-- Layar venue: satu panggung besar, sponsor berputar di bawahnya. --}}
            <div class="feat-stage">
                <div class="feat-lw"><span class="feat-lw-nm"></span><span class="feat-lw-sc"></span></div>
            </div>
            <div class="feat-wall">
                <span class="feat-logobox feat-logobox-hi"><i></i></span>
                <span class="feat-logobox"><i></i></span>
                <span class="feat-logobox"><i></i></span>
            </div>
        @break

        @case('booster'){{-- Paket suara tambahan: makin besar paketnya, makin panjang bilahnya. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-1/3"></span>
                <span class="feat-pill feat-pill-lime feat-end">Booster</span>
            </div>
            <div class="feat-card feat-card-row feat-card-hi"><span class="feat-pill feat-pill-accent">10 suara</span><span class="feat-bar feat-bar-sm"></span></div>
            <div class="feat-card feat-card-row"><span class="feat-pill">25 suara</span><span class="feat-bar feat-bar-sm w-2/3"></span></div>
            <div class="feat-card feat-card-row"><span class="feat-pill">50 suara</span><span class="feat-bar feat-bar-sm w-1/3"></span></div>
        @break

        @case('hasil-voting'){{-- Perolehan suara bertumpuk dengan daftar transaksinya. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-2/5"></span>
                <span class="feat-pill feat-pill-accent feat-end">Rp 1,2 jt</span>
            </div>
            <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-1/3"></span><span class="feat-ballot w-4/5"></span><span class="feat-pill feat-pill-lime">420</span></div>
            <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-1/3"></span><span class="feat-ballot w-3/5"></span><span class="feat-pill">310</span></div>
            <div class="feat-card feat-card-row"><span class="feat-av"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-pill">Sukses</span></div>
        @break

        @case('komentar'){{-- Moderasi: tiap komentar menunggu setuju atau sembunyi. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-2/5"></span>
                <span class="feat-pill feat-pill-accent feat-end">Tunggu 3</span>
            </div>
            <div class="feat-card feat-card-row">
                <span class="feat-av"></span>
                <span class="feat-mcol"><span class="feat-bar feat-bar-sm w-4/5"></span><span class="feat-bar feat-bar-sm w-1/2"></span></span>
                <span class="feat-pill feat-pill-lime">Setuju</span>
            </div>
            <div class="feat-card feat-card-row">
                <span class="feat-av"></span>
                <span class="feat-mcol"><span class="feat-bar feat-bar-sm w-3/5"></span><span class="feat-bar feat-bar-sm w-1/3"></span></span>
                <span class="feat-pill">Sembunyi</span>
            </div>
        @break

        @case('checkin'){{-- Pintu masuk: QR dipindai, tiket ditandai terpakai. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-1/2"></span>
                <span class="feat-pill feat-pill-lime feat-end">Masuk</span>
            </div>
            <div class="feat-card">
                <div class="feat-mrow">
                    <span class="feat-qr"></span>
                    <span class="feat-mcol">
                        <span class="feat-bar feat-bar-title w-4/5"></span>
                        <span class="feat-bar feat-bar-sm w-3/5"></span>
                        <span class="feat-pill feat-pill-lime">Valid</span>
                    </span>
                </div>
            </div>
            <div class="feat-mrow"><span class="feat-pill">Sudah dipakai</span><span class="feat-bar feat-bar-sm w-1/4"></span></div>
        @break

        @case('dokumen'){{-- Juknis: baris berkas dengan jenis dan tombol unduhnya. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-2/5"></span>
                <span class="feat-pill feat-pill-accent feat-end">Juknis</span>
            </div>
            <div class="feat-mrow"><span class="feat-sq"></span><span class="feat-mcol"><span class="feat-bar feat-bar-sm w-4/5"></span><span class="feat-bar feat-bar-sm w-1/3"></span></span><span class="feat-pill feat-pill-lime">PDF</span></div>
            <div class="feat-mrow"><span class="feat-sq"></span><span class="feat-mcol"><span class="feat-bar feat-bar-sm w-3/5"></span><span class="feat-bar feat-bar-sm w-1/4"></span></span><span class="feat-pill">PDF</span></div>
            <div class="feat-card feat-card-row feat-card-hi"><span class="feat-sq"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-pill feat-pill-accent">Unduh</span></div>
        @break

        @case('galeri'){{-- Galeri foto event — kotak 3:2, satu ditandai sebagai yang sedang dibuka. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-1/3"></span>
                <span class="feat-pill feat-pill-accent feat-end">48 foto</span>
            </div>
            <div class="feat-wall">
                <span class="feat-logobox feat-logobox-hi"><i></i></span>
                <span class="feat-logobox"><i></i></span>
                <span class="feat-logobox"><i></i></span>
                <span class="feat-logobox"><i></i></span>
                <span class="feat-logobox"><i></i></span>
                <span class="feat-logobox"><i></i></span>
            </div>
        @break

        @case('faq'){{-- Akordeon: satu pertanyaan terbuka, sisanya menunggu diklik. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-2/5"></span>
                <span class="feat-pill feat-pill-lime feat-end">FAQ</span>
            </div>
            <div class="feat-card">
                <span class="feat-bar feat-bar-sm w-4/5"></span>
                <span class="feat-bar feat-bar-sm w-3/5"></span>
            </div>
            <div class="feat-card feat-card-row"><span class="feat-bar feat-bar-sm"></span><span class="feat-pill">+</span></div>
            <div class="feat-card feat-card-row"><span class="feat-bar feat-bar-sm w-2/3"></span><span class="feat-pill">+</span></div>
        @break

        @case('notifikasi'){{-- Pengumuman: satu pesan, dua sasaran, status terkirim di bawah. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-1/2"></span>
                <span class="feat-pill feat-pill-accent feat-end">Kirim</span>
            </div>
            <div class="feat-card">
                <span class="feat-bar feat-bar-sm w-4/5"></span>
                <span class="feat-bar feat-bar-sm w-3/5"></span>
                <div class="feat-mrow"><span class="feat-pill">Semua peserta</span><span class="feat-pill feat-pill-lime">Juara</span></div>
            </div>
            <div class="feat-mrow"><span class="feat-av"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-pill feat-pill-lime">Terkirim</span></div>
        @break

        @case('log'){{-- Activity log: siapa mengubah apa, jamnya di kanan. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-2/5"></span>
                <span class="feat-pill feat-pill-accent feat-end">Hari ini</span>
            </div>
            <div class="feat-mrow"><span class="feat-av"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-pill feat-pill-lime">09.14</span></div>
            <div class="feat-mrow"><span class="feat-av"></span><span class="feat-bar feat-bar-sm w-4/5"></span><span class="feat-pill">09.02</span></div>
            <div class="feat-mrow"><span class="feat-av"></span><span class="feat-bar feat-bar-sm w-3/5"></span><span class="feat-pill">08.47</span></div>
        @break

        @case('tenant'){{-- Stan tenant: nomor, nama, dan siapa yang sudah mengisi. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-1/4"></span>
                <span class="feat-pill feat-pill-accent feat-end">24 stan</span>
            </div>
            <div class="feat-mrow"><span class="feat-pill">A-01</span><span class="feat-bar feat-bar-sm w-1/3"></span><span class="feat-pill feat-pill-lime feat-end">Terisi</span></div>
            <div class="feat-mrow"><span class="feat-pill">A-02</span><span class="feat-bar feat-bar-sm w-1/4"></span></div>
            <div class="feat-card feat-card-row feat-card-hi"><span class="feat-sq"></span><span class="feat-bar feat-bar-sm"></span><span class="feat-pill feat-pill-accent">A-03</span></div>
        @break

        @case('keuangan'){{-- Tiga sumber pemasukan, panjang bilah = besarnya. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-1/3"></span>
                <span class="feat-pill feat-pill-lime feat-end">Rp 8,4 jt</span>
            </div>
            <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-1/4"></span><span class="feat-ballot w-full"></span></div>
            <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-1/4"></span><span class="feat-ballot w-2/3"></span></div>
            <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-1/4"></span><span class="feat-ballot w-1/3"></span></div>
            <div class="feat-mrow"><span class="feat-pill feat-pill-accent">Laporan PDF</span></div>
        @break

        @case('ttd'){{-- Dokumen resmi: tanda tangan dan cap, plus rekening penerimaannya. --}}
            <div class="feat-card items-center text-center">
                <span class="feat-bar feat-bar-title w-1/2"></span>
                <span class="feat-bar feat-bar-sm w-3/5"></span>
                <div class="feat-mrow mt-2 w-full"><span class="feat-sign"></span><span class="feat-seal"></span></div>
            </div>
            <div class="feat-mrow"><span class="feat-pill">Bank BCA</span><span class="feat-bar feat-bar-sm w-1/3"></span></div>
        @break

        @case('paket'){{-- Feature gating: yang aktif dicentang lime, yang terkunci biru dan menunggu dibuka. --}}
            <div class="feat-mrow">
                <span class="feat-bar feat-bar-title w-1/3"></span>
                <span class="feat-pill feat-pill-lime feat-end">Gratis</span>
            </div>
            <div class="feat-card">
                <div class="feat-mrow"><span class="feat-bar feat-bar-sm"></span><span class="feat-pill feat-pill-lime">Aktif</span></div>
                <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-3/5"></span><span class="feat-pill feat-pill-accent">Buka</span></div>
                <div class="feat-mrow"><span class="feat-bar feat-bar-sm w-2/5"></span><span class="feat-pill feat-pill-accent">Buka</span></div>
            </div>
            <div class="feat-mrow"><span class="feat-pill feat-pill-accent">Bayar sekali</span><span class="feat-bar feat-bar-sm w-1/4"></span></div>
        @break

        @default{{-- Fitur yang ditambahkan admin sendiri: ikon generik, miniatur generik. --}}
            <div class="feat-card">
                <div class="feat-mrow"><span class="feat-sq"></span><span class="feat-bar feat-bar-title w-3/5"></span></div>
                <span class="feat-bar feat-bar-sm w-4/5"></span>
                <span class="feat-bar feat-bar-sm w-3/5"></span>
                <div class="feat-mrow"><span class="feat-pill feat-pill-accent">{{ $judul }}</span></div>
            </div>
    @endswitch
</div>
