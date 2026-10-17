{{--
    Kanvas denah, satu-satunya tempat zona DAN alur digambar.

    Dipakai bersama oleh editor panitia, halaman publik, dan PDF; kalau tiap
    layar menggambar sendiri, ketiganya pelan-pelan berbeda.

    Posisi zona tersimpan sebagai persen papan, jadi satu markup ini tampil
    proporsional di lebar apa pun. `--rasio` yang menyetel tinggi kanvas.

    Alur digambar sebagai ruas garis hasil rotasi, bukan SVG: dompdf tak
    menggambar SVG sebaris sama sekali (nol operator), sedangkan div tipis yang
    diputar tergambar benar di dompdf maupun di peramban. Satu renderer untuk
    tiga permukaan.

    Parameter:
      $layout      CompetitionLayout
      $zones       koleksi LayoutZone (opsional; default $layout->zones)
      $interaktif  bool; editor menyeret zona, publik & PDF tidak
      $terpilihId  int|null; zona yang sedang dipilih di editor
      $kisi        bool; tampilkan kisi latar
      $mode        'zona' | 'alur'; apa yang bisa diseret di editor
--}}
@php
    $zones = $zones ?? $layout->zones;
    $interaktif = $interaktif ?? false;
    $terpilihId = $terpilihId ?? null;
    $kisi = $kisi ?? false;
    $mode = $mode ?? 'zona';
    $rasio = max(0.2, min(6, (float) $layout->rasio));

    // Di editor alur tetap digambar walau panitia menyembunyikannya dari
    // publik; kalau tidak, tak ada lagi yang bisa disunting.
    $tampilkanAlur = $interaktif ? $layout->punya_alur : $layout->alur_tampil;

    // Pita ukuran digambar di keempat tepi kanvas, jadi kanvasnya butuh bantalan
    // selebar pitanya supaya zona tak tertimbun. Di editor bantalannya lebih
    // lebar karena pitanya juga memuat tombol pengubah ukuran papan.
    //
    // Lebarnya ditulis sebagai persen di atribut style, bukan lewat variabel
    // CSS: dompdf tak menjamin mengenal custom property, dan pita yang gagal
    // dihitung membuat denah di kertas bergeser sejauh lebar pitanya.
    $pita = $pita ?? true;
    $bantalan = $pita ? ($interaktif ? 3.6 : 2.6) : 0;

    // Persen tinggi ≠ persen lebar. Kanvas 16:9 butuh bantalan tegak 1,78×
    // lipat supaya pita atas setebal pita kiri — tanpa itu, pita atas terlihat
    // gepeng di denah memanjang dan gemuk di denah tegak.
    $bantalanY = round($bantalan * $rasio, 3);

    // Bingkai ditulis left/top/width/height eksplisit, bukan inset atau
    // variabel CSS: dompdf tak menjamin keduanya, dan bingkai yang gagal
    // dihitung menggeser seluruh denah sejauh lebar pitanya.
    $bingkaiX = $bantalan;
    $bingkaiY = $bantalanY;
    $bingkaiW = round(100 - 2 * $bantalan, 3);
    $bingkaiH = round(100 - 2 * $bantalanY, 3);

    // Panjang pita dipatok dalam px, bukan persen: pita ukuran itu penggaris,
    // dan lurik yang melebar mengikuti papan 60 m tak lagi terbaca sebagai
    // lurik. Jumlah span dihitung dari perkiraan lebar terbesar kanvas; lebihnya
    // dipangkas overflow, bukan direntangkan.
    $lurik = [];
    if ($pita) {
        $jumlahLurik = (int) min(80, max(16, ceil(1100 / 14)));
        $lurik = array_fill(0, $jumlahLurik, null);
    }
@endphp

{{-- Sengaja TANPA wire:ignore.

     wire:ignore menghentikan SEMUA pembaruan, jadi menambah atau menghapus zona
     tidak pernah muncul di kanvas. Blade tetap sumber kebenaran posisi; Alpine
     cuma memindahkan kotaknya selama seretan, lalu server mengirim posisi
     finalnya kembali lewat morph. --}}
