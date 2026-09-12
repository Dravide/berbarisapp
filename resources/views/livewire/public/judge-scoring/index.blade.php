@php
    // Warna tombol nilai: pakai token tema frontend (primary/surface/outline-variant).
    $scoreBtnBase = 'rounded-xl border font-bold transition select-none';
@endphp

<div class="min-h-screen bg-surface">

    <div class="container-landing py-5">

        {{-- ========== 1. PILIH TINGKAT LOMBA ========== --}}
        @if($view === 'categories')
            <h2 class="font-display text-sm font-bold uppercase tracking-wider text-on-surface-variant mb-3">
                1. Pilih Tingkat Lomba
            </h2>

            @if($categories->isEmpty())
                <div class="rounded-2xl border border-outline-variant/30 bg-white p-8 text-center">
                    <i class="ti ti-info-circle text-3xl text-on-surface-variant"></i>
                    <p class="text-sm text-on-surface-variant mt-2 mb-0">
                        Belum ada tingkat lomba yang ditugaskan ke Anda. Hubungi panitia.
                    </p>
                </div>
            @else
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach($categories as $cat)
                        <button type="button" wire:click="selectCategory({{ $cat->id }})"
                                class="rounded-2xl border border-outline-variant/30 bg-white p-5 text-left shadow-sm hover:border-primary hover:shadow-md active:scale-[0.99] transition">
                            <p class="font-display text-lg font-bold text-on-surface m-0">{{ $cat->name }}</p>
                            <p class="text-xs text-on-surface-variant mt-0.5 mb-2">{{ $cat->parent?->name ?? '—' }}</p>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary border border-primary/20">
                                <i class="ti ti-users"></i> {{ $cat->registrations_count }} peserta
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif
        @endif

        {{-- ========== 2. PESERTA (URUTAN PANGGUNG) ========== --}}
        @if($view === 'participants')
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-display text-sm font-bold uppercase tracking-wider text-on-surface-variant m-0">
                    2. Pilih Peserta
                </h2>
                <button type="button" wire:click="backToCategories"
                        class="text-xs font-semibold text-primary hover:underline">
                    <i class="ti ti-arrow-left"></i> Ganti tingkat
                </button>
            </div>

            @if($participants->isEmpty())
                <div class="rounded-2xl border border-outline-variant/30 bg-white p-8 text-center">
                    <p class="text-sm text-on-surface-variant m-0">Belum ada peserta terdaftar di tingkat ini.</p>
                </div>
            @else
                <div class="rounded-2xl border border-outline-variant/30 bg-white overflow-hidden shadow-sm divide-y divide-outline-variant/20">
                    @foreach($participants as $p)
                        @php
                            $badge = match($p->judge_status) {
                                'final'   => ['text' => 'Final',   'class' => 'bg-emerald-100 text-emerald-700 border-emerald-200', 'icon' => 'ti-lock'],
                                'dinilai' => ['text' => 'Dinilai', 'class' => 'bg-amber-100 text-amber-700 border-amber-200',       'icon' => 'ti-progress'],
                                default   => ['text' => 'Belum',   'class' => 'bg-slate-100 text-slate-600 border-slate-200',       'icon' => 'ti-circle-dashed'],
                            };
                        @endphp
                        <button type="button" wire:click="selectParticipant({{ $p->id }})"
                                class="w-full flex items-center gap-4 px-4 py-4 text-left hover:bg-primary/5 active:bg-primary/10 transition">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary font-display text-sm font-bold border border-primary/20">
                                {{ $p->urutan_tampil ?? '–' }}
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold text-on-surface truncate">{{ $p->nama_sekolah }}</span>
                                @if($p->nama_pelatih)
                                    <span class="block text-xs text-on-surface-variant truncate">Pelatih: {{ $p->nama_pelatih }}</span>
                                @endif
                            </span>
                            <span class="shrink-0 inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-[11px] font-bold {{ $badge['class'] }}">
                                <i class="ti {{ $badge['icon'] }}"></i> {{ $badge['text'] }}
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif
        @endif

        {{-- ========== 3. PENILAIAN ========== --}}
        @if($view === 'scoring')
            @php
                $registration = $this->registration;
                $categories = $this->assessmentCategories;
                $totalCriteria = $categories->flatMap(fn($c) => $c->subCategories->flatMap(fn($s) => $s->criterias))->count();
                $filledCriteria = collect($scores)->filter(fn($v) => $v !== '' && $v !== null)->count();
            @endphp

            <div class="flex items-center justify-between mb-3 gap-3">
                <div class="min-w-0">
                    <h2 class="font-display text-sm font-bold uppercase tracking-wider text-on-surface-variant m-0">
                        3. Penilaian
                    </h2>
                    <p class="text-sm font-bold text-on-surface truncate m-0 mt-0.5">
                        @if($registration->urutan_tampil) No. {{ $registration->urutan_tampil }} · @endif
                        {{ $registration->nama_sekolah }}
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-3">
                    {{-- Dua cara mengisi: satu kriteria per layar (maju otomatis)
                         atau semua kriteria sekaligus. Pilihan tidak mengubah
                         nilai yang sudah tersimpan. --}}
                    <div class="inline-flex rounded-xl border border-outline-variant/40 bg-white p-0.5">
                        <button type="button" wire:click="setCriteriaMode('satu-satu')"
                                class="rounded-[10px] px-3 py-1.5 text-[11px] font-bold transition
                                    {{ $criteriaMode === 'satu-satu' ? 'bg-primary text-white' : 'text-on-surface-variant hover:text-primary' }}">
                            <i class="ti ti-square-check"></i> Satu per Satu
                        </button>
                        <button type="button" wire:click="setCriteriaMode('semua')"
                                class="rounded-[10px] px-3 py-1.5 text-[11px] font-bold transition
                                    {{ $criteriaMode === 'semua' ? 'bg-primary text-white' : 'text-on-surface-variant hover:text-primary' }}">
                            <i class="ti ti-list"></i> Semua
                        </button>
                    </div>

                    <button type="button" wire:click="backToParticipants"
                            class="text-xs font-semibold text-primary hover:underline">
                        <i class="ti ti-arrow-left"></i> Daftar peserta
                    </button>
                </div>
            </div>

            @if($isFinalized)
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 mb-4 flex items-start gap-3">
                    <i class="ti ti-lock text-xl text-emerald-600 shrink-0 mt-0.5"></i>
                    <div>
                        <p class="text-sm font-bold text-emerald-800 m-0">Nilai terkunci</p>
                        <p class="text-xs text-emerald-700 m-0 mt-0.5">
                            Penilaian untuk peserta ini sudah difinalisasi. Hubungi panitia bila perlu koreksi.
                        </p>
                    </div>
                </div>
            @endif

            @php $flatCriteria = $this->flatCriteria; @endphp

            @if($flatCriteria === [])
                <div class="rounded-2xl border border-outline-variant/30 bg-white p-8 text-center">
                    <p class="text-sm text-on-surface-variant m-0">
                        Belum ada rubrik penilaian untuk tingkat lomba ini. Hubungi panitia.
                    </p>
                </div>
            @elseif($criteriaMode === 'satu-satu')
                {{-- ===== MODE SATU PER SATU =====
                     Satu kriteria per layar; ketuk nilai → otomatis pindah ke
                     kriteria berikutnya. Tombol nilai dibuat besar karena hanya
                     ada satu kriteria yang perlu disentuh. --}}
                @php $current = $this->currentCriteria; @endphp
                <div class="rounded-2xl border border-outline-variant/30 bg-white shadow-sm overflow-hidden">
                    <div class="px-4 py-3 bg-primary/5 border-b border-outline-variant/20 flex items-center justify-between gap-3">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant m-0 truncate">
                            {{ $current['category'] }} · {{ $current['sub'] }}
                        </p>
                        <span class="shrink-0 rounded-full bg-white border border-outline-variant/40 px-3 py-1 text-[11px] font-bold text-on-surface">
                            {{ $currentCriteriaIndex + 1 }} / {{ count($flatCriteria) }}
                        </span>
                    </div>

                    <div class="px-4 py-6">
                        <p class="font-display text-xl font-bold text-on-surface text-center m-0 mb-6">
                            {{ $current['name'] }}
                        </p>

                        @include('livewire.public.judge-scoring._score-options', [
                            'criteriaId' => $current['id'],
                            'scoreOptions' => $current['score_options'],
                            'scores' => $scores,
                            'isFinalized' => $isFinalized,
                            'scoreBtnBase' => $scoreBtnBase,
                            'buttonSize' => 'min-w-[76px] min-h-[64px] px-6 text-xl',
                            'optionsWrapClass' => 'justify-center',
                        ])
                    </div>

                    {{-- Sebelumnya / Berikutnya — penanda kriteria mana yang masih
                         kosong supaya juri tahu harus kembali ke mana. --}}
                    <div class="px-4 py-3 border-t border-outline-variant/20 flex items-center justify-between gap-3">
                        <button type="button" wire:click="prevCriteria"
                                @disabled($currentCriteriaIndex === 0)
                                class="rounded-xl border border-outline-variant/40 bg-white px-4 py-2.5 text-sm font-bold text-on-surface transition
                                    {{ $currentCriteriaIndex === 0 ? 'opacity-40 cursor-not-allowed' : 'hover:border-primary hover:text-primary active:scale-95' }}">
                            <i class="ti ti-arrow-left"></i> Sebelumnya
                        </button>

                        <button type="button" wire:click="nextCriteria"
                                @disabled($currentCriteriaIndex >= count($flatCriteria) - 1)
                                class="rounded-xl border border-outline-variant/40 bg-white px-4 py-2.5 text-sm font-bold text-on-surface transition
                                    {{ $currentCriteriaIndex >= count($flatCriteria) - 1 ? 'opacity-40 cursor-not-allowed' : 'hover:border-primary hover:text-primary active:scale-95' }}">
                            Berikutnya <i class="ti ti-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- Pengintip progres: satu titik per kriteria, terisi = penuh.
                     Titik kosong bisa diketuk untuk melompat langsung. --}}
                <div class="mt-3 flex flex-wrap items-center justify-center gap-1.5">
                    @foreach($flatCriteria as $i => $c)
                        @php
                            $val = $scores[$c['id']] ?? null;
                            $isFilled = $val !== null && $val !== '';
                        @endphp
                        <button type="button" wire:click="goToCriteria({{ $i }})"
                                title="{{ $c['name'] }}"
                                class="h-7 w-7 rounded-lg border text-[10px] font-bold transition
                                    {{ $i === $currentCriteriaIndex ? 'ring-2 ring-primary ring-offset-1' : '' }}
                                    {{ $isFilled
                                        ? 'bg-primary text-white border-primary'
                                        : 'bg-white text-on-surface-variant border-outline-variant/50 hover:border-primary' }}">
                            {{ $i + 1 }}
                        </button>
                    @endforeach
                </div>
            @else
                {{-- ===== MODE SEMUA KRITERIA =====
                     Kriteria dan tombol nilai berdampingan (label kiri, tombol
                     kanan) supaya satu layar memuat lebih banyak kriteria dan
                     juri tidak perlu menggulir. --}}
                @foreach($categories as $cat)
                    <div class="rounded-2xl border border-outline-variant/30 bg-white shadow-sm mb-4 overflow-hidden">
                        <div class="px-4 py-3 bg-primary/5 border-b border-outline-variant/20">
                            <p class="font-display text-sm font-bold text-on-surface m-0">{{ $cat->name }}</p>
                        </div>

                        @foreach($cat->subCategories as $sub)
                            <div class="px-4 py-4 border-b border-outline-variant/20 last:border-b-0">
                                <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-3">
                                    {{ $sub->name }}
                                </p>

                                @foreach($sub->criterias as $criteria)
                                    <div class="mb-4 last:mb-0 flex flex-wrap items-center gap-x-4 gap-y-2 md:flex-nowrap">
                                        <p class="min-w-[9rem] flex-1 text-sm font-semibold text-on-surface m-0">
                                            {{ $criteria->name }}
                                        </p>

                                        @include('livewire.public.judge-scoring._score-options', [
                                            'criteriaId' => $criteria->id,
                                            'scoreOptions' => $criteria->score_options,
                                            'scores' => $scores,
                                            'isFinalized' => $isFinalized,
                                            'scoreBtnBase' => $scoreBtnBase,
                                            'buttonSize' => 'min-w-[56px] min-h-[48px] px-4 text-base',
                                            'optionsWrapClass' => 'shrink-0',
                                        ])
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @endif

            {{-- Sticky bottom bar --}}
            <div class="sticky bottom-0 -mx-4 px-4 py-3 bg-surface/95 backdrop-blur border-t border-outline-variant/30 mt-6">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs text-on-surface-variant m-0">
                            Terisi <span class="font-bold text-on-surface">{{ $filledCriteria }}/{{ $totalCriteria }}</span>
                        </p>
                        <p class="text-[11px] font-semibold m-0">
                            @if($saveStatus === 'saved')
                                <span class="text-emerald-600"><i class="ti ti-check"></i> Tersimpan</span>
                            @elseif($saveStatus === 'finalized')
                                <span class="text-emerald-600"><i class="ti ti-lock"></i> Terkunci</span>
                            @elseif($saveStatus === 'error')
                                <span class="text-rose-600"><i class="ti ti-alert-triangle"></i> Masih ada kriteria yang kosong</span>
                            @endif
                        </p>
                    </div>

                    @if($isFinalized)
                        <button type="button" wire:click="backToParticipants"
                                class="rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white shadow-sm active:scale-95 transition">
                            Peserta Berikutnya <i class="ti ti-arrow-right"></i>
                        </button>
                    @else
                        <button type="button" wire:click="finalize"
                                wire:confirm="Finalisasi nilai peserta ini? Nilai akan dikunci dan tidak bisa diubah."
                                wire:loading.attr="disabled"
                                class="rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white shadow-sm active:scale-95 transition disabled:opacity-60">
                            <i class="ti ti-lock"></i> Finalisasi &amp; Lanjut
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
