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
      $terkunci    bool; editor boleh memilih, tapi tak boleh menggeser apa pun
      $terpilihId  int|null; zona yang sedang dipilih di editor
      $kisi        bool; tampilkan kisi latar
      $mode        'zona' | 'alur'; apa yang bisa diseret di editor
--}}
@php
    $zones = $zones ?? $layout->zones;
    $interaktif = $interaktif ?? false;
    $terkunci = $terkunci ?? false;
    $terpilihId = $terpilihId ?? null;
    $kisi = $kisi ?? false;
    $mode = $mode ?? 'zona';
    $rasio = max(0.2, min(6, (float) $layout->rasio));

    // Di editor alur tetap digambar walau panitia menyembunyikannya dari
    // publik; kalau tidak, tak ada lagi yang bisa disunting.
    $tampilkanAlur = $interaktif ? $layout->punya_alur : $layout->alur_tampil;
@endphp

{{-- Sengaja TANPA wire:ignore.

     wire:ignore menghentikan SEMUA pembaruan, jadi menambah atau menghapus zona
     tidak pernah muncul di kanvas. Blade tetap sumber kebenaran posisi; Alpine
     cuma memindahkan kotaknya selama seretan, lalu server mengirim posisi
     finalnya kembali lewat morph. --}}
<div
    class="denah-kanvas @if($interaktif) denah-interaktif @endif @if($interaktif && $terkunci) denah-terkunci @endif @if($mode === 'alur') denah-alur-mode @endif"
    style="--rasio: {{ number_format($rasio, 4, '.', '') }};"
    @if($interaktif)
        data-denah-editor
        data-denah-mode="{{ $mode }}"
        data-denah-terkunci="{{ $terkunci ? '1' : '0' }}"
        wire:key="kanvas-{{ $layout->id }}"
    @endif