<div
    class="denah-kanvas @if($interaktif) denah-interaktif @endif @if($mode === 'alur') denah-alur-mode @endif"
    style="--rasio: {{ number_format($rasio, 4, '.', '') }};"
    @if($interaktif)
        data-denah-editor
        data-denah-mode="{{ $mode }}"
        wire:key="kanvas-{{ $layout->id }}"
    @endif
>
    {{-- Zona & alur digambar di dalam bingkai, bukan langsung di kanvas.
         Bingkai inilah yang menyusut selebar pita ukuran, jadi persen zona
         tetap dihitung dari papan — kalau zona ditempel langsung ke kanvas,
         denahnya bergeser sejauh lebar pitanya. --}}
    <div
        class="denah-bingkai @if($kisi) denah-kisi @endif"
        style="left: {{ $bingkaiX }}%; top: {{ $bingkaiY }}%; width: {{ $bingkaiW }}%; height: {{ $bingkaiH }}%;"
    >
    @forelse($zones as $zona)
        <div
            wire:key="zona-{{ $zona->id }}"
            class="zone {{ $zona->kelas_tipe }} @if($terpilihId === $zona->id) is-terpilih @endif"
            style="{{ $zona->gaya }}"
            data-zona="{{ $zona->id }}"
            @if($interaktif) data-zona-nama="{{ $zona->name }}" @endif
        >
            <span class="zone-label">{{ $zona->name }}</span>
            @if($zona->capacity)
                <span class="zone-sub">{{ $zona->capacity }}</span>
            @endif
            {{-- Pegangan ubah ukuran: sudut kanan-bawah, seukuran jari. --}}
            @if($interaktif)
                <span class="zona-hnd" data-hnd="1" title="Tarik untuk mengubah ukuran"></span>
            @endif
        </div>
    @empty
        @unless($interaktif)
            <div class="denah-kosong">Denah belum berisi zona.</div>
        @endunless
    @endforelse

    {{-- Alur digambar SESUDAH zona supaya berada di atasnya: garis alur yang
         tertimbun kotak arena tak terbaca justru di denah yang paling padat. --}}
    @if($tampilkanAlur)
        <div class="alur-lapis">
            @foreach($layout->alurSegmen() as $i => $ruas)
                <span
                    wire:key="ruas-{{ $i }}"
                    class="alur-ruas"
                    style="left: {{ $ruas['x'] }}%; top: {{ $ruas['y'] }}%; width: {{ $ruas['panjang'] }}%; transform: rotate({{ $ruas['sudut'] }}deg);"
                    @if($interaktif && $mode === 'alur')
                        data-alur-ruas="{{ $i }}"
                        title="Klik untuk menyisipkan titik di sini"
                    @endif
                ><span class="alur-garis"></span></span>
            @endforeach

            @foreach($layout->alur_titik as $i => $titik)
                <span
                    wire:key="titik-{{ $i }}"
                    class="alur-titik alur-{{ $titik['kind'] }} @if($interaktif && $mode === 'alur' && $layout->route_points && $i === ($terpilihTitik ?? null)) is-terpilih @endif"
                    style="left: {{ $titik['x'] }}%; top: {{ $titik['y'] }}%;"
                    @if($interaktif && $mode === 'alur') data-alur-titik="{{ $i }}" @endif
                >
                    @if($titik['heading'] !== null)
                        {{-- Panah arah hadap: derajat searah jarum jam dari atas. --}}
                        <span class="alur-hadap" style="transform: rotate({{ $titik['heading'] }}deg);"></span>
                    @endif
                    @if($titik['label'] !== '')
                        <span class="alur-label">{{ $titik['label'] }}</span>
                    @endif
                </span>
            @endforeach
        </div>
    @endif
    </div>{{-- /.denah-bingkai --}}

    @if($pita)
        {{-- Pita ukuran mengelilingi keempat tepi, pola garis polisi.

             Luriknya span berselang-seling, bukan repeating-linear-gradient:
             dompdf mengabaikan gradien sepenuhnya (terukur nol operator). Span
             juga membuat luriknya sama besar di layar dan di kertas, karena
             lebarnya px — bukan persen yang ikut melebar bersama papan.

             Angkanya cuma sekali di tiap sisi: pita ukur yang mengulang
             "40 × 22 m" di empat tepi justru terbaca seperti empat ukuran. --}}
        <div class="denah-pita denah-pita-atas" style="left: 0; top: 0; width: 100%; height: {{ $bantalanY }}%;">
            <span class="pita-lurik">@foreach($lurik as $i)<span></span>@endforeach</span>
            <span class="pita-teks">{{ $layout->ukuran_teks }}</span>
        </div>
        <div class="denah-pita denah-pita-bawah" style="left: 0; bottom: 0; width: 100%; height: {{ $bantalanY }}%;">
            <span class="pita-lurik">@foreach($lurik as $i)<span></span>@endforeach</span>
        </div>
        <div class="denah-pita denah-pita-kiri" style="left: 0; top: {{ $bantalanY }}%; width: {{ $bantalan }}%; height: {{ $bingkaiH }}%;">
            <span class="pita-lurik pita-lurik-tegak">@foreach($lurik as $i)<span></span>@endforeach</span>
            <span class="pita-teks pita-teks-tegak">{{ $layout->tinggi_teks }}</span>
        </div>
        <div class="denah-pita denah-pita-kanan" style="right: 0; top: {{ $bantalanY }}%; width: {{ $bantalan }}%; height: {{ $bingkaiH }}%;">
            <span class="pita-lurik pita-lurik-tegak">@foreach($lurik as $i)<span></span>@endforeach</span>
            <span class="pita-teks pita-teks-tegak">{{ $layout->tinggi_teks }}</span>
        </div>

        @if($interaktif)
            {{-- Tombol ubah ukuran papan ditaruh di pita, bukan di bar atas:
                 ukuran lapangan dan tempat mengubahnya memang satu hal.

                 Tingginya disetel setinggi pitanya, bukan 22px tetap: pita
                 atas menipis di kanvas sempit, dan tombol yang lebih tinggi
                 dari pitanya menonjol menutupi sudut papan. --}}
            <button type="button" class="pita-ubah" wire:click="bukaPapanModal"
                    style="right: {{ $bantalan }}%; top: 0; height: {{ $bantalanY }}%;"
                    title="Ubah ukuran papan">
                <i class="ti ti-ruler-measure"></i>
            </button>
        @endif
    @endif
