<div class="min-h-screen bg-surface">

    {{-- ========== BANNER (JIKA ADA DARI PENYELENGGARA) ========== --}}
    @if($eventner->header_banner)
        <div class="container-landing pt-6">
            <div class="relative w-full aspect-[21/9] md:aspect-[3/1] rounded-2xl overflow-hidden shadow-xs border border-outline-variant/30 bg-black/5">
                <img src="{{ asset('storage/' . $eventner->header_banner) }}" alt="Banner {{ $eventner->nama_event }}" class="w-full h-full object-cover">
            </div>
        </div>
    @endif

    {{-- ========== HEADER INFO BLOCK ========== --}}
    <div class="container-landing pt-6">
        <div class="surface-card p-5 md:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4 min-w-0">
                @if($eventner->logo_event)
                    <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="h-14 w-14 rounded-xl object-cover border border-outline-variant/40 shrink-0 shadow-xs" alt="{{ $eventner->nama_event }}">
                @else
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-primary/10 text-primary border border-primary/20 shrink-0">
                        <i class="ti ti-flag-filled text-2xl"></i>
                    </div>
                @endif
                <div class="min-w-0">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-primary block mb-0.5">
                        Pendaftaran Kontingen
                    </span>
                    <h1 class="font-display text-xl sm:text-2xl font-bold text-deep-slate leading-tight m-0 truncate">
                        {{ $eventner->nama_event }}
                    </h1>
                    <div class="flex flex-wrap items-center gap-3 mt-1 text-xs text-on-surface-variant font-medium">
                        @if($eventner->tanggal_mulai)
                            <span class="inline-flex items-center gap-1">
                                <i class="ti ti-calendar text-primary"></i>
                                {{ \Carbon\Carbon::parse($eventner->tanggal_mulai)->translatedFormat('d M Y') }}
                            </span>
                        @endif
                        @if($eventner->lokasi)
                            <span class="inline-flex items-center gap-1">
                                <i class="ti ti-map-pin text-primary"></i>
                                {{ $eventner->lokasi }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="shrink-0">
                <a href="{{ event_url($eventner, 'detail') }}" class="btn-ghost py-2 px-3.5 text-xs font-semibold leading-normal inline-flex items-center gap-1.5 text-decoration-none">
                    <i class="ti ti-arrow-left"></i> Informasi Event
                </a>
            </div>
        </div>
    </div>

    {{-- ========== FLASH MESSAGES ========== --}}
    @if(session('error'))
        <div class="container-landing pt-4">
            <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-600 text-xs font-semibold flex items-center gap-2">
                <i class="ti ti-alert-circle text-base"></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    {{-- ========== PROGRESS STEPPER ========== --}}
    @if(($eventner->registration_status ?? 'open') != 'closed')
        <div class="container-landing pt-5 pb-2">
            <div class="max-w-3xl mx-auto grid grid-cols-3 gap-3">
                @php
                    $steps = [
                        1 => ['label' => '1. Kategori Lomba', 'desc' => 'Pilih tingkat & cabang'],
                        2 => ['label' => '2. Data Kontingen', 'desc' => 'Identitas pangkalan'],
                        3 => ['label' => '3. Konfirmasi', 'desc' => 'Ringkasan & booking'],
                    ];
                @endphp
                @foreach($steps as $idx => $s)
                    <div class="flex flex-col gap-1.5">
                        <div class="h-1 rounded-full {{ $step >= $idx ? 'bg-primary' : 'bg-outline-variant/30' }} transition-colors"></div>
                        <span class="text-xs font-bold {{ $step >= $idx ? 'text-deep-slate' : 'text-on-surface-variant/60' }} truncate text-center sm:text-left">
                            {{ $s['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ========== MAIN CONTENT FORM ========== --}}
    <div class="container-landing py-6">
        <div class="max-w-3xl mx-auto">

            {{-- 1. CLOSED REGISTRATION STATE --}}
            @if(($eventner->registration_status ?? 'open') == 'closed')
                <div class="surface-card p-8 md:p-10 text-center flex flex-col items-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-500/10 text-red-500 border border-red-500/20 mb-4">
                        <i class="ti ti-lock text-2xl"></i>
                    </div>
                    <h2 class="font-display text-lg font-bold text-deep-slate mb-2">Pendaftaran Ditutup</h2>
                    <p class="text-xs text-on-surface-variant leading-relaxed max-w-md mb-6 font-medium">
                        Pendaftaran slot kontingen untuk event <strong>{{ $eventner->nama_event }}</strong> saat ini telah ditutup oleh panitia penyelenggara.
                    </p>
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <a href="{{ event_url($eventner, 'detail') }}" class="btn-secondary py-2 px-4 text-xs font-bold text-decoration-none">
                            Kembali Ke Event
                        </a>
                        <a href="{{ event_url($eventner, 'participant') }}" class="btn-ghost py-2 px-4 text-xs font-bold text-decoration-none">
                            Lihat Daftar Peserta
                        </a>
                    </div>

                    @if($eventner->link_whatsapp)
                        <div class="mt-6 pt-5 border-t border-outline-variant/30 w-full max-w-sm">
                            <span class="text-xs text-on-surface-variant block mb-1.5">Ada pertanyaan terkait pendaftaran?</span>
                            @php $waNumber = preg_replace('/[^0-9]/', '', $eventner->link_whatsapp); @endphp
                            <a href="https://wa.me/{{ $waNumber }}" target="_blank" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 inline-flex items-center gap-1 text-decoration-none">
                                <i class="ti ti-brand-whatsapp text-sm"></i> Hubungi Panitia (WhatsApp)
                            </a>
                        </div>
                    @endif
                </div>
            @else

                {{-- STEP 1: Pilih Kategori --}}
                @if($step === 1)
                    <div class="surface-card overflow-hidden">
                        <div class="bg-surface-container px-5 py-4 border-b border-outline-variant/40 flex items-center justify-between">
                            <div>
                                <h3 class="font-display text-sm font-bold text-deep-slate inline-flex items-center gap-2 m-0">
                                    <i class="ti ti-list text-primary"></i> Pilih Kategori Lomba
                                </h3>
                                <p class="text-xs text-on-surface-variant font-medium m-0 mt-0.5">
                                    Pilih tingkat terlebih dahulu, lalu centang lomba yang ingin diikuti.
                                </p>
                            </div>
                        </div>

                        <div class="p-5 md:p-6">
                            @error('selectedCategories')
                                <div class="p-3.5 rounded-xl bg-red-500/10 border border-red-500/20 text-red-600 text-xs font-semibold mb-4 leading-normal">
                                    {{ $message }}
                                </div>
                            @enderror

                            @php
                                $lockedTingkat = $this->lockedTingkat();
                                $grouped = $categories->groupBy('name');
                            @endphp

                            <div class="space-y-6">
                                @foreach($grouped as $tingkatName => $cats)
                                    @php
                                        $isLocked = $lockedTingkat !== null && $tingkatName !== $lockedTingkat;
                                    @endphp
                                    <div class="{{ $isLocked ? 'opacity-40 pointer-events-none' : '' }}">
                                        <div class="flex items-center gap-2 mb-3">
                                            <span class="inline-flex items-center justify-center h-6 w-6 rounded-md bg-primary/10 text-primary text-xs font-bold">
                                                <i class="ti ti-medal text-xs"></i>
                                            </span>
                                            <h4 class="font-display text-xs font-bold text-deep-slate uppercase tracking-wider m-0">
                                                Tingkat {{ $tingkatName }}
                                            </h4>
                                            @if($isLocked)
                                                <span class="text-[10px] font-semibold text-on-surface-variant bg-surface-container px-2 py-0.5 rounded ml-1">(terkunci)</span>
                                            @endif
                                        </div>

                                        <div class="space-y-2.5">
                                            @foreach($cats as $cat)
                                                @php
                                                    $isFull = $cat->kuota && $cat->registrations_count >= $cat->kuota;
                                                    $maxPerSchool = $cat->max_registrations_per_school ?? 1;
                                                    $checked = in_array($cat->id, $selectedCategories);
                                                    $disabled = $isFull || ($isLocked && !$checked);
                                                @endphp
                                                <div class="border rounded-xl p-4 transition-all {{ $checked ? 'border-primary bg-primary/[0.03] shadow-xs' : 'border-outline-variant/40 bg-white' }} {{ $disabled ? 'opacity-50' : '' }}">
                                                    <div class="flex items-start gap-3">
                                                        <input type="checkbox" wire:click="toggleCategory({{ $cat->id }})" id="cat_{{ $cat->id }}"
                                                            {{ $isFull ? 'disabled' : '' }}
                                                            {{ $disabled ? 'disabled' : '' }}
                                                            {{ $checked ? 'checked' : '' }}
                                                            class="mt-1 h-4 w-4 accent-primary shrink-0 cursor-pointer rounded">

                                                        <div class="flex-1 min-w-0">
                                                            <label for="cat_{{ $cat->id }}" class="text-sm font-bold text-deep-slate cursor-pointer block leading-tight">
                                                                {{ $cat->parent?->name }}
                                                                <span class="font-normal text-on-surface-variant">—</span>
                                                                {{ $cat->name }}
                                                            </label>

                                                            <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                                                <span class="inline-flex items-center gap-1 rounded-md bg-surface-container px-2 py-0.5 text-[10px] font-semibold text-deep-slate border border-outline-variant/30">
                                                                    <i class="ti ti-users text-on-surface-variant"></i> Kuota: {{ $cat->registrations_count }} / {{ $cat->kuota ?? '∞' }} Pasukan
                                                                </span>

                                                                <span class="inline-flex items-center gap-1 rounded-md bg-surface-container px-2 py-0.5 text-[10px] font-semibold text-deep-slate border border-outline-variant/30">
                                                                    Maks. {{ $maxPerSchool }} per Sekolah
                                                                </span>

                                                                @if($cat->registration_fee)
                                                                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold text-emerald-600 border border-emerald-500/20">
                                                                        Rp {{ number_format($cat->registration_fee, 0, ',', '.') }}/pasukan
                                                                    </span>
                                                                @else
                                                                    <span class="inline-flex items-center gap-1 rounded-md bg-surface-container px-2 py-0.5 text-[10px] font-bold text-on-surface-variant border border-outline-variant/30">
                                                                        Gratis
                                                                    </span>
                                                                @endif

                                                                @if($isFull)
                                                                    <span class="inline-flex items-center gap-1 rounded-md bg-red-500/10 px-2 py-0.5 text-[10px] font-bold text-red-600 border border-red-500/20">
                                                                        Kuota Penuh
                                                                    </span>
                                                                @endif
                                                            </div>

                                                            {{-- Multi-Pasukan Team Count --}}
                                                            @if($checked && $maxPerSchool > 1)
                                                                <div class="mt-3 pt-3 border-t border-outline-variant/30 flex items-center gap-2">
                                                                    <span class="text-xs text-on-surface-variant font-medium">Jumlah Pasukan:</span>
                                                                    <div class="flex items-center gap-1">
                                                                        @for($i = 1; $i <= $maxPerSchool; $i++)
                                                                            <button type="button" wire:click="setTeamCount({{ $cat->id }}, {{ $i }})"
                                                                                class="h-7 w-8 text-xs font-bold rounded-lg transition cursor-pointer border {{ ($teamCounts[$cat->id] ?? 1) == $i ? 'bg-primary text-white border-primary shadow-xs' : 'bg-surface-container text-deep-slate border-outline-variant/30 hover:border-primary/50' }}">
                                                                                {{ $i }}
                                                                            </button>
                                                                        @endfor
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if($categories->isEmpty())
                                <div class="text-center py-8 text-xs font-semibold text-on-surface-variant">
                                    Belum ada kategori lomba yang tersedia saat ini.
                                </div>
                            @endif

                            {{-- Bottom Action Row --}}
                            <div class="flex items-center justify-between mt-8 border-t border-outline-variant/30 pt-5">
                                <div>
                                    @if(count($selectedCategories) > 0)
                                        <span class="text-xs font-bold text-deep-slate">
                                            {{ count($selectedCategories) }} kategori dipilih
                                        </span>
                                    @else
                                        <span class="text-xs text-on-surface-variant font-medium italic">
                                            Pilih minimal satu kategori
                                        </span>
                                    @endif
                                </div>
                                <button wire:click="nextStep" class="btn-secondary py-2.5 px-5 font-bold text-xs inline-flex items-center gap-1.5 cursor-pointer">
                                    Lanjutkan <i class="ti ti-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- STEP 2: Data Sekolah --}}
                @if($step === 2)
                    <div class="surface-card overflow-hidden">
                        <div class="bg-surface-container px-5 py-4 border-b border-outline-variant/40">
                            <h3 class="font-display text-sm font-bold text-deep-slate inline-flex items-center gap-2 m-0">
                                <i class="ti ti-school text-primary"></i> Data Sekolah &amp; Kontak Penanggung Jawab
                            </h3>
                            <p class="text-xs text-on-surface-variant font-medium m-0 mt-0.5">
                                Field bertanda <span class="text-red-500 font-bold">*</span> wajib diisi. Berkas persyaratan diunggah dari portal kontingen setelah pendaftaran.
                            </p>
                        </div>

                        <div class="p-5 md:p-6">
                            <div class="grid gap-4 sm:grid-cols-2">
                                @foreach($this->fields as $field)
                                    @php
                                        $namaField = 'fieldValues.' . $field->field_key;
                                        $nilai = $this->nilaiField($field);
                                        $id = 'field_' . $field->field_key;
                                    @endphp
                                    <div class="{{ in_array($field->type, ['textarea'], true) ? 'sm:col-span-2' : '' }}">
                                        <label for="{{ $id }}" class="text-xs font-bold text-deep-slate block mb-1">
                                            {{ $field->label }}
                                            @if($field->is_required) <span class="text-red-500">*</span> @endif
                                        </label>

                                        @if($field->type === 'textarea')
                                            <textarea id="{{ $id }}" rows="3" wire:model="{{ $namaField }}"
                                                placeholder="{{ $field->help_text }}" class="field-input w-full text-xs"></textarea>
                                        @elseif($field->type === 'select')
                                            <select id="{{ $id }}" wire:model="{{ $namaField }}" class="field-input w-full text-xs">
                                                <option value="">— Pilih {{ $field->label }} —</option>
                                                @foreach($field->options ?? [] as $opsi)
                                                    <option value="{{ $opsi['value'] }}">{{ $opsi['label'] ?? $opsi['value'] }}</option>
                                                @endforeach
                                            </select>
                                        @elseif($field->type === 'wilayah')
                                            @include('livewire.public.partials._wilayah-field', [
                                                'field' => $field,
                                                'kelas' => 'field-input w-full text-xs',
                                            ])
                                        @elseif($field->type === 'date')
                                            <input type="date" id="{{ $id }}" wire:model="{{ $namaField }}" class="field-input w-full text-xs">
                                        @elseif($field->type === 'number')
                                            <input type="number" id="{{ $id }}" wire:model="{{ $namaField }}" class="field-input w-full text-xs">
                                        @else
                                            <input type="text" id="{{ $id }}" wire:model="{{ $namaField }}"
                                                placeholder="{{ $field->help_text }}" class="field-input w-full text-xs">
                                        @endif

                                        @if($field->help_text && $field->type !== 'text')
                                            <span class="text-[10px] text-on-surface-variant font-medium mt-1 block leading-normal">{{ $field->help_text }}</span>
                                        @endif
                                        @error($namaField) <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex justify-between items-center mt-8 border-t border-outline-variant/30 pt-5">
                                <button wire:click="prevStep" class="btn-ghost py-2.5 px-4 font-bold text-xs inline-flex items-center gap-1.5 cursor-pointer">
                                    <i class="ti ti-arrow-left"></i> Kembali
                                </button>
                                <button wire:click="nextStep" class="btn-secondary py-2.5 px-5 font-bold text-xs inline-flex items-center gap-1.5 cursor-pointer">
                                    Lanjutkan <i class="ti ti-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- STEP 3: Review & Submit --}}
                @if($step === 3)
                    <div class="surface-card overflow-hidden">
                        <div class="bg-surface-container px-5 py-4 border-b border-outline-variant/40">
                            <h3 class="font-display text-sm font-bold text-deep-slate inline-flex items-center gap-2 m-0">
                                <i class="ti ti-checkbox text-primary"></i> Konfirmasi Booking Slot
                            </h3>
                            <p class="text-xs text-on-surface-variant font-medium m-0 mt-0.5">
                                Periksa kembali rincian data sebelum mengirimkan pendaftaran.
                            </p>
                        </div>

                        <div class="p-5 md:p-6 space-y-5">
                            {{-- Data Sekolah Summary --}}
                            <div>
                                <h4 class="font-display text-xs font-bold text-deep-slate uppercase tracking-wider mb-2.5 inline-flex items-center gap-1.5">
                                    <i class="ti ti-school text-primary"></i> Data Sekolah
                                </h4>
                                <div class="bg-surface-container-low border border-outline-variant/30 rounded-xl p-4">
                                    <div class="grid gap-3 sm:grid-cols-2 text-xs leading-normal">
                                        @foreach($this->fields as $field)
                                            @php $nilai = $this->nilaiField($field); @endphp
                                            <div>
                                                <span class="text-[11px] text-on-surface-variant font-medium block">{{ $field->label }}</span>
                                                @if($nilai !== '')
                                                    @if($field->type === 'select')
                                                        @php
                                                            $opsiTerpilih = collect($field->options ?? [])->firstWhere('value', $nilai);
                                                        @endphp
                                                        <p class="font-bold text-deep-slate m-0 mt-0.5">{{ $opsiTerpilih['label'] ?? $nilai }}</p>
                                                    @elseif($field->type === 'wilayah')
                                                        <p class="font-bold text-deep-slate m-0 mt-0.5">{{ \App\Services\WilayahService::nama($nilai) }}</p>
                                                    @else
                                                        <p class="font-bold text-deep-slate m-0 mt-0.5">{{ $nilai }}</p>
                                                    @endif
                                                @else
                                                    <p class="text-on-surface-variant m-0 mt-0.5">&mdash;</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            {{-- Kategori Selection Summary --}}
                            <div>
                                <h4 class="font-display text-xs font-bold text-deep-slate uppercase tracking-wider mb-2.5 inline-flex items-center gap-1.5">
                                    <i class="ti ti-medal text-primary"></i> Kategori Lomba Dipilih
                                </h4>
                                <div class="bg-surface-container-low border border-outline-variant/30 rounded-xl divide-y divide-outline-variant/20 overflow-hidden">
                                    @foreach($selectedCategories as $scId)
                                        @php $scCat = $categories->firstWhere('id', (int) $scId); @endphp
                                        @if($scCat)
                                            <div class="p-3.5 flex items-center justify-between text-xs">
                                                <div>
                                                    <span class="font-bold text-deep-slate block">{{ $scCat->full_name }}</span>
                                                    <span class="text-[10px] text-on-surface-variant font-medium">
                                                        Biaya: {{ $scCat->registration_fee ? 'Rp ' . number_format($scCat->registration_fee, 0, ',', '.') . '/pasukan' : 'Gratis' }}
                                                    </span>
                                                </div>
                                                <span class="chip py-0.5 px-2 bg-primary/10 font-bold text-[11px]">
                                                    {{ $teamCounts[$scCat->id] ?? 1 }} pasukan
                                                </span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            {{-- Fee Summary --}}
                            @php
                                $totalFee = 0;
                                $hasFee = false;
                                foreach($selectedCategories as $scId) {
                                    $scCat = $categories->firstWhere('id', (int) $scId);
                                    if ($scCat && $scCat->registration_fee) {
                                        $totalFee += $scCat->registration_fee * ($teamCounts[$scCat->id] ?? 1);
                                        $hasFee = true;
                                    }
                                }
                            @endphp

                            @if($hasFee)
                                <div class="bg-surface-container-low border border-outline-variant/30 rounded-xl p-4 flex items-center justify-between">
                                    <div>
                                        <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider block">Total Estimasi Biaya</span>
                                        <p class="text-[11px] text-on-surface-variant font-medium m-0 mt-0.5">
                                            Pembayaran dilakukan melalui portal kontingen setelah booking.
                                        </p>
                                    </div>
                                    <span class="font-display font-extrabold text-lg text-deep-slate">
                                        Rp {{ number_format($totalFee, 0, ',', '.') }}
                                    </span>
                                </div>
                            @endif

                            {{-- Informational Notice --}}
                            <div class="p-3.5 rounded-xl bg-surface-container border border-outline-variant/30 text-xs text-on-surface-variant leading-relaxed font-medium">
                                <span class="font-bold text-deep-slate block mb-1">
                                    <i class="ti ti-info-circle text-primary"></i> Informasi Selanjutnya:
                                </span>
                                Setelah menekan tombol booking, Anda akan langsung diarahkan ke <strong>Portal Kontingen</strong> untuk menyimpan data pasukan, nomor kontak, serta mengunggah berkas persyaratan.
                            </div>

                            {{-- Action buttons --}}
                            <div class="flex justify-between items-center border-t border-outline-variant/30 pt-5">
                                <button wire:click="prevStep" class="btn-ghost py-2.5 px-4 font-bold text-xs inline-flex items-center gap-1.5 cursor-pointer">
                                    <i class="ti ti-arrow-left"></i> Kembali
                                </button>
                                <button wire:click="submit" class="btn-secondary py-2.5 px-6 font-bold text-xs inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-60" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="submit">
                                        <i class="ti ti-circle-check text-sm"></i> Booking Sekarang
                                    </span>
                                    <span wire:loading wire:target="submit" class="inline-flex items-center gap-2">
                                        <span class="h-3.5 w-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                        Memproses...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

        </div>
    </div>
</div>