>
    {{-- Zona & alur digambar di dalam bingkai, bukan langsung di kanvas, supaya
         ada satu tempat yang jelas-jelas berarti "papan". --}}
    <div
        class="denah-bingkai @if($kisi) denah-kisi @endif"
        style="left: 0%; top: 0%; width: 100%; height: 100%;"
    >
    @forelse($zones as $zona)
        <div
            wire:key="zona-{{ $zona->id }}"
            class="zone {{ $zona->kelas_tipe }} @if($terpilihId === $zona->id) is-terpilih @endif @if($zona->is_locked) is-terkunci @endif"
            style="{{ $zona->gaya }}"
            data-zona="{{ $zona->id }}"
            @if($interaktif) data-zona-nama="{{ $zona->name }}" @endif
            @if($interaktif && $zona->is_locked) data-zona-terkunci="1" @endif
        >
            <span class="zone-label">{{ $zona->name }}</span>
            {{-- Gembok kecil di kotaknya: kanvas penuh zona, dan gembok di
                 daftar kiri cuma menolong kalau orangnya sedang menatap daftar.
                 Yang sedang menarik kotak perlu tahu dari kotaknya sendiri.

                 Hanya di editor: pembaca denah tak perlu tahu kotak mana yang
                 dikunci panitia — itu urusan kerja, bukan keterangan lapangan. --}}
            @if($interaktif && $zona->is_locked)
                <span class="zone-gembok" title="Zona terkunci"><i class="ti ti-lock"></i></span>
            @endif
            @if($zona->punya_ukuran_sendiri)
                {{-- Ukuran arena berukuran tetap ditulis di kotaknya sendiri.
                     Dulu ukuran papan tercetak di pita tepi; pitanya dihapus,
                     jadi kotak inilah satu-satunya ukuran yang dilihat pembaca
                     denah — dan itu memang yang dicari orang di denah lomba. --}}
                <span class="zone-ukur">{{ $zona->ukuranMeter($layout) }}</span>
            @endif
            @if($zona->capacity)
                <span class="zone-sub">{{ $zona->capacity }}</span>
            @endif
            {{-- Pegangan ubah ukuran: sudut kanan-bawah, seukuran jari.
                 Di denah terkunci — atau di zona yang terkunci — ia tak digambar
                 sama sekali: pegangan yang bisa ditarik tapi tak mengubah apa
                 pun cuma janji palsu. --}}
            @if($interaktif && ! $terkunci && ! $zona->is_locked)
                <span class="zona-hnd" data-hnd="1" title="Tarik untuk mengubah ukuran"></span>
            @endif
        </div>
    @empty
        @unless($interaktif)
            <div class="denah-kosong">Denah belum berisi zona.</div>
        @endunless
    @endforelse

    {{-- Garis pandu: muncul selama seretan, saat kotak yang diseret sejajar
         dengan kotak lain atau jatuh tepat di tengah papan.

         Digambar di dalam bingkai, bukan di kanvas: panduannya menunjuk garis
         di PAPAN, dan persennya persen papan.

         Tak ikut terpotret PNG karena bawaannya memang tersembunyi — kelas
         penampilnya hanya dipasang selama seretan. --}}
    @if($interaktif && ! $terkunci)
        <span class="pandu pandu-tegak" data-pandu="tegak"></span>
        <span class="pandu pandu-datar" data-pandu="datar"></span>
    @endif

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

        /* Bingkai = papan sesungguhnya. Dulu ia menyusut selebar pita ukuran
           yang mengelilingi tepi kanvas; pitanya sudah tidak ada, jadi papan
           dan kanvas berimpit. */
        .denah-bingkai {
            position: absolute;
            box-sizing: border-box;
        }

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
        /* Angka ukuran arena duduk di barisnya sendiri di bawah namanya.
           Disamakan dengan sub-label, tapi tidak diredupkan: ukuran arena
           justru angka yang dicari orang di denah lomba. */
        .denah-kanvas .zone-ukur { font-size: .85em; font-weight: 600; }

        /* Warna per jenis: bahasa visual yang sama di editor, publik, PDF. */
        .zone.z-arena   { background: #dcfce7; border-color: #16a34a; }
        .zone.z-dp      { background: #dbeafe; border-color: #2563eb; }
        .zone.z-juri    { background: #fef9c3; border-color: #ca8a04; }
        .zone.z-tribun  { background: #f1f5f9; border-color: #94a3b8; }
        .zone.z-sekre   { background: #fae8ff; border-color: #a21caf; }
        .zone.z-jalur   { background: #ffe4e6; border-color: #e11d48; }
        .zone.z-arah    { background: transparent; border-style: dashed; border-color: #94a3b8; }
        .zone.z-danton  { background: #fef3c7; border-color: #b45309; }
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

        /* ── Garis pandu penyelaras ────────────────────────────────────────

           Muncul hanya selama seretan. Bawaannya display:none, dan kelas
           penampilnya dilepas begitu seretan selesai — jadi tak ada panduan
           yang tertinggal di kanvas, di halaman publik, atau di PDF.

           Z-index-nya di bawah alur (4) tapi di atas zona (default): panduan
           menunjuk tepi kotak, dan tepi itu justru ada di belakang kotaknya. */
        .denah-kanvas .pandu {
            position: absolute;
            display: none;
            pointer-events: none;
            z-index: 3;
            background: #e11d48;
        }
        .denah-kanvas .pandu.pandu-tampil { display: block; }
        .denah-kanvas .pandu-tegak {
            top: 0;
            width: 1px;
            height: 100%;
        }
        .denah-kanvas .pandu-datar {
            left: 0;
            height: 1px;
            width: 100%;
        }
        /* Tepi papan juga garis yang bisa diselarasi: panduannya dibedakan
           supaya "menempel tepi" tak terbaca sebagai "sejajar zona lain". */
        .denah-kanvas .pandu.pandu-papan {
            background: #2563eb;
        }

        .denah-kanvas .zone.is-terpilih {
            outline: 3px solid #2563eb;
            outline-offset: 1px;
            z-index: 5;
        }

        .denah-interaktif .zone { cursor: grab; }
        .denah-interaktif .zone:active { cursor: grabbing; }

        /* Denah terkunci tetap bisa dipilih, jadi kursor tunjuknya tetap ada;
           yang hilang cuma kursor "bisa diseret". */
        .denah-terkunci .zone,
        .denah-terkunci .zone:active,
        .denah-terkunci .alur-titik,
        .denah-terkunci .alur-titik:active { cursor: pointer; }
        .denah-terkunci .alur-ruas { cursor: default; }

        /* Zona yang terkunci sendiri: di denah terbuka pun tak bisa diseret.
           Kursor "grab" di atasnya akan menjanjikan seretan yang lalu ditolak
           server — dan penolakan yang datang belakangan terasa seperti rusak. */
        .denah-interaktif .zone.is-terkunci,
        .denah-interaktif .zone.is-terkunci:active { cursor: pointer; }

        .denah-kanvas .zone-gembok {
            position: absolute;
            right: 2px;
            top: 2px;
            font-size: 11px;
            line-height: 1;
            color: #475569;
            pointer-events: none;
            z-index: 2;
        }

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

        /* Alur mengalir: lurik putih berjalan dari titik awal ke titik akhir.

           Dash putihnya lapis kedua di atas garis merahnya, bukan garisnya
           sendiri yang diputus-putus: garis merahnya tetap utuh, jadi alur
           masih terbaca penuh kalau animasinya dimatikan — oleh
           prefers-reduced-motion, atau oleh peramban yang tak menjalankannya.

           background-position yang dianimasikan, bukan transform: luriknya
           harus berulang mulus, dan satu periode background-size persis sama
           dengan satu periode gradien. Kalau keduanya tak sama, luriknya
           tersentak tiap putaran.

           Satuannya px, bukan persen — kalau luriknya ikut memanjang bersama
           ruasnya, ruas panjang mengalir pelan dan ruas pendek berpacu, padahal
           pasukan berjalan dengan kecepatan yang sama di seluruh alur. */
        .denah-kanvas .alur-garis::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background-image: repeating-linear-gradient(
                90deg,
                rgba(255, 255, 255, .9) 0 8px,
                transparent 8px 24px
            );
            background-size: 24px 100%;
            animation: alur-mengalir .9s linear infinite;
        }
        @keyframes alur-mengalir {
            from { background-position: 0 0; }
            to   { background-position: 24px 0; }
        }
        /* Denah yang bergerak mengganggu pembaca yang memintanya diam. */
        @media (prefers-reduced-motion: reduce) {
            .denah-kanvas .alur-garis::after { animation: none; }
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

        /* Tinggi kanvas di kertas TIDAK dipatok di sini.

           Sebelumnya ada aturan cadangan `height: 150mm` untuk halaman cetak,
           dan itu justru menang atas tinggi yang dihitung controller: dua
           aturan berspesifisitas sama, dan gaya kanvas ini selalu keluar
           SESUDAH gaya halaman cetaknya. Akibatnya denah 40 × 22 m (135mm) dan
           22 × 40 m (145mm) sama-sama tercetak 150mm, meluber ke halaman kedua.

           Yang berhak menentukan tingginya cuma pdf-cetak, karena hanya ia yang
           tahu ruang sisa setelah kop dan tabel keterangan. */

        /* Garis alur di kertas ditebalkan sedikit: 4px pada 255mm terbaca
           putus-putus oleh printer kantor. */
        .denah-cetak .denah-kanvas .alur-garis { height: 5px; top: -2.5px; }
        .denah-cetak .denah-kanvas .alur-label { font-size: 11px; }

        /* Lurik alur tak perlu dilepas di sini: dompdf sudah mengabaikan
           gradiennya sendiri (terukur — dengan dan tanpa aturan ini, PDF-nya
           sama persis). Yang dicetak memang garis alurnya yang utuh. */
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
            // Yang diukur BINGKAI, bukan kanvas: bingkailah papan sesungguhnya,
            // dan persen zona dihitung dari papan. Keduanya kini berimpit,
            // tapi mengukur bingkai tetap yang benar kalau kelak ada yang
            // memakan tepi kanvas lagi.
            const batasKanvas = () => (kanvas.querySelector('.denah-bingkai') ?? kanvas).getBoundingClientRect();
            const modeAlur = () => kanvas.dataset.denahMode === 'alur';

            // Dibaca tiap kali, bukan disimpan saat init — sama alasannya
            // dengan mode: Livewire memorph kanvas di tempat, jadi kunci yang
            // baru dinyalakan takkan terlihat oleh nilai yang ditangkap sekali.
            const terkunci = () => kanvas.dataset.denahTerkunci === '1';

            let seret = null;

            const persen = (px, total) => Math.round((px / total) * 1000) / 10;

            // ── Garis pandu penyelaras ────────────────────────────────────
            //
            // Saat kotak diseret, tepinya dibandingkan dengan tepi tiap kotak
            // lain dan dengan tepi & tengah papan. Yang paling dekat dalam
            // ambang di bawah ini "menangkap" seretannya, jadi kotak jatuh
            // benar-benar sejajar — bukan hampir sejajar.
            //
            // Tanpa penangkapan, cuma ada garisnya: mata tetap yang harus
            // menepatkan, dan denah hasilnya menyimpan selisih sepersekian
            // persen yang tak terlihat di layar tapi berlipat jadi sentimeter
            // begitu dicetak.
            const AMBANG = 0.8;

            /**
             * Kandidat garis untuk satu sumbu, dari bentuk kotak lain.
             *
             * Tepi & tengah papan ikut jadi kandidat dan ditandai `papan`,
             * supaya panduannya bisa diberi warna lain: "menempel tepi papan"
             * dan "sejajar zona lain" dua hal berbeda bagi yang membaca denah.
             */
            const kandidat = (kotakLain, sumbu) => {
                const daftar = [
                    { nilai: 0, papan: true },
                    { nilai: 50, papan: true },
                    { nilai: 100, papan: true },
                ];

                for (const k of kotakLain) {
                    daftar.push({ nilai: k[sumbu.mulai], papan: false });
                    daftar.push({ nilai: k[sumbu.akhir], papan: false });
                    daftar.push({ nilai: (k[sumbu.mulai] + k[sumbu.akhir]) / 2, papan: false });
                }

                return daftar;
            };

            /**
             * Cari garis yang paling dekat dengan salah satu dari tiga jangkar
             * kotak — tepi awal, tepi akhir, atau tengahnya.
             *
             * Ketiganya sekaligus, bukan cuma tepi: kotak selebar 60% papan
             * yang ditaruh "di tengah" hampir tak pernah punya tepi yang jatuh
             * di angka bulat; yang orang maksud memang tengahnya.
             *
             * Mengembalikan posisi baru + garis yang menangkapnya, atau null.
             */
            const selaras = (pos, ukuran, daftar) => {
                let terbaik = null;

                for (const jangkar of [pos, pos + ukuran, pos + ukuran / 2]) {
                    for (const k of daftar) {
                        const jarak = Math.abs(jangkar - k.nilai);

                        if (jarak <= AMBANG && (!terbaik || jarak < terbaik.jarak)) {
                            terbaik = {
                                jarak,
                                posBaru: pos + (k.nilai - jangkar),
                                garis: k,
                            };
                        }
                    }
                }

                return terbaik;
            };

            const tampilkanPandu = (tegak, datar) => {
                const atur = (el, hasil, sumbu) => {
                    // Dicari tiap kali, bukan disimpan saat init: elemen
                    // panduannya tak ada di DOM selama denah terkunci, dan
                    // Livewire memorph kanvas di tempat — referensi yang
                    // ditangkap saat init jadi elemen yatim begitu kuncinya
                    // dibuka, dan garisnya berhenti muncul tanpa error apa pun.
                    if (! el) return;

                    el.classList.toggle('pandu-tampil', Boolean(hasil));
                    el.classList.toggle('pandu-papan', Boolean(hasil?.garis.papan));

                    if (hasil) el.style[sumbu] = hasil.garis.nilai + '%';
                };

                atur(kanvas.querySelector('[data-pandu="tegak"]'), tegak, 'left');
                atur(kanvas.querySelector('[data-pandu="datar"]'), datar, 'top');
            };

            const sembunyikanPandu = () => tampilkanPandu(null, null);

            kanvas.addEventListener('pointerdown', (e) => {
                const batas = batasKanvas();

                // Denah terkunci: memilih tetap boleh, menggeser tidak.
                //
                // Kuncinya sengaja tak mematikan seluruh penangan: denah yang
                // tak bisa dipilih sama saja dengan denah yang tak bisa dibaca,
                // dan panitia masih perlu mengintip sifat zonanya. Yang dibuang
                // cuma seretannya — tak ada pointer capture, tak ada `seret`,
                // jadi pointermove di bawah memang tak punya apa pun untuk
                // digerakkan.
                if (terkunci()) {
                    const sasaran = e.target.closest('[data-zona], [data-alur-titik]');

                    if (! sasaran) return;

                    e.preventDefault();

                    if (sasaran.dataset.alurTitik !== undefined) {
                        $wire.pilihTitik(Number(sasaran.dataset.alurTitik));
                    } else {
                        $wire.pilihZona(Number(sasaran.dataset.zona));
                    }

                    return;
                }

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

                // Zona terkunci sendiri: sama seperti denah terkunci, memilih
                // tetap boleh — yang dibuang cuma seretannya. Ditolak di sini
                // juga, bukan cuma di server, supaya kotaknya tak sempat
                // bergerak di layar lalu melompat balik saat Livewire menolak.
                if (zona.dataset.zonaTerkunci !== undefined) {
                    e.preventDefault();
                    sembunyikanPandu();
                    $wire.pilihZona(Number(zona.dataset.zona));

                    return;
                }

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
                    // Kotak lain, dalam persen papan — bahan garis pandu.
                    // Diukur saat seretan MULAI, bukan tiap gerakan: kotak lain
                    // tak bergerak selama seretan ini, jadi mengukurnya lagi
                    // tiap frame cuma memaksa layout dihitung ulang terus.
                    lain: [...kanvas.querySelectorAll('[data-zona]')]
                        .filter((el) => el !== zona)
                        .map((el) => {
                            const k = el.getBoundingClientRect();

                            return {
                                kiri: persen(k.left - batas.left, batas.width),
                                atas: persen(k.top - batas.top, batas.height),
                                kanan: persen(k.right - batas.left, batas.width),
                                bawah: persen(k.bottom - batas.top, batas.height),
                            };
                        }),
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

                    // Panduan cuma saat MENGGESER. Mengubah ukuran menyentuh
                    // satu tepi saja, dan menangkap dua tepi sekaligus pada
                    // kotak yang sedang membesar membuat kotaknya melompat.
                    const tegak = selaras(x, w, kandidat(seret.lain, { mulai: 'kiri', akhir: 'kanan' }));
                    const datar = selaras(y, h, kandidat(seret.lain, { mulai: 'atas', akhir: 'bawah' }));

                    // Jepitan tepi papan menang atas panduan: panduan yang
                    // menyuruh keluar papan dibuang, bukan jepitannya. Garis
                    // yang digambar di posisi yang ternyata tak dipakai itu
                    // janji palsu — panitia menaruh kotaknya di situ karena
                    // percaya garisnya.
                    const xTerjepit = tegak ? Math.max(0, Math.min(100 - w, tegak.posBaru)) : x;
                    const yTerjepit = datar ? Math.max(0, Math.min(100 - h, datar.posBaru)) : y;

                    x = xTerjepit;
                    y = yTerjepit;

                    tampilkanPandu(
                        tegak && xTerjepit === tegak.posBaru ? tegak : null,
                        datar && yTerjepit === datar.posBaru ? datar : null,
                    );
                } else {
                    sembunyikanPandu();

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
                sembunyikanPandu();

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