</div>

@once
    <style>
        /* Kanvas memakai rasio papan supaya denah 40×22 m dan 22×40 m tidak
           dipaksa ke kotak yang sama. */
        .denah-kanvas {
            position: relative;
            width: 100%;
            aspect-ratio: var(--rasio);
            background: #f8fafc;
            border: 2px solid #cbd5e1;
            border-radius: .5rem;
            overflow: hidden;
            touch-action: none;
        }
        .denah-kisi {
            background-image:
                linear-gradient(to right, #e2e8f0 1px, transparent 1px),
                linear-gradient(to bottom, #e2e8f0 1px, transparent 1px);
            background-size: 5% 5%;
        }

        /* Bingkai = papan sesungguhnya. Posisinya ditulis dari Blade, bukan
           dihitung di sini, supaya bantalan tegak bisa dikalikan rasio. */
        .denah-bingkai {
            position: absolute;
            box-sizing: border-box;
        }

        /* ── Pita ukuran: garis polisi di keempat tepi ─────────────────────

           Luriknya span berselang-seling, bukan repeating-linear-gradient:
           dompdf mengabaikan gradien sepenuhnya. Span-nya juga dipangkas
           overflow, jadi panjang pita tak perlu tahu lebar kanvas — sisa
           span-nya cuma tak terlihat. */
        .denah-kanvas .denah-pita {
            position: absolute;
            overflow: hidden;
            background: #facc15;
            border: 1px solid #1e293b;
            box-sizing: border-box;
            z-index: 2;
        }
        /* Span lurik ditata inline-block, bukan flex: dompdf baru mengenal
           flexbox sebagian, sedangkan baris inline-block tergambar sama di
           peramban maupun di kertas. font-size:0 menutup celah putih antar
           span, yang di sini justru terbaca sebagai garis kedua. */
        .denah-kanvas .pita-lurik {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            font-size: 0;
            line-height: 0;
            white-space: nowrap;
        }
        .denah-kanvas .pita-lurik > span {
            display: inline-block;
            width: 14px;
            height: 100%;
            background: #1e293b;
        }
        .denah-kanvas .pita-lurik-tegak { white-space: normal; }
        .denah-kanvas .pita-lurik-tegak > span { display: block; width: 100%; height: 14px; }

        /* Sisi mendatar & tegak: ukurannya ditulis dari Blade, bukan di sini.
           Bantalan tegak = bantalan × rasio, dan rasio itu milik denahnya —
           angka tetap di CSS akan membuat pita atas setebal pita kiri hanya
           pada denah 1:1. */

        .denah-kanvas .pita-teks {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            white-space: nowrap;
            font-size: clamp(.5rem, 1vw, .7rem);
            font-weight: 700;
            color: #1e293b;
            background: #facc15;
            padding: 0 5px;
            letter-spacing: .02em;
        }
        /* Angka di pita tegak diputar — dompdf menggambar teks berputar dengan
           benar, terukur sebagai matriks rotasi di dalam PDF-nya. */
        .denah-kanvas .pita-teks-tegak {
            transform: translate(-50%, -50%) rotate(-90deg);
        }

        /* Tombol ubah ukuran papan, duduk di pita atas di sebelah kiri pita
           kanan. Posisinya dari Blade: tepi pitanya persen-tinggi, dan itu
           tergantung rasio denahnya.

           z-index-nya di atas pita (2), tapi tetap di bawah lapis alur (4)
           supaya tak pernah mencuri seretan titik alur. */
        .denah-kanvas .pita-ubah {
            position: absolute;
            width: 26px;
            min-height: 16px;
            padding: 0;
            border: 1px solid #1e293b;
            box-sizing: border-box;
            border-radius: 0 0 0 .35rem;
            background: #facc15;
            color: #1e293b;
            font-size: .8rem;
            line-height: 1;
            cursor: pointer;
            z-index: 3;
        }
        .denah-kanvas .pita-ubah:hover { background: #fde047; }
        .denah-kanvas .denah-kosong {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: .875rem;
        }
        .denah-kanvas .zone {
            position: absolute;
            box-sizing: border-box;
            border: 1.5px solid #64748b;
            border-radius: .35rem;
            background: #e2e8f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            text-align: center;
            padding: 2px 4px;
            font-size: clamp(.45rem, 1.05vw, .78rem);
            line-height: 1.15;
            color: #1e293b;
            user-select: none;
        }
        .denah-kanvas .zone-label { font-weight: 600; }
        .denah-kanvas .zone-sub { font-size: .85em; opacity: .75; }

        /* Warna per jenis: bahasa visual yang sama di editor, publik, PDF. */
        .zone.z-arena   { background: #dcfce7; border-color: #16a34a; }
        .zone.z-dp      { background: #dbeafe; border-color: #2563eb; }
        .zone.z-juri    { background: #fef9c3; border-color: #ca8a04; }
        .zone.z-tribun  { background: #f1f5f9; border-color: #94a3b8; }
        .zone.z-sekre   { background: #fae8ff; border-color: #a21caf; }
        .zone.z-jalur   { background: #ffe4e6; border-color: #e11d48; }
        .zone.z-arah    { background: transparent; border-style: dashed; border-color: #94a3b8; }
        .zone.z-teks    { background: transparent; border-color: transparent; }

        /* Tiang bendera: tonggak, bukan bidang. Tiangnya batang tegak di tengah,
           benderanya persegi yang di-skew supaya terbaca sebagai kain.

           Skew, bukan segitiga dari border CSS: dompdf hanya mengeluarkan satu
           garis lurus dari border segitiga (terukur), sedangkan skewY tergambar
           benar di peramban maupun di kertas. */
        .zone.z-bendera {
            background: transparent;
            border-color: transparent;
            overflow: visible;
            justify-content: flex-end;
            padding: 0;
        }
        .zone.z-bendera::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            width: 3px;
            height: 100%;
            margin-left: -1.5px;
            background: #475569;
            border-radius: 1.5px;
        }
        .zone.z-bendera::after {
            content: '';
            position: absolute;
            left: 50%;
            top: 2%;
            width: 70%;
            height: 22%;
            background: #dc2626;
            transform: skewY(-12deg);
            transform-origin: 0 0;
        }
        /* Namanya diberi latar supaya tak terbaca menyatu dengan tiangnya. */
        .zone.z-bendera .zone-label {
            position: relative;
            background: rgba(255, 255, 255, .85);
            padding: 0 3px;
            border-radius: 2px;
        }

        .denah-kanvas .zone.is-terpilih {
            outline: 3px solid #2563eb;
            outline-offset: 1px;
            z-index: 5;
        }

        .denah-interaktif .zone { cursor: grab; }
        .denah-interaktif .zone:active { cursor: grabbing; }

        .denah-kanvas .zona-hnd {
            position: absolute;
            right: 0;
            bottom: 0;
            width: 14px;
            height: 14px;
            background: #2563eb;
            border-top-left-radius: .35rem;
            cursor: nwse-resize;
            opacity: 0;
            transition: opacity .12s;
        }
        .denah-interaktif .zone:hover .zona-hnd,
        .denah-interaktif .zone.is-terpilih .zona-hnd { opacity: 1; }

        /* Menyeret: kanvas tidak boleh ikut memilih teks di sekitarnya. */
        body.denah-menyeret { cursor: grabbing; user-select: none; }

        /* ── Alur gerak pasukan ────────────────────────────────────────────

           Ruas garis = span tipis yang diputar. Panjangnya persen lebar,
           sudutnya dihitung di CompetitionLayout::alurSegmen(). Karena
           transform-origin ada di ujung kiri, ujung pangkalnya tetap di titik
           awalnya berapa pun sudutnya.

           Ketebalannya dipatok px, bukan persen: garis alur harus tetap
           sekurus ini di denah 20 m maupun 60 m, dan di PDF mm. */
        .denah-kanvas .alur-lapis {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 4;
        }
        .denah-kanvas .alur-ruas {
            position: absolute;
            height: 0;
            transform-origin: 0 50%;
        }
        .denah-kanvas .alur-garis {
            position: absolute;
            left: 0;
            top: -2px;
            width: 100%;
            height: 4px;
            background: #b91c1c;
            border-radius: 2px;
        }

        /* Titik alur. Bentuknya yang membedakan, bukan warnanya — denah ini
           sering dicetak hitam-putih. */
        .denah-kanvas .alur-titik {
            position: absolute;
            width: 0;
            height: 0;
            z-index: 6;
        }
        .denah-kanvas .alur-titik::before {
            content: '';
            position: absolute;
            left: -6px;
            top: -6px;
            width: 12px;
            height: 12px;
            background: #b91c1c;
        }
        /* Awal & akhir: bulat, berlubang. */
        .denah-kanvas .alur-start::before,
        .denah-kanvas .alur-end::before {
            border-radius: 50%;
            background: #fff;
            border: 3px solid #b91c1c;
        }
        /* Belok: belah ketupat. */
        .denah-kanvas .alur-turn::before {
            left: -5px;
            top: -5px;
            width: 10px;
            height: 10px;
            transform: rotate(45deg);
        }
        /* Aba-aba: kotak penuh. */
        .denah-kanvas .alur-aba_aba::before {
            left: -5px;
            top: -5px;
            width: 10px;
            height: 10px;
        }

        /* Panah arah hadap: batang yang diputar dari titiknya. Derajat 0 =
           menghadap atas kanvas, searah jarum jam. */
        .denah-kanvas .alur-hadap {
            position: absolute;
            left: -1.5px;
            top: -26px;
            width: 3px;
            height: 20px;
            background: #b91c1c;
            transform-origin: 50% 100%;
            border-radius: 1.5px;
        }

        /* Label titik. nowrap bukan hiasan: dompdf memecah label ber-spasi jadi
           dua operasi teks terpisah yang jatuh di baris berbeda. */
        .denah-kanvas .alur-label {
            position: absolute;
            left: 10px;
            top: -9px;
            white-space: nowrap;
            font-size: clamp(.45rem, 1vw, .72rem);
            font-weight: 600;
            color: #7f1d1d;
            background: rgba(255, 255, 255, .85);
            padding: 0 3px;
            border-radius: 2px;
            line-height: 1.3;
        }

        /* Titik terpilih di editor. */
        .denah-kanvas .alur-titik.is-terpilih::before {
            outline: 3px solid #2563eb;
            outline-offset: 2px;
        }

        /* Di mode alur, zona tak boleh menangkap seretan: titik alur kerap
           jatuh tepat di atas kotak arena. */
        .denah-alur-mode .zone { pointer-events: none; }
        .denah-alur-mode .alur-lapis { pointer-events: auto; }
        .denah-alur-mode .alur-titik { cursor: grab; }
        .denah-alur-mode .alur-titik:active { cursor: grabbing; }
        .denah-alur-mode .alur-ruas { cursor: copy; }

        /* dompdf hanya paham CSS sederhana; tak ada aspect-ratio. Tinggi
           kanvas dipatok supaya denah di PDF tidak runtuh jadi garis tipis. */
        .denah-cetak .denah-kanvas { aspect-ratio: auto; height: 150mm; }

        /* Garis alur di kertas ditebalkan sedikit: 4px pada 255mm terbaca
           putus-putus oleh printer kantor. */
        .denah-cetak .denah-kanvas .alur-garis { height: 5px; top: -2.5px; }
        .denah-cetak .denah-kanvas .alur-label { font-size: 11px; }

        /* Pita di kertas. clamp() tak dikenal dompdf, jadi ukuran teksnya
           dipatok; luriknya dirapatkan sedikit karena halaman ini dibaca dari
           jarak dekat, bukan dari seberang lapangan. */
        .denah-cetak .denah-kanvas .pita-teks { font-size: 9px; }
        .denah-cetak .denah-kanvas .pita-lurik > span { width: 11px; }
        .denah-cetak .denah-kanvas .pita-lurik-tegak > span { width: 100%; height: 11px; }
    </style>
@endonce

@if($interaktif)
    @script
    <script>
        // Seret zona & titik alur di kanvas.
        //
        // Vanilla JS, bukan Alpine.data: skrip ini perlu dijalankan ulang setiap
        // komponen Livewire dimuat atau dinavigasi, dan blok skrip Livewire
        // melakukannya tanpa mendaftarkan apa pun ke Alpine.
        //
        // Jangan sebut nama directive Livewire di berkas ini, walau cuma di
        // komentar JS: Blade mengompilasi semuanya, dan directive kedua di
        // dalam satu blok menelan seluruh sisa view lewat ob_start() yatim.
        //
        // Posisi dikirim sekali di akhir seretan; tiap pointermove akan membuat
        // satu seretan jadi ratusan request.
        (() => {
            const kanvas = document.querySelector('[data-denah-editor]');
            if (!kanvas || kanvas.dataset.siap) return;
            kanvas.dataset.siap = '1';

            // Batas & mode dibaca ulang tiap kali, bukan disimpan saat init:
            // Livewire memorph kanvas di tempat (wire:key-nya tetap), jadi
            // nilai yang ditangkap sekali akan basi begitu panitia berpindah
            // mode atau jendelanya diubah ukurannya.
            //
            // Yang diukur BINGKAI, bukan kanvas: kanvas punya bantalan selebar
            // pita ukuran, dan persen zona dihitung dari papan. Mengukur kanvas
            // membuat setiap seretan meleset sejauh lebar pitanya.
            const batasKanvas = () => (kanvas.querySelector('.denah-bingkai') ?? kanvas).getBoundingClientRect();
            const modeAlur = () => kanvas.dataset.denahMode === 'alur';

            let seret = null;

            const persen = (px, total) => Math.round((px / total) * 1000) / 10;

            kanvas.addEventListener('pointerdown', (e) => {
                const batas = batasKanvas();

                // Mode alur: titik alur lebih dulu. Zona sengaja dilewati di
                // mode ini (lihat .denah-alur-mode di gaya) karena titik alur
                // kerap jatuh tepat di atas arena.
                if (modeAlur()) {
                    const titik = e.target.closest('[data-alur-titik]');
                    const ruas = e.target.closest('[data-alur-ruas]');

                    if (ruas && !titik) {
                        e.preventDefault();
                        $wire.sisipTitik(Number(ruas.dataset.alurRuas));
                        return;
                    }

                    if (!titik) return;

                    e.preventDefault();
                    // mulaiX/mulaiY wajib ikut, sama seperti cabang zona:
                    // pointermove mengukur geseran dari titik tekannya, dan
                    // tanpa itu dx-nya NaN — titik bergerak di layar tapi tak
                    // pernah dianggap digeser, jadi lepasnya cuma memilih dan
                    // Livewire mengembalikan posisinya ke tempat semula.
                    seret = {
                        el: titik,
                        mode: 'titik',
                        i: Number(titik.dataset.alurTitik),
                        mulaiX: e.clientX,
                        mulaiY: e.clientY,
                        bergerak: false,
                    };
                    titik.setPointerCapture?.(e.pointerId);
                    document.body.classList.add('denah-menyeret');

                    return;
                }

                const zona = e.target.closest('[data-zona]');
                if (!zona) return;

                e.preventDefault();
                const kotak = zona.getBoundingClientRect();

                seret = {
                    el: zona,
                    id: zona.dataset.zona,
                    mode: e.target.dataset.hnd ? 'resize' : 'move',
                    mulaiX: e.clientX,
                    mulaiY: e.clientY,
                    // Geometri awal dalam persen, dihitung dari kanvas saat ini.
                    kiri: persen(kotak.left - batas.left, batas.width),
                    atas: persen(kotak.top - batas.top, batas.height),
                    lebar: persen(kotak.width, batas.width),
                    tinggi: persen(kotak.height, batas.height),
                    bergerak: false,
                };

                zona.setPointerCapture?.(e.pointerId);
                document.body.classList.add('denah-menyeret');
            });

            kanvas.addEventListener('pointermove', (e) => {
                if (!seret) return;

                const batas = batasKanvas();
                const dx = persen(e.clientX - seret.mulaiX, batas.width);
                const dy = persen(e.clientY - seret.mulaiY, batas.height);

                if (Math.abs(dx) > 0.4 || Math.abs(dy) > 0.4) seret.bergerak = true;

                if (seret.mode === 'titik') {
                    const x = Math.max(0, Math.min(100, persen(e.clientX - batas.left, batas.width)));
                    const y = Math.max(0, Math.min(100, persen(e.clientY - batas.top, batas.height)));

                    seret.el.style.left = x + '%';
                    seret.el.style.top = y + '%';
                    Object.assign(seret, { x, y });

                    return;
                }

                let x = seret.kiri, y = seret.atas, w = seret.lebar, h = seret.tinggi;

                if (seret.mode === 'move') {
                    // Digeser, bukan dilepas: zona yang keluar papan bisa hilang
                    // dan panitia tak punya cara mengambilnya kembali.
                    x = Math.max(0, Math.min(100 - w, seret.kiri + dx));
                    y = Math.max(0, Math.min(100 - h, seret.atas + dy));
                } else {
                    w = Math.max(2, Math.min(100 - seret.kiri, seret.lebar + dx));
                    h = Math.max(2, Math.min(100 - seret.atas, seret.tinggi + dy));
                }

                seret.el.style.left = x + '%';
                seret.el.style.top = y + '%';
                seret.el.style.width = w + '%';
                seret.el.style.height = h + '%';
                Object.assign(seret, { x, y, w, h });
            });

            const selesai = () => {
                if (!seret) return;

                const s = seret;
                seret = null;
                document.body.classList.remove('denah-menyeret');

                if (s.mode === 'titik') {
                    if (s.bergerak) {
                        $wire.simpanTitik(s.i, s.x, s.y);
                    } else {
                        $wire.pilihTitik(s.i);
                    }

                    return;
                }

                // Klik tanpa geser = pilih zona, bukan simpan posisi.
                if (!s.bergerak) {
                    $wire.pilihZona(Number(s.id));
                    return;
                }

                $wire.simpanPosisi(Number(s.id), s.x, s.y, s.w, s.h);
            };

            kanvas.addEventListener('pointerup', selesai);
            kanvas.addEventListener('pointercancel', selesai);
        })();
    </script>
    @endscript
@endif
