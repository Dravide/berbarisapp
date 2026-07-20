<div class="min-h-screen bg-surface">

    {{-- ========== COVER BANNER / FALLBACK GRADIENT ========== --}}
    <div class="container-landing pt-6">
        @if($eventner->poster)
            <div class="relative w-full aspect-[21/9] md:aspect-[3/1] rounded-2xl overflow-hidden shadow-md border border-outline-variant/30 bg-black/5">
                <img src="{{ asset('storage/' . $eventner->poster) }}" alt="Poster {{ $eventner->nama_event }}" class="w-full h-full object-cover">
            </div>
        @else
            <div class="relative overflow-hidden bg-gradient-to-br from-primary via-[#0053da] to-tertiary rounded-2xl h-36 md:h-48">
                {{-- Decorative light orbs --}}
                <div class="absolute -left-20 -top-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
                <div class="absolute -right-20 -bottom-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
            </div>
        @endif
    </div>

    {{-- ========== HEADER INFO BLOCK ========== --}}
    <div class="container-landing pt-6">
        <div class="flex items-start gap-4">
            @if($eventner->logo_event)
                <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="h-16 w-16 md:h-20 md:w-20 rounded-2xl object-cover shadow-sm border border-outline-variant/30 shrink-0" alt="{{ $eventner->nama_event }}">
            @else
                <div class="flex h-16 w-16 md:h-20 md:w-20 items-center justify-center rounded-2xl bg-primary/10 text-primary border border-outline-variant/30 shrink-0">
                    <i class="ti ti-calendar-event text-3xl"></i>
                </div>
            @endif
            <div class="min-w-0">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-0.5 text-[10px] font-bold uppercase tracking-wider text-primary border border-primary/20 mb-2">
                    <i class="ti ti-edit"></i>
                    Pendaftaran Kompetisi
                </span>
                <h1 class="font-display text-2xl font-extrabold tracking-tight text-deep-slate leading-tight sm:text-3xl">
                    Booking Slot Pasukan
                </h1>
                <p class="mt-2 text-sm font-semibold text-on-surface-variant">
                    Event: <span class="text-primary font-bold">{{ $eventner->nama_event }}</span>
                </p>
            </div>
        </div>
    </div>

    {{-- ========== FLASH MESSAGES ========== --}}
    @if(session('error'))
        <div class="container-landing pt-6">
            <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-600 text-sm font-semibold flex items-center gap-2">
                <i class="ti ti-alert-circle text-lg"></i>
                {{ session('error') }}
            </div>
        </div>
    @endif

    {{-- ========== PROGRESS STEPPER ========== --}}
    <div class="container-landing pt-8 pb-4">
        <div class="flex items-center justify-center gap-2 max-w-xl mx-auto flex-wrap">
            @for($i = 1; $i <= 3; $i++)
                <div class="flex items-center gap-2">
                    <div class="h-9 w-9 rounded-full flex items-center justify-center font-bold text-sm border transition-all duration-300 {{ $step >= $i ? 'bg-primary text-white border-primary shadow-sm' : 'bg-surface-container text-on-surface-variant border-outline-variant/30' }}">
                        @if($step > $i)
                            <i class="ti ti-check text-base"></i>
                        @else
                            {{ $i }}
                        @endif
                    </div>
                    <span class="text-xs font-bold transition-all duration-300 {{ $step >= $i ? 'text-deep-slate' : 'text-on-surface-variant' }}">
                        @if($i == 1) Pilih Kategori
                        @elseif($i == 2) Data Sekolah
                        @else Konfirmasi
                        @endif
                    </span>
                </div>
                @if($i < 3)
                    <div class="w-10 h-0.5 shrink-0 rounded transition-all duration-300 {{ $step > $i ? 'bg-primary' : 'bg-outline-variant/30' }}"></div>
                @endif
            @endfor
        </div>
    </div>

    {{-- ========== MAIN CONTENT FORM ========== --}}
    <div class="container-landing py-6">
        <div class="max-w-3xl mx-auto">

            {{-- 1. CLOSED REGISTRATION STATE --}}
            @if(($eventner->registration_status ?? 'open') == 'closed')
                <div class="surface-card p-8 md:p-12 text-center flex flex-col items-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10 text-red-500 border border-red-500/20 mb-6">
                        <i class="ti ti-lock text-3xl"></i>
                    </div>
                    <h2 class="font-display text-xl font-bold text-deep-slate mb-3">Pendaftaran Ditutup</h2>
                    <p class="text-sm text-on-surface-variant leading-relaxed max-w-lg mb-8">
                        Mohon maaf, pendaftaran slot kontingen untuk event <strong>{{ $eventner->nama_event }}</strong> saat ini telah ditutup secara resmi oleh panitia.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <a href="{{ event_url($eventner, 'detail') }}" class="btn-primary py-3 px-6 text-sm font-bold text-center text-decoration-none">
                            Kembali Ke Event
                        </a>
                        <a href="{{ event_url($eventner, 'participant') }}" class="btn-ghost py-3 px-6 text-sm font-bold text-center text-decoration-none">
                            Lihat Daftar Peserta
                        </a>
                    </div>

                    @if($eventner->link_whatsapp)
                        <div class="mt-8 pt-6 border-t border-outline-variant/30 w-full">
                            <span class="text-xs text-on-surface-variant block mb-2">Ada pertanyaan terkait pendaftaran?</span>
                            @php $waNumber = preg_replace('/[^0-9]/', '', $eventner->link_whatsapp); @endphp
                            <a href="https://wa.me/{{ $waNumber }}" target="_blank" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 inline-flex items-center gap-1 text-decoration-none transition">
                                <i class="ti ti-brand-whatsapp text-sm"></i> Hubungi Panitia (WhatsApp)
                            </a>
                        </div>
                    @endif
                </div>
            @else

                {{-- STEP 1: Pilih Kategori --}}
                @if($step === 1)
                    <div class="surface-card overflow-hidden">
                        <div class="bg-surface-container px-6 py-4 border-b border-outline-variant/40">
                            <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2">
                                <i class="ti ti-list text-primary"></i>
                                Pilih Kategori Lomba
                            </h3>
                        </div>
                        <div class="p-6">
                            @error('selectedCategories')
                                <div class="p-3.5 rounded-lg bg-red-500/10 border border-red-500/20 text-red-600 text-xs font-semibold mb-4 leading-normal">
                                    {{ $message }}
                                </div>
                            @enderror

                            <p class="text-sm text-on-surface-variant mb-2">Pilih tingkat terlebih dahulu, lalu pilih lomba di bawah tingkat tersebut.</p>

                            @php
                                $lockedTingkat = $this->lockedTingkat();
                                $grouped = $categories->groupBy('name');
                            @endphp

                            @foreach($grouped as $tingkatName => $cats)
                                @php
                                    $isLocked = $lockedTingkat !== null && $tingkatName !== $lockedTingkat;
                                @endphp
                                <div class="mb-6 last:mb-0 {{ $isLocked ? 'opacity-40 pointer-events-none' : '' }}">
                                    <h4 class="font-display text-sm font-bold text-deep-slate mb-3 flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center h-7 w-7 rounded-lg bg-primary/10 text-primary text-xs font-bold">
                                            {{ substr($tingkatName, 0, 4) }}
                                        </span>
                                        {{ $tingkatName }}
                                        @if($isLocked)
                                            <span class="text-[10px] font-semibold text-on-surface-variant ml-1">(terkunci)</span>
                                        @endif
                                    </h4>

                                    <div class="flex flex-col gap-2 ml-9">
                                        @foreach($cats as $cat)
                                            @php
                                                $isFull = $cat->kuota && $cat->registrations_count >= $cat->kuota;
                                                $maxPerSchool = $cat->max_registrations_per_school ?? 1;
                                                $checked = in_array($cat->id, $selectedCategories);
                                                $disabled = $isFull || ($isLocked && !$checked);
                                            @endphp
                                            <div class="border rounded-xl p-3.5 transition-all duration-200 {{ $checked ? 'border-primary bg-primary/5 shadow-sm' : 'border-outline-variant/50' }} {{ $disabled ? 'opacity-50' : '' }}">
                                                <div class="flex items-start gap-3">
                                                    <input type="checkbox" wire:click="toggleCategory({{ $cat->id }})" id="cat_{{ $cat->id }}"
                                                        {{ $isFull ? 'disabled' : '' }}
                                                        {{ $disabled ? 'disabled' : '' }}
                                                        {{ $checked ? 'checked' : '' }}
                                                        class="mt-1 h-5 w-5 accent-primary shrink-0 cursor-pointer rounded">

                                                    <div class="flex-1 min-w-0">
                                                        <label for="cat_{{ $cat->id }}" class="text-sm font-bold text-deep-slate cursor-pointer block leading-tight">
                                                            {{ $cat->parent?->name }}
                                                            <span class="font-normal text-on-surface-variant">—</span>
                                                            {{ $cat->name }}
                                                        </label>

                                                        <div class="flex flex-wrap gap-2 mt-2">
                                                            <span class="inline-flex items-center gap-1 rounded-md bg-primary/10 px-2 py-0.5 text-[10px] font-bold text-primary border border-primary/20">
                                                                <i class="ti ti-users"></i> {{ $cat->registrations_count }} / {{ $cat->kuota ?? '∞' }} Pasukan
                                                            </span>
                                                            <span class="inline-flex items-center gap-1 rounded-md bg-[#5a7d00]/10 px-2 py-0.5 text-[10px] font-bold text-[#5a7d00] border border-[#5a7d00]/20">
                                                                Maks {{ $maxPerSchool }} per Sekolah
                                                            </span>
                                                            @if($isFull)
                                                                <span class="inline-flex items-center gap-1 rounded-md bg-red-500/10 px-2 py-0.5 text-[10px] font-bold text-red-600 border border-red-500/20">Kuota Penuh</span>
                                                            @endif
                                                        </div>

                                                        {{-- Team Count Multi-Pasukan --}}
                                                        @if($checked && $maxPerSchool > 1)
                                                            <div class="mt-4 flex items-center gap-2 bg-white border border-outline-variant/40 rounded-lg p-2 max-w-sm">
                                                                <span class="text-xs text-on-surface-variant font-bold shrink-0">Jumlah Pasukan:</span>
                                                                <div class="flex gap-1.5 ml-auto">
                                                                    @for($i = 1; $i <= $maxPerSchool; $i++)
                                                                        <button type="button" wire:click="setTeamCount({{ $cat->id }}, {{ $i }})" class="h-8 w-10 text-xs font-bold rounded transition cursor-pointer {{ ($teamCounts[$cat->id] ?? 1) == $i ? 'bg-primary text-white shadow-sm' : 'bg-surface-container text-primary hover:bg-primary/5' }}">
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

                            @if($categories->isEmpty())
                                <div class="text-center py-8 text-sm font-semibold text-on-surface-variant">
                                    Belum ada kategori lomba yang tersedia saat ini.
                                </div>
                            @endif

                            {{-- Selected summary --}}
                            @if(count($selectedCategories) > 0)
                                <div class="mt-6 p-4 bg-primary/5 border border-primary/20 rounded-xl">
                                    <span class="text-xs font-bold text-primary uppercase tracking-wider block mb-2">
                                        <i class="ti ti-check-circle"></i> {{ count($selectedCategories) }} Kategori Dipilih
                                    </span>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($selectedCategories as $scId)
                                            @php $sc = $categories->firstWhere('id', $scId); @endphp
                                            @if($sc)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 border border-primary/20 px-3 py-1 text-xs font-bold text-primary">
                                                    {{ $sc->full_name }}
                                                    @if(($sc->max_registrations_per_school ?? 1) > 1)
                                                        <span class="text-[10px] opacity-75">({{ $teamCounts[$sc->id] ?? 1 }} pasukan)</span>
                                                    @endif
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="text-right mt-6 border-t border-outline-variant/30 pt-6">
                                <button wire:click="nextStep" class="btn-primary py-3 px-6 font-bold text-sm inline-flex items-center gap-1.5 cursor-pointer">
                                    Lanjutkan <i class="ti ti-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- STEP 2: Data Sekolah --}}
                @if($step === 2)
                    <div class="surface-card overflow-hidden">
                        <div class="bg-surface-container px-6 py-4 border-b border-outline-variant/40">
                            <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2">
                                <i class="ti ti-school text-primary"></i>
                                Data Sekolah &amp; Akun
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label class="text-sm font-bold text-deep-slate block mb-1.5">NPSN <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="npsn" placeholder="Masukkan Nomor Pokok Sekolah Nasional" maxlength="20" class="field-input w-full">
                                    @error('npsn') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="text-sm font-bold text-deep-slate block mb-1.5">Nama Sekolah <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="nama_sekolah" placeholder="Masukkan nama sekolah resmi" class="field-input w-full">
                                    @error('nama_sekolah') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="text-sm font-bold text-deep-slate block mb-1.5">No. HP / WhatsApp <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="no_hp" placeholder="Masukkan No. HP Pelatih/Pembina" class="field-input w-full">
                                    @error('no_hp') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="text-sm font-bold text-deep-slate block mb-1.5">Email Penanggung Jawab <span class="text-red-500">*</span></label>
                                    <input type="email" wire:model="school_email" placeholder="contoh@email.com" class="field-input w-full" required>
                                    <span class="text-[10px] text-on-surface-variant font-medium mt-1 block leading-normal">Magic link dikirim ke email ini untuk akses dashboard kontingen.</span>
                                    @error('school_email') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="text-sm font-bold text-deep-slate block mb-1.5">Nama Pelatih / Pembina <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="nama_pelatih" placeholder="Masukkan nama pelatih" class="field-input w-full" required>
                                    @error('nama_pelatih') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    {{-- Spacing --}}
                                </div>

                            </div>

                            <div class="flex justify-between items-center mt-8 border-t border-outline-variant/30 pt-6">
                                <button wire:click="prevStep" class="btn-ghost py-3 px-6 font-bold text-sm inline-flex items-center gap-1.5 cursor-pointer">
                                    <i class="ti ti-arrow-left"></i> Kembali
                                </button>
                                <button wire:click="nextStep" class="btn-primary py-3 px-6 font-bold text-sm inline-flex items-center gap-1.5 cursor-pointer">
                                    Lanjutkan <i class="ti ti-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- STEP 3: Review & Submit --}}
                @if($step === 3)
                    <div class="surface-card overflow-hidden">
                        <div class="bg-surface-container px-6 py-4 border-b border-outline-variant/40">
                            <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2">
                                <i class="ti ti-checkbox text-primary"></i>
                                Konfirmasi Booking Slot
                            </h3>
                        </div>
                        <div class="p-6">
                            {{-- School Info --}}
                            <h4 class="font-display text-sm font-bold text-deep-slate mb-3 inline-flex items-center gap-1.5">
                                <i class="ti ti-school text-primary"></i> Data Sekolah
                            </h4>
                            <div class="bg-surface-container-low border border-outline-variant/40 rounded-xl p-4 mb-6">
                                <div class="grid gap-4 sm:grid-cols-2 text-sm leading-normal">
                                    <div>
                                        <span class="text-xs text-on-surface-variant font-medium block">NPSN</span>
                                        <p class="font-bold text-deep-slate m-0">{{ $npsn }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs text-on-surface-variant font-medium block">Nama Sekolah</span>
                                        <p class="font-bold text-deep-slate m-0">{{ $nama_sekolah }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs text-on-surface-variant font-medium block">No. HP / WhatsApp</span>
                                        <p class="font-bold text-deep-slate m-0">{{ $no_hp }}</p>
                                    </div>
                                    @if($school_email)
                                        <div>
                                            <span class="text-xs text-on-surface-variant font-medium block">Email</span>
                                            <p class="font-bold text-deep-slate m-0">{{ $school_email }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Kategori selection --}}
                            <h4 class="font-display text-sm font-bold text-deep-slate mb-3 inline-flex items-center gap-1.5">
                                <i class="ti ti-medal text-primary"></i> Kategori Pilihan
                            </h4>
                            <div class="bg-surface-container-low border border-outline-variant/40 rounded-xl p-4 mb-6 space-y-2">
                                @foreach($selectedCategories as $scId)
                                    @php $scCat = $categories->firstWhere('id', (int) $scId); @endphp
                                    @if($scCat)
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="font-bold text-deep-slate">{{ $scCat->full_name }}</span>
                                            <span class="chip py-1 px-3 bg-primary/10 font-bold text-xs">
                                                {{ $teamCounts[$scCat->id] ?? 1 }} pasukan
                                            </span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            {{-- Informational Warning Box --}}
                            <div class="bg-amber-500/5 border border-amber-500/20 rounded-xl p-4 mb-6 flex gap-3 text-xs leading-normal">
                                <i class="ti ti-info-circle text-amber-500 text-lg shrink-0 mt-0.5"></i>
                                <div>
                                    <p class="font-bold text-amber-800 m-0">Status Pendaftaran: Booking</p>
                                    <p class="text-on-surface-variant font-medium mt-1 mb-0">
                                        Pendaftaran Anda akan berstatus <strong>Booking</strong>. Setelah Technical Meeting, Anda dapat mengonfirmasi berkas dan melengkapi rincian anggota pasukan melalui link yang kami kirimkan ke WhatsApp/Email.
                                    </p>
                                </div>
                            </div>

                            {{-- Action buttons --}}
                            <div class="flex justify-between items-center border-t border-outline-variant/30 pt-6">
                                <button wire:click="prevStep" class="btn-ghost py-3 px-6 font-bold text-sm inline-flex items-center gap-1.5 cursor-pointer">
                                    <i class="ti ti-arrow-left"></i> Kembali
                                </button>
                                <button wire:click="submit" class="btn-primary !bg-emerald-500 hover:!bg-emerald-600 !text-white border-none py-3.5 px-6 font-bold text-sm inline-flex items-center gap-1.5 cursor-pointer shadow-md hover:shadow-lg disabled:opacity-60" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="submit">
                                        <i class="ti ti-circle-check text-base"></i> Booking Sekarang
                                    </span>
                                    <span wire:loading wire:target="submit" class="inline-flex items-center gap-2">
                                        <span class="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
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
