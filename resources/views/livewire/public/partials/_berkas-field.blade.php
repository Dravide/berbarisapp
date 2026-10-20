@php
    /**
     * Kotak unggah satu field berkas builder (tipe `file` / `image`).
     *
     * Dipakai kartu section inline maupun modal section — markup-nya identik,
     * dan satu-satunya hal yang membedakan hanyalah lebar kolomnya. Karena
     * berkas diunggah lewat endpoint HTTP (bukan properti Livewire: field
     * builder jumlahnya dinamis sedangkan properti upload harus ada saat
     * compile), nilainya langsung tersimpan begitu berkas selesai diunggah.
     *
     * Dianggap ada dari pemanggil: $berkas (satu entri `berkasFields()`),
     * komponen Livewire pemilik $token dan $activeRegId, serta opsional
     * $kelas dan $nonce (penanda buka-modal).
     *
     * @var array{field: \App\Models\RegistrationField, path: ?string, url: ?string} $berkas
     * @var string|null $kelas
     * @var int|null $nonce
     */
    $field = $berkas['field'];
    $nonce = $nonce ?? null;
    $fieldId = 'pond-' . $activeRegId . '-field-' . $field->id . ($nonce ? '-n' . $nonce : '');
    $uploadUrl = route('magic.link.field.upload', ['token' => $token, 'fieldId' => $field->id]);
    $deleteUrl = route('magic.link.field.destroy', ['token' => $token, 'fieldId' => $field->id]);

    // Dua-duanya HARUS MIME, dan HARUS sama.
    //
    // FilePondPluginFileValidateType memetakan atribut `accept` ke opsi
    // `acceptedFileTypes` (SET_ATTRIBUTE_TO_OPTION_MAP), dan core merge
    // atribut SETELAH opsi JS — jadi `accept` menimpa array yang kita tulis di
    // FilePond.create(). Memberi ekstensi di `accept` membuat daftar yang
    // benar-benar dipakai berisi ".pdf", yang tidak pernah cocok dengan
    // file.type ("application/pdf") — plugin hanya mengerti MIME dan wildcard,
    // ekstensi "not enabled yet". Efeknya berkas yang benar ditolak "File is of
    // invalid type" tanpa pernah sampai ke server.
    //
    // Wildcard image/* dulu lolos karena kedua sisi kebetulan sama; daftar
    // eksplisit tidak.
    $tipes = $field->type === 'image'
        ? 'image/*'
        : 'application/pdf,image/jpeg,image/png';
    $mimeDiterima = $field->type === 'image'
        ? '[\'image/*\']'
        : '[\'application/pdf\', \'image/jpeg\', \'image/png\']';
    $batas = number_format($field->max_kb ?: 5120, 0, ',', '.');
@endphp

{{-- Unggahan lewat endpoint HTTP, bukan properti Livewire: field builder
     jumlahnya dinamis sedangkan properti upload Livewire harus ada saat
     compile. --}}
<div wire:key="{{ $fieldId }}" class="{{ $kelas ?? 'md:col-span-2' }}">
    <label class="text-sm font-bold text-deep-slate block mb-1.5">
        {{ $field->label }}
        @if($field->is_required) <span class="text-red-500">*</span> @endif
        <span class="font-normal text-on-surface-variant">(maks. {{ $batas }} KB)</span>
    </label>
    <div wire:ignore x-data="{ pond: null }" x-init="
        pond = FilePond.create($refs.input, {
            credits: false,
            acceptedFileTypes: {!! $mimeDiterima !!},
            labelFileTypeNotAllowed: 'Tipe berkas tidak diizinkan. Gunakan {{ $field->type === 'image' ? 'gambar (JPG/PNG)' : 'PDF, JPG, atau PNG' }}.',
            labelIdle: 'Tarik & Letakkan berkas atau <span class=\'filepond--label-action\'>Pilih File</span>',
            server: {
                process: (fieldName, file, metadata, load, error, progress, abort) => {
                    const data = new FormData();
                    data.append('file', file);
                    data.append('_token', '{{ csrf_token() }}');

                    const request = new XMLHttpRequest();
                    request.open('POST', '{{ $uploadUrl }}');
                    request.upload.onprogress = (e) => progress(e.lengthComputable, e.loaded, e.total);
                    request.onload = () => {
                        if (request.status >= 200 && request.status < 300) {
                            load(request.responseText);
                        } else {
                            let pesan = 'Berkas gagal diunggah.';
                            try { pesan = JSON.parse(request.responseText).message || pesan; } catch (e) {}
                            error(pesan);
                        }
                    };
                    request.onerror = () => error('Berkas gagal diunggah. Periksa koneksi Anda.');
                    request.send(data);

                    return {
                        abort: () => {
                            request.abort();
                            abort();
                        },
                    };
                },
                revert: (uniqueFileId, load, error) => {
                    fetch('{{ $deleteUrl }}', {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                    }).then((res) => res.ok ? load() : error('Berkas gagal dihapus.'));
                },
            },
        });
    ">
        <input type="file" x-ref="input" accept="{{ $tipes }}">
    </div>
    @error('fields.' . $field->id) <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
    @if($berkas['path'])
        <span class="text-xs font-bold text-emerald-600 mt-2 inline-flex items-center gap-1">
            <i class="ti ti-circle-check-filled"></i> Berkas sudah diunggah
            <a href="{{ $berkas['url'] }}" target="_blank" class="text-primary underline ml-1">Lihat</a>
        </span>
    @elseif($field->is_required)
        <span class="text-[10px] text-on-surface-variant font-medium mt-1 block leading-normal">Wajib diunggah sebelum data difinalisasi.</span>
    @endif
</div>
