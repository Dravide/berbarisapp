{{--
    Satu denah di halaman publik.

    Memakai partial kanvas yang sama dengan editor, bukan renderer kedua, supaya
    keduanya tak bisa menyimpang. Kisi & penanda pilihan editor tidak dipakai.
--}}
<div class="surface-card p-5 denah-tampil" wire:key="denah-{{ $denah->id }}">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div class="min-w-0">
            <h2 class="font-display text-base font-bold text-on-surface mb-1">{{ $denah->name }}</h2>
            <p class="text-xs text-on-surface-variant mb-0">
                @if($denah->competitionCategory)
                    {{ $denah->competitionCategory->full_name }}
                @else
                    Lintas tingkat lomba
                @endif
                @if($denah->venue) &middot; {{ $denah->venue->name }} @endif
                &middot;
                {{ rtrim(rtrim(number_format((float) $denah->ground_width, 1, ',', '.'), '0'), ',') }}
                &times;
                {{ rtrim(rtrim(number_format((float) $denah->ground_height, 1, ',', '.'), '0'), ',') }} m
            </p>
        </div>

        @php($daftarDp = $denah->zones->where('type', \App\Models\LayoutZone::TYPE_DP)->sortBy('dp_number'))
        @if($daftarDp->isNotEmpty())
            <div class="d-flex flex-wrap gap-1">
                @foreach($daftarDp as $dp)
                    <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 text-primary px-2.5 py-1 text-[10px] font-bold">
                        {{ $dp->name }}@if($dp->capacity) &middot; {{ $dp->capacity }}@endif
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    @if($denah->zones->isEmpty())
        {{-- Terjadi kalau panitia menerbitkan denah lalu menghapus semua
             zonanya. Lebih jujur mengatakan kosong daripada menampilkan
             kanvas hampa. --}}
        <p class="text-xs text-on-surface-variant mb-0">Denah ini belum berisi zona.</p>
    @else
        @include('partials.denah-kanvas', [
            'layout' => $denah,
            'zones' => $denah->zones,
            'interaktif' => false,
            'kisi' => false,
        ])
    @endif

    {{-- Tabel keterangan: yang dibaca panitia saat mencari "DP saya yang mana"
         justru teksnya, bukan gambarnya. --}}
    @if($denah->zones->isNotEmpty())
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-left text-on-surface-variant border-b border-outline-variant/40">
                        <th class="py-1.5 pr-3 font-bold">Zona</th>
                        <th class="py-1.5 pr-3 font-bold">Jenis</th>
                        <th class="py-1.5 pr-3 font-bold">Kapasitas</th>
                        <th class="py-1.5 font-bold">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($denah->zones as $z)
                        <tr class="border-b border-outline-variant/20 @if($z->type === \App\Models\LayoutZone::TYPE_DP) bg-primary/[0.03] @endif">
                            <td class="py-1.5 pr-3 font-semibold text-on-surface">{{ $z->name }}</td>
                            <td class="py-1.5 pr-3 text-on-surface-variant">{{ $z->label_tipe }}</td>
                            <td class="py-1.5 pr-3 text-on-surface-variant">{{ $z->capacity ?: '-' }}</td>
                            <td class="py-1.5 text-on-surface-variant">{{ $z->note ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
