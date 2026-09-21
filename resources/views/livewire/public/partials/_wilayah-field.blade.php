@php
    /**
     * Dropdown wilayah berjenjang untuk field bertipe `wilayah`.
     *
     * Dipakai form pendaftaran publik maupun portal magic link — markup-nya
     * identik, jadi ia hidup di sini supaya dua view tidak menyalin logika
     * "turun ke input teks kalau daftar tidak bisa dimuat".
     *
     * Dianggap ada dari pemanggil: $field, $kelas (kelas input), dan komponen
     * Livewire yang memakai trait MengelolaWilayah.
     *
     * @var \App\Models\RegistrationField $field
     * @var string $kelas
     */
    $tingkatWilayah = ['provinsi' => 'Provinsi', 'kabupaten' => 'Kabupaten / Kota', 'kecamatan' => 'Kecamatan'];
    $kedalaman = $this->kedalamanWilayah($field);
    $namaField = 'fieldValues.' . $field->field_key;

    // Daftar provinsi juga jadi penanda API hidup/tidak: kalau yang paling atas
    // saja kosong, tidak ada gunanya menampilkan tiga select kosong.
    $adaApi = $this->opsiWilayah($field->field_key, 'provinsi') !== [];
@endphp

@if(! $adaApi)
    {{--
        API wilayah sedang tidak bisa dihubungi. Pendaftaran TIDAK boleh mustahil
        karena layanan pihak ketiga tumbang: jatuh ke teks biasa, dan nilainya
        tetap sah karena kolomnya teks bebas.
    --}}
    <input type="text" id="field_{{ $field->field_key }}" wire:model="{{ $namaField }}"
        placeholder="{{ $field->help_text ?: $field->label }}" class="{{ $kelas }}">
    <span class="text-[10px] text-amber-600 font-medium mt-1 block leading-normal">
        Daftar wilayah sedang tidak bisa dimuat. Isi manual, atau coba muat ulang halaman.
    </span>
@else
    <div class="grid gap-2">
        @foreach($tingkatWilayah as $tingkat => $labelTingkat)
            {{-- Tingkat di bawah kedalaman yang diminta tidak dirender sama
                 sekali; tingkat yang belum ada isinya tetap dirender (kosong
                 dan terkunci) supaya jenjangnya terlihat sejak awal. --}}
            @if($loop->index >= $kedalaman)
                @break
            @endif

            @php
                $opsi = $this->opsiWilayah($field->field_key, $tingkat);
                $induk = $tingkat === 'provinsi' ? null : ($tingkat === 'kabupaten' ? 'provinsi' : 'kabupaten');
            @endphp

            <select id="field_{{ $field->field_key }}_{{ $tingkat }}"
                wire:model.live="wilayah.{{ $field->field_key }}.{{ $tingkat }}"
                class="{{ $kelas }}"
                {{-- Terkunci sampai tingkat induknya dipilih, supaya tidak ada
                     kabupaten dari provinsi yang belum ditentukan. --}}
                @disabled($induk !== null && ($this->wilayah[$field->field_key][$induk] ?? '') === '')>
                <option value="">— Pilih {{ $labelTingkat }} —</option>
                @foreach($opsi as $item)
                    <option value="{{ $item['kode'] }}">{{ $item['nama'] }}</option>
                @endforeach
            </select>
        @endforeach
    </div>
@endif
