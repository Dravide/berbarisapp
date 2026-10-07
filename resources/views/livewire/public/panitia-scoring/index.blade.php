{{-- Satu root element. Livewire hanya memorph root, jadi jangan menaruh
     bagian apa pun di luar </div> penutup ini. --}}
<div class="min-h-screen bg-surface">
    <div class="container-landing py-5">

        {{-- ========== 0. GERBANG PIN ========== --}}
        @if(!$terbuka)
            <div class="mx-auto max-w-md">
                <div class="rounded-2xl border border-outline-variant/30 bg-white p-6 shadow-sm text-center">
                    <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                        <i class="ti ti-lock text-2xl"></i>
                    </span>
                    <h1 class="font-display text-lg font-bold text-on-surface m-0">{{ $eventner->nama_event }}</h1>
                    <p class="text-sm text-on-surface-variant mt-1 mb-5">
                        Masukkan PIN entry panitia untuk membuka lembar nilai.
                    </p>

                    <input type="password"
                           wire:model="pinInput"
                           wire:keydown.enter="bukaPin"
                           inputmode="numeric"
                           autocomplete="off"
                           maxlength="6"
                           autofocus
                           placeholder="••••••"
                           class="w-full rounded-xl border border-outline-variant/50 bg-white px-4 py-3 text-center font-display text-2xl font-bold tracking-[0.5em] text-on-surface outline-none focus:border-primary">

                    <button type="button" wire:click="bukaPin" wire:loading.attr="disabled"
                            class="mt-4 w-full rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white shadow-sm transition active:scale-95 disabled:opacity-60">
                        <i class="ti ti-login"></i> Masuk
                    </button>

                    <p class="mt-4 text-[11px] text-on-surface-variant m-0">
                        PIN ini untuk petugas input nilai, bukan kartu juri.
                    </p>
                </div>
            </div>
        @endif

        {{-- ========== 1. PILIH TINGKAT ========== --}}
        @if($terbuka && $view === 'categories')
            <h2 class="font-display text-sm font-bold uppercase tracking-wider text-on-surface-variant mb-3">
                1. Pilih Tingkat Lomba
            </h2>

            @if($categories->isEmpty())
                <div class="rounded-2xl border border-outline-variant/30 bg-white p-8 text-center">
                    <i class="ti ti-info-circle text-3xl text-on-surface-variant"></i>
                    <p class="text-sm text-on-surface-variant mt-2 mb-0">Belum ada tingkat lomba di event ini.</p>
                </div>
            @else
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach($categories as $cat)
                        <button type="button" wire:click="selectCategory({{ $cat->id }})"
                                class="rounded-2xl border border-outline-variant/30 bg-white p-5 text-left shadow-sm transition hover:border-primary hover:shadow-md active:scale-[0.99]">
                            <p class="font-display text-lg font-bold text-on-surface m-0">{{ $cat->name }}</p>
                            <p class="text-xs text-on-surface-variant mt-0.5 mb-2">{{ $cat->parent?->name ?? '—' }}</p>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-xs font-bold text-primary">
                                <i class="ti ti-users"></i> {{ $jumlahPeserta[$cat->id] ?? 0 }} peserta
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif
        @endif

        {{-- ========== 2. PESERTA ========== --}}
        @if($terbuka && $view === 'participants')
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-display text-sm font-bold uppercase tracking-wider text-on-surface-variant m-0">
                    2. Pilih Peserta
                </h2>
                <button type="button" wire:click="backToCategories"
                        class="text-xs font-semibold text-primary hover:underline">
                    <i class="ti ti-arrow-left"></i> Ganti tingkat
                </button>
            </div>

            @if($rounds->count() > 1)
                {{-- Babak menentukan rubrik mana yang dimuat, jadi panitia bisa
                     berpindah penyisihan ↔ final tanpa keluar halaman. --}}
                <div class="rounded-2xl border border-outline-variant/30 bg-white p-3 mb-3">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant m-0 mb-2">Babak</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($rounds as $round)
                            <button type="button" wire:click="switchRound({{ $round->id }})"
                                    class="rounded-xl border px-3 py-2 text-xs font-bold transition
                                        {{ $selectedRoundId == $round->id
                                            ? ($round->isFinal() ? 'border-amber-500 bg-amber-500 text-white' : 'border-primary bg-primary text-white')
                                            : ($round->isFinal() ? 'border-amber-400/60 text-amber-700' : 'border-outline-variant/40 text-on-surface-variant') }}">
                                <i class="ti ti-flag"></i> {{ $round->name }}
                                @if($round->isFinal())
                                    <span class="ml-1 rounded px-1.5 py-0.5 text-[10px] {{ $selectedRoundId == $round->id ? 'bg-white text-amber-700' : 'bg-amber-400 text-amber-900' }}">Final</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @elseif($rounds->count() === 1)
                @php $satu = $rounds->first(); @endphp
                <div class="mb-3 flex items-center gap-2 rounded-2xl border border-outline-variant/30 bg-white p-3">
                    <i class="ti ti-flag {{ $satu->isFinal() ? 'text-amber-600' : 'text-primary' }}"></i>
                    <span class="text-xs font-bold text-on-surface">{{ $satu->name }}</span>
                    @if($satu->isFinal())
                        <span class="ml-1 rounded bg-amber-400 px-1.5 py-0.5 text-[10px] font-bold text-amber-900">Final</span>
                    @endif
                </div>
            @endif

            @if(!$adaJuri)
                {{-- Tanpa juri, lembar nilai tak punya pemilik: assessment_scores
                     unik pada (peserta, kriteria, juri). Lebih baik mengatakannya
                     di sini daripada membiarkan panitia memilih peserta lalu
                     menemukan layar kosong. --}}
                <div class="rounded-2xl border border-amber-300 bg-amber-50 p-5 flex items-start gap-3">
                    <i class="ti ti-alert-triangle text-xl text-amber-600 shrink-0 mt-0.5"></i>
                    <p class="text-sm text-amber-800 m-0">
                        Belum ada juri di event ini. Tambahkan juri lebih dulu di dashboard panitia.
                    </p>
                </div>
            @elseif($participants->isEmpty())
                <div class="rounded-2xl border border-outline-variant/30 bg-white p-8 text-center">
                    <p class="text-sm text-on-surface-variant m-0">Belum ada peserta terdaftar di tingkat ini.</p>
                </div>
            @else
                <div class="overflow-hidden rounded-2xl border border-outline-variant/30 bg-white shadow-sm divide-y divide-outline-variant/20">
                    @foreach($participants as $p)
                        @php
                            $nomor = $p->nomorUndian($selectedRound);
                            $badge = match($p->panitia_status ?? 'belum') {
                                'final'   => ['text' => 'Final',   'class' => 'bg-emerald-100 text-emerald-700 border-emerald-200', 'icon' => 'ti-lock'],
                                'dinilai' => ['text' => 'Dinilai', 'class' => 'bg-amber-100 text-amber-700 border-amber-200',       'icon' => 'ti-progress'],
                                default   => ['text' => 'Belum',   'class' => 'bg-slate-100 text-slate-600 border-slate-200',       'icon' => 'ti-circle-dashed'],
                            };
                        @endphp
                        <button type="button" wire:click="selectParticipant({{ $p->id }})"
                                class="flex w-full items-center gap-4 px-4 py-4 text-left transition hover:bg-primary/5 active:bg-primary/10">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-primary/20 bg-primary/10 font-display text-sm font-bold text-primary">
                                {{ $nomor ?? '–' }}
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2 min-w-0">
                                    <span class="truncate font-semibold text-on-surface">{{ $p->nama_sekolah }}</span>
                                    @if($p->competitionSeries)
                                        <span class="shrink-0 rounded-full border border-primary/20 bg-primary/10 px-2 py-0.5 text-[10px] font-bold text-primary">
                                            <i class="ti ti-flag-2"></i> {{ $p->competitionSeries->name }}
                                        </span>
                                    @endif
                                </span>
                                @if($p->nama_pelatih)
                                    <span class="block truncate text-xs text-on-surface-variant">Pelatih: {{ $p->nama_pelatih }}</span>
                                @endif
                            </span>
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-3 py-1 text-[11px] font-bold {{ $badge['class'] }}">
                                <i class="ti {{ $badge['icon'] }}"></i> {{ $badge['text'] }}
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif
        @endif

        {{-- ========== 3. INPUT NILAI ========== --}}
        @if($terbuka && $view === 'scoring')
            @php
                $registration = $this->registration;
                $totalCriteria = collect($flatCriteria)->count();
                $filledCriteria = collect($scores)->filter(fn ($v) => $v !== '' && $v !== null)->count();
                $scoreBtnBase = 'rounded-xl border font-bold transition select-none';
            @endphp

            <div class="mb-3 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="font-display text-sm font-bold uppercase tracking-wider text-on-surface-variant m-0">
                        3. Input Nilai
                    </h2>
                    <p class="mt-0.5 mb-0 truncate text-sm font-bold text-on-surface">
                        @if($registration->nomorUndian($selectedRound)) No. {{ $registration->nomorUndian($selectedRound) }} · @endif
                        {{ $registration->nama_sekolah }}
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-3">
                    <div class="inline-flex rounded-xl border border-outline-variant/40 bg-white p-0.5">
                        <button type="button" wire:click="setCriteriaMode('semua')"
                                class="rounded-[10px] px-3 py-1.5 text-[11px] font-bold transition
                                    {{ $criteriaMode === 'semua' ? 'bg-primary text-white' : 'text-on-surface-variant hover:text-primary' }}">
                            <i class="ti ti-list"></i> Semua
                        </button>
                        <button type="button" wire:click="setCriteriaMode('satu-satu')"
                                class="rounded-[10px] px-3 py-1.5 text-[11px] font-bold transition
                                    {{ $criteriaMode === 'satu-satu' ? 'bg-primary text-white' : 'text-on-surface-variant hover:text-primary' }}">
                            <i class="ti ti-square-check"></i> Satu per Satu
                        </button>
                    </div>

                    <button type="button" wire:click="backToParticipants"
                            class="text-xs font-semibold text-primary hover:underline">
                        <i class="ti ti-arrow-left"></i> Daftar peserta
                    </button>
                </div>
            </div>

            {{-- Pemilih juri. Nilai disimpan ATAS NAMA juri ini — halaman tidak
                 bisa menyimpulkan sendiri siapa pemilik nilainya. --}}
            @if($judges->isNotEmpty())
                <div class="mb-3 rounded-2xl border border-outline-variant/30 bg-white p-3">
                    <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Lembar nilai milik juri</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($judges as $judge)
                            <button type="button" wire:click="selectJudge({{ $judge->id }})"
                                    wire:loading.attr="disabled"
                                    class="rounded-xl border px-4 py-2 text-sm font-bold transition
                                        {{ (int) $selectedJudgeId === (int) $judge->id
                                            ? 'border-primary bg-primary text-white shadow-sm'
                                            : 'border-outline-variant/40 bg-white text-on-surface-variant hover:border-primary hover:text-primary' }}">
                                <i class="ti ti-user"></i> {{ $judge->name }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($isFinalized)
                <div class="mb-4 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <i class="ti ti-lock mt-0.5 shrink-0 text-xl text-emerald-600"></i>
                    <div>
                        <p class="m-0 text-sm font-bold text-emerald-800">Nilai terkunci</p>
                        <p class="m-0 mt-0.5 text-xs text-emerald-700">
                            Lembar juri ini sudah difinalisasi. Buka kunci dari dashboard panitia bila perlu koreksi.
                        </p>
                    </div>
                </div>
            @endif

            @if($flatCriteria === [])
                <div class="rounded-2xl border border-outline-variant/30 bg-white p-8 text-center">
                    <p class="m-0 text-sm text-on-surface-variant">
                        Tidak ada rubrik yang bisa dinilai juri ini untuk peserta ini.
                    </p>
                </div>
            @elseif($criteriaMode === 'satu-satu')
                @php $current = $currentCriteria; @endphp
                <div class="overflow-hidden rounded-2xl border border-outline-variant/30 bg-white shadow-sm">
                    <div class="flex items-center justify-between gap-3 border-b border-outline-variant/20 bg-primary/5 px-4 py-3">
                        <p class="m-0 truncate text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                            {{ $current['category'] }} · {{ $current['sub'] }}
                        </p>
                        <span class="shrink-0 rounded-full border border-outline-variant/40 bg-white px-3 py-1 text-[11px] font-bold text-on-surface">
                            {{ $currentCriteriaIndex + 1 }} / {{ $totalCriteria }}
                        </span>
                    </div>

                    <div class="px-4 py-6">
                        <p class="mb-6 mt-0 text-center font-display text-xl font-bold text-on-surface">
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

                        @php $currentFilled = isset($scores[$current['id']]) && $scores[$current['id']] !== '' && $scores[$current['id']] !== null; @endphp
                        @if($currentFilled && !$isFinalized)
                            <div class="mt-5 flex justify-center">
                                <button type="button" wire:click="clearScore({{ $current['id'] }})" wire:loading.attr="disabled"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-rose-300 bg-rose-50 px-4 py-2.5 text-sm font-bold text-rose-600 transition active:scale-95">
                                    <i class="ti ti-x"></i> Hapus Nilai
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-between gap-3 border-t border-outline-variant/20 px-4 py-3">
                        <button type="button" wire:click="prevCriteria"
                                @disabled($currentCriteriaIndex === 0)
                                class="rounded-xl border border-outline-variant/40 bg-white px-4 py-2.5 text-sm font-bold text-on-surface transition
                                    {{ $currentCriteriaIndex === 0 ? 'cursor-not-allowed opacity-40' : 'hover:border-primary hover:text-primary active:scale-95' }}">
                            <i class="ti ti-arrow-left"></i> Sebelumnya
                        </button>

                        <button type="button" wire:click="nextCriteria"
                                @disabled($currentCriteriaIndex >= $totalCriteria - 1)
                                class="rounded-xl border border-outline-variant/40 bg-white px-4 py-2.5 text-sm font-bold text-on-surface transition
                                    {{ $currentCriteriaIndex >= $totalCriteria - 1 ? 'cursor-not-allowed opacity-40' : 'hover:border-primary hover:text-primary active:scale-95' }}">
                            Berikutnya <i class="ti ti-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-center gap-1.5">
                    @foreach($flatCriteria as $i => $c)
                        @php
                            $val = $scores[$c['id']] ?? null;
                            $isFilled = $val !== null && $val !== '';
                        @endphp
                        <button type="button" wire:click="goToCriteria({{ $i }})" title="{{ $c['name'] }}"
                                class="h-7 w-7 rounded-lg border text-[10px] font-bold transition
                                    {{ $i === $currentCriteriaIndex ? 'ring-2 ring-primary ring-offset-1' : '' }}
                                    {{ $isFilled ? 'border-primary bg-primary text-white' : 'border-outline-variant/50 bg-white text-on-surface-variant hover:border-primary' }}">
                            {{ $i + 1 }}
                        </button>
                    @endforeach
                </div>
            @else
                @foreach($assessmentCategories as $cat)
                    <div class="mb-4 overflow-hidden rounded-2xl border border-outline-variant/30 bg-white shadow-sm">
                        <div class="border-b border-outline-variant/20 bg-primary/5 px-4 py-3">
                            <p class="m-0 font-display text-sm font-bold text-on-surface">{{ $cat->name }}</p>
                        </div>

                        @foreach($cat->subCategories as $sub)
                            <div class="border-b border-outline-variant/20 px-4 py-4 last:border-b-0">
                                <p class="mb-3 text-xs font-bold uppercase tracking-wider text-on-surface-variant">{{ $sub->name }}</p>

                                @foreach($sub->criterias as $criteria)
                                    <div class="mb-4 flex flex-wrap items-center gap-x-4 gap-y-2 last:mb-0 md:flex-nowrap">
                                        <p class="m-0 min-w-[9rem] flex-1 text-sm font-semibold text-on-surface">{{ $criteria->name }}</p>

                                        @include('livewire.public.judge-scoring._score-options', [
                                            'criteriaId' => $criteria->id,
                                            'scoreOptions' => $criteria->score_options,
                                            'scores' => $scores,
                                            'isFinalized' => $isFinalized,
                                            'scoreBtnBase' => $scoreBtnBase,
                                            'buttonSize' => 'min-w-[56px] min-h-[48px] px-4 text-base',
                                            'optionsWrapClass' => 'shrink-0',
                                        ])

                                        @php $critFilled = isset($scores[$criteria->id]) && $scores[$criteria->id] !== '' && $scores[$criteria->id] !== null; @endphp
                                        @if($critFilled && !$isFinalized)
                                            <button type="button" wire:click="clearScore({{ $criteria->id }})" wire:loading.attr="disabled"
                                                    class="h-9 w-9 shrink-0 rounded-xl border border-rose-300 bg-rose-50 text-rose-600 transition active:scale-95"
                                                    title="Hapus nilai kriteria ini">
                                                <i class="ti ti-x"></i>
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @endif

            {{-- Bar bawah: progres + finalisasi. Nilai tersimpan tiap ketukan,
                 jadi tombol ini hanya mengunci — bukan menyimpan. --}}
            <div class="sticky bottom-0 -mx-4 mt-6 border-t border-outline-variant/30 bg-surface/95 px-4 py-3 backdrop-blur">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="m-0 text-xs text-on-surface-variant">
                            Terisi <span class="font-bold text-on-surface">{{ $filledCriteria }}/{{ $totalCriteria }}</span>
                        </p>
                        <p class="m-0 text-[11px] font-semibold">
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
                                class="rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white shadow-sm transition active:scale-95">
                            Peserta Berikutnya <i class="ti ti-arrow-right"></i>
                        </button>
                    @else
                        <button type="button" wire:click="finalize"
                                wire:confirm="Finalisasi nilai peserta ini? Nilai akan dikunci dan tidak bisa diubah."
                                wire:loading.attr="disabled"
                                class="rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white shadow-sm transition active:scale-95 disabled:opacity-60">
                            <i class="ti ti-lock"></i> Finalisasi &amp; Lanjut
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
