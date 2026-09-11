@php
    // Warna tombol nilai: pakai token tema frontend (primary/surface/outline-variant).
    $scoreBtnBase = 'rounded-xl border font-bold transition select-none';
@endphp

@push('styles')
    <style>[x-cloak] { display: none !important; }</style>
@endpush

<div class="min-h-screen bg-surface" x-data="{
        online: navigator.onLine,
        init() {
            window.addEventListener('online', () => this.online = true);
            window.addEventListener('offline', () => this.online = false);
        }
    }">

    {{-- ========== BANNER KONEKSI PUTUS ========== --}}
    <div x-show="!online" x-cloak
         class="sticky top-0 z-50 bg-amber-500 text-white text-sm font-semibold px-4 py-2.5 text-center">
        <i class="ti ti-wifi-off mr-1"></i>
        Koneksi terputus — nilai belum tersimpan. Tunggu sampai koneksi kembali.
    </div>

    {{-- ========== HEADER ========== --}}
    <div class="container-landing pt-6">
        <div class="rounded-2xl border border-outline-variant/30 bg-white shadow-sm p-4 md:p-5">
            <div class="flex items-center gap-4">
                @if($eventner->logo_event)
                    <img src="{{ asset('storage/' . $eventner->logo_event) }}"
                         class="h-12 w-12 md:h-14 md:w-14 rounded-xl object-cover border border-outline-variant/30 shrink-0"
                         alt="{{ $eventner->nama_event }}">
                @else
                    <div class="flex h-12 w-12 md:h-14 md:w-14 items-center justify-center rounded-xl bg-primary/10 text-primary border border-outline-variant/30 shrink-0">
                        <i class="ti ti-gavel text-2xl"></i>
                    </div>
                @endif

                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-primary mb-0.5">
                        <i class="ti ti-clipboard-check"></i> Penilaian Juri
                    </p>
                    <h1 class="font-display text-base md:text-lg font-bold text-on-surface truncate m-0">
                        {{ $eventner->nama_event }}
                    </h1>
                </div>

                <div class="text-right shrink-0">
                    <p class="text-[10px] uppercase tracking-wider text-on-surface-variant m-0">Juri</p>
                    <p class="text-sm font-bold text-on-surface m-0">{{ $judge->name }}</p>
                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold mt-0.5"
                          :class="online ? 'text-emerald-600' : 'text-amber-600'">
                        <span class="h-1.5 w-1.5 rounded-full" :class="online ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                        <span x-text="online ? 'Online' : 'Offline'">Online</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container-landing py-6">

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
                <button type="button" wire:click="backToParticipants"
                        class="shrink-0 text-xs font-semibold text-primary hover:underline">
                    <i class="ti ti-arrow-left"></i> Daftar peserta
                </button>
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

            @forelse($categories as $cat)
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
                                @php
                                    // Kelompokkan opsi per label — bentuk score_options bisa
                                    // scalar atau {score,label} (sama seperti dashboard panitia).
                                    $groups = [];
                                    foreach ($criteria->score_options as $o) {
                                        $sv = is_array($o) ? $o['score'] : $o;
                                        $lb = is_array($o) ? ($o['label'] ?? null) : null;
                                        $groups[$lb ?: (string) $sv][] = ['score' => $sv, 'label' => $lb];
                                    }
                                    // Judul grup hanya berguna bila opsinya memang berlabel.
                                    $showGroupLabels = collect($groups)->keys()->contains(fn ($k) => !is_numeric($k));
                                @endphp

                                <div class="mb-4 last:mb-0">
                                    <div class="flex items-baseline justify-between gap-2 mb-2">
                                        <p class="text-sm font-semibold text-on-surface m-0">{{ $criteria->name }}</p>
                                        @if(isset($scores[$criteria->id]) && $scores[$criteria->id] !== '' && $scores[$criteria->id] !== null)
                                            <span class="text-[11px] font-bold text-emerald-600 shrink-0">
                                                <i class="ti ti-check"></i> {{ $scores[$criteria->id] }}
                                            </span>
                                        @endif
                                    </div>

                                    @foreach($groups as $label => $opts)
                                        @if($showGroupLabels)
                                            <p class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant mb-1.5">
                                                {{ $label }}
                                            </p>
                                        @endif

                                        <div class="flex flex-wrap gap-2 mb-3 last:mb-0">
                                            @foreach($opts as $opt)
                                                @php $selected = isset($scores[$criteria->id]) && (string) $scores[$criteria->id] === (string) $opt['score']; @endphp
                                                <button type="button"
                                                        wire:click="setScore({{ $criteria->id }}, '{{ $opt['score'] }}')"
                                                        wire:loading.attr="disabled"
                                                        @disabled($isFinalized)
                                                        class="{{ $scoreBtnBase }} min-w-[64px] min-h-[56px] px-5 text-lg
                                                            {{ $selected
                                                                ? 'bg-primary text-white border-primary shadow-sm'
                                                                : 'bg-white text-on-surface border-outline-variant/50 hover:border-primary hover:text-primary' }}
                                                            {{ $isFinalized ? 'opacity-50 cursor-not-allowed' : 'active:scale-95' }}">
                                                    {{ $opt['score'] }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="rounded-2xl border border-outline-variant/30 bg-white p-8 text-center">
                    <p class="text-sm text-on-surface-variant m-0">
                        Belum ada rubrik penilaian untuk tingkat lomba ini. Hubungi panitia.
                    </p>
                </div>
            @endforelse

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
