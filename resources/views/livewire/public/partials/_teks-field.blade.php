@php
    /**
     * Satu kotak isian field teks builder (semua tipe selain berkas dan grup).
     *
     * Dipakai kartu section inline maupun modal section — markup-nya memang
     * harus sama, karena keduanya mengikat properti Livewire yang sama; yang
     * berbeda hanya lebar kolomnya. Tipe `wilayah` diserahkan ke partialnya
     * sendiri karena isinya tiga dropdown bertingkat.
     *
     * Dianggap ada dari pemanggil: $field dan $kunci (nama properti Livewire),
     * serta opsional $nilai untuk isi awal textarea.
     *
     * @var \App\Models\RegistrationField $field
     * @var string $kunci
     * @var string|null $nilai
     */
    $nilai = $nilai ?? '';
@endphp
<div wire:key="text-{{ $field->id }}" class="{{ $field->type === 'textarea' ? 'md:col-span-2' : '' }}">
    <label class="text-sm font-bold text-deep-slate block mb-1.5">
        {{ $field->label }}
        @if($field->is_required) <span class="text-red-500">*</span> @endif
    </label>

    @if($field->type === 'textarea')
        <textarea wire:model="{{ $kunci }}" rows="3" maxlength="{{ $field->maxLength() }}" class="field-input w-full" placeholder="{{ $field->help_text ?: $field->label }}">{{ $nilai }}</textarea>
    @elseif($field->type === 'select')
        <select wire:model="{{ $kunci }}" class="field-input w-full">
            <option value="">— Pilih {{ $field->label }} —</option>
            @foreach(($field->options ?? []) as $opsi)
                <option value="{{ $opsi['value'] }}">{{ $opsi['label'] }}</option>
            @endforeach
        </select>
    @elseif($field->type === 'wilayah')
        @include('livewire.public.partials._wilayah-field', [
            'field' => $field,
            'kelas' => 'field-input w-full',
        ])
    @else
        <input type="{{ $field->type === 'number' ? 'number' : ($field->type === 'date' ? 'date' : 'text') }}"
            @if(! in_array($field->type, ['number', 'date'], true)) maxlength="{{ $field->maxLength() }}" @endif
            wire:model="{{ $kunci }}" class="field-input w-full" placeholder="{{ $field->help_text ?: $field->label }}">
    @endif

    @error($kunci) <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
    @if($field->help_text && $field->type !== 'textarea')
        <span class="text-[10px] text-on-surface-variant font-medium mt-1 block leading-normal">{{ $field->help_text }}</span>
    @endif
</div>
