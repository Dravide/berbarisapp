{{-- Baris tugas juri — bentuk kompak.

     Bentuknya ditentukan LEBAR layar, bukan jumlah juri: pemanggil memasang
     partial ini di bawah `lg`, dan kartu potret di atasnya. Karena itu daftar
     ini selalu tampil penuh — dulu ia menerima `$batas` dan menyembunyikan
     sisanya di balik tombol "Lihat semua juri", sisa dari ambang `count() > 6`
     yang sekarang sudah dibuang.

     Menyembunyikan sebagian daftar memang masuk akal untuk kartu potret yang
     tinggi, tapi bukan untuk bentuk ini: sepuluh baris kompak pun masih lebih
     pendek daripada empat kartu potret, dan di ponsel justru bentuk inilah
     yang menggantikan potret — menyembunyikan juri di situ berarti pembaca
     ponsel tak pernah bisa melihat seluruh dewan tanpa menekan tombol.

     Tugas dipotong satu baris dengan atribut title berisi teks penuhnya:
     memotong tanpa menyisakan jalur ke teks aslinya sama dengan
     menghilangkan informasi. --}}

<div class="flex flex-col divide-y divide-outline-variant/30 overflow-hidden rounded-xl border border-outline-variant/40">
    @foreach($dewan as $row)
        <div class="flex items-center gap-3 px-4 py-3">
            @if($row['judge']->photo)
                <img src="{{ asset('storage/' . $row['judge']->photo) }}"
                     alt="{{ $row['judge']->name }}"
                     class="h-10 w-10 shrink-0 rounded-full border border-outline-variant/30 object-cover">
            @else
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-outline-variant/40 bg-surface-container-low text-xs font-bold {{ $row['aksen'] }}">
                    {{ $row['inisial'] }}
                </div>
            @endif

            <div class="min-w-0 flex-1">
                <span class="block text-sm font-bold leading-tight text-deep-slate">{{ $row['judge']->name }}</span>
                <span class="block truncate text-xs leading-snug {{ $row['tugas'] ? 'text-on-surface-variant' : 'italic text-on-surface-variant/70' }}"
                      title="{{ $row['tugas'] ?: 'Penilai' }}">
                    {{ $row['tugas'] ?: 'Penilai' }}
                </span>
            </div>

            @if($row['induk'])
                <span class="chip shrink-0 py-0.5 px-2 !text-[10px]">{{ $row['induk'] }}</span>
            @endif
        </div>
    @endforeach
</div>
