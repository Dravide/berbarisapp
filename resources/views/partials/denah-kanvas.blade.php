{{--
    Kanvas denah, satu-satunya tempat zona digambar.

    Dipakai bersama oleh editor panitia, halaman publik, dan PDF; kalau tiap
    layar menggambar sendiri, ketiganya pelan-pelan berbeda.

    Posisi zona tersimpan sebagai persen papan, jadi satu markup ini tampil
    proporsional di lebar apa pun. `--rasio` yang menyetel tinggi kanvas.

    Parameter:
      $layout      CompetitionLayout
      $zones       koleksi LayoutZone (opsional; default $layout->zones)
      $interaktif  bool; editor menyeret zona, publik & PDF tidak
      $terpilihId  int|null; zona yang sedang dipilih di editor
      $kisi        bool; tampilkan kisi latar
--}}
@php
    $zones = $zones ?? $layout->zones;
    $interaktif = $interaktif ?? false;
    $terpilihId = $terpilihId ?? null;
    $kisi = $kisi ?? false;
    $rasio = max(0.2, min(6, (float) $layout->rasio));
@endphp

{{-- Sengaja TANPA wire:ignore.

     wire:ignore menghentikan SEMUA pembaruan, jadi menambah atau menghapus zona
     tidak pernah muncul di kanvas. Blade tetap sumber kebenaran posisi; Alpine
     cuma memindahkan kotaknya selama seretan, lalu server mengirim posisi
     finalnya kembali lewat morph. --}}
<div
    class="denah-kanvas @if($kisi) denah-kisi @endif @if($interaktif) denah-interaktif @endif"
    style="--rasio: {{ number_format($rasio, 4, '.', '') }};"
    @if($interaktif)
        data-denah-editor
        wire:key="kanvas-{{ $layout->id }}"
    @endif
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

        /* dompdf hanya paham CSS sederhana; tak ada aspect-ratio. Tinggi
           kanvas dipatok supaya denah di PDF tidak runtuh jadi garis tipis. */
        .denah-cetak .denah-kanvas { aspect-ratio: auto; height: 150mm; }
    </style>
@endonce

@if($interaktif)
    @script
    <script>
        // Seret zona di kanvas.
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

            const batas = kanvas.getBoundingClientRect();
            let seret = null;

            const persen = (px, total) => Math.round((px / total) * 1000) / 10;

            kanvas.addEventListener('pointerdown', (e) => {
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

                const dx = persen(e.clientX - seret.mulaiX, batas.width);
                const dy = persen(e.clientY - seret.mulaiY, batas.height);

                if (Math.abs(dx) > 0.4 || Math.abs(dy) > 0.4) seret.bergerak = true;

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
