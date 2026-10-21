{{-- Kartu potret juri — bentuk lega, untuk layar `lg` ke atas.

     Hanya juri BERFOTO yang memakai <img>. Juri tanpa foto tidak dibuatkan
     placeholder gambar, melainkan monogram dua inisial di atas bidang yang
     geometrinya sama persis: kalau bidangnya mengecil saat fotonya tak ada,
     dewan tanpa foto kembali terbaca seperti daftar gagal render — persis
     masalah yang bentuk ini selesaikan.

     `auto-fit` (bukan repeat(3,1fr)) supaya lima juri mengisi barisnya
     sendiri; repeat(3,1fr) menyisakan satu sel kosong yang terbaca seperti
     data hilang.

     Lebar selnya dibatasi 200 px dan barisnya ditengahkan, bukan dibiarkan
     melar memenuhi kartu. Dengan `1fr` seorang juri tunggal mendapat bidang
     persegi selebar kartu — monogram sebesar poster untuk satu orang — dan
     dua juri membentuk dua kotak raksasa. Pada 200 px, dewan berapa pun
     besarnya digambar pada skala yang sama. --}}
<div class="grid justify-center gap-4 [grid-template-columns:repeat(auto-fit,minmax(140px,200px))]">
    @foreach($dewan as $row)
        <div class="flex min-w-0 flex-col overflow-hidden rounded-xl border border-outline-variant/40 bg-surface-container-lowest">
            {{-- container-type di div bidang, bukan di span: `cqw` menilik
                 container terdekat dan sebuah elemen tak bisa menanyai
                 dirinya sendiri. Pola yang sama dipakai .feat-mini. --}}
            <div class="relative flex aspect-square items-center justify-center overflow-hidden border-b border-outline-variant/30 bg-surface-container-low [container-type:inline-size]">
                @if($row['judge']->photo)
                    <img src="{{ asset('storage/' . $row['judge']->photo) }}"
                         alt="{{ $row['judge']->name }}"
                         loading="lazy"
                         class="h-full w-full object-cover">
                @elseif($row['inisial'])
                    <span class="font-display text-[34cqw] font-bold leading-none {{ $row['aksen'] }}"
                          aria-hidden="true">{{ $row['inisial'] }}</span>
                @else
                    <i class="ti ti-user text-3xl text-on-surface-variant"></i>
                @endif
            </div>

            <div class="flex min-w-0 flex-col gap-1 px-3 py-2.5">
                <span class="text-sm font-bold leading-tight text-deep-slate [overflow-wrap:anywhere]">{{ $row['judge']->name }}</span>
                {{-- Tugas juri bisa menyebut beberapa tingkat; dua baris cukup
                     untuk membacanya, dan `title` menyimpan sisanya. Tanpa
                     batas ini satu juri dengan banyak penugasan melipat kartu
                     potretnya jadi tinggi beberapa kali kartu tetangganya. --}}
                <span class="line-clamp-2 text-[11px] leading-snug [overflow-wrap:anywhere] {{ $row['tugas'] ? 'text-on-surface-variant' : 'italic text-on-surface-variant/70' }}"
                      title="{{ $row['tugas'] ?: 'Penilai' }}">
                    {{ $row['tugas'] ?: 'Penilai' }}
                </span>
            </div>
        </div>
    @endforeach
</div>
