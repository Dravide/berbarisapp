{{-- Baris tugas juri — bentuk kompak.

     Dipakai di dua tempat dengan sasaran berbeda: di ponsel selalu (kartu
     potret yang ditumpuk memakan tiga layar), dan di layar lebar begitu
     jurinya banyak. `$batas` null = tampilkan semuanya tanpa tombol.

     Tugas dipotong satu baris dengan atribut title berisi teks penuhnya:
     memotong tanpa menyisakan jalur ke teks aslinya sama dengan
     menghilangkan informasi. --}}
@php
    $batas = $batas ?? null;
    $sisa = $batas === null ? 0 : max(0, $dewan->count() - $batas);
@endphp

<div class="flex flex-col divide-y divide-outline-variant/30 overflow-hidden rounded-xl border border-outline-variant/40">
    @foreach($dewan as $row)
        @php $tersembunyi = $batas !== null && $loop->index >= $batas; @endphp

        {{-- x-cloak wajib di baris yang tersembunyi: tanpa itu isinya sempat
             terlihat sebelum Alpine menyala, dan halaman berkedip memanjang
             lalu memendek. --}}
        <div class="flex items-center gap-3 px-4 py-3"
             @if($tersembunyi) x-cloak x-show="semua" @endif>
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

    @if($sisa > 0)
        <button type="button"
                x-show="!semua"
                @click="semua = true"
                class="flex w-full items-center justify-center gap-1.5 bg-surface-container-low px-4 py-3 text-xs font-bold text-primary transition-colors hover:bg-surface-container">
            <i class="ti ti-chevron-down"></i>
            Lihat semua juri ({{ $dewan->count() }})
        </button>
    @endif
</div>
