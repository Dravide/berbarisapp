<div class="min-h-screen bg-surface">
    @push('styles')
        <link href="https://unpkg.com/filepond/dist/filepond.css" rel="stylesheet">
        <link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css" rel="stylesheet">
        <style>
            .filepond--root { font-family: 'Inter', sans-serif; margin-bottom: 0; }
            .filepond--panel-root { border-radius: 12px; background-color: #fff; border: 1px solid var(--color-outline-variant); }
            .filepond--drop-label { color: var(--color-on-surface-variant); }
        </style>
    @endpush

    {{-- ========== BANNER (JIKA ADA DARI PENYELENGGARA) ========== --}}
    @if($registration->eventner->header_banner)
        <div class="container-landing pt-6">
            <div class="max-w-4xl mx-auto">
                <div class="relative w-full aspect-[21/9] md:aspect-[3/1] rounded-2xl overflow-hidden shadow-xs border border-outline-variant/30 bg-black/5">
                    <img src="{{ asset('storage/' . $registration->eventner->header_banner) }}" alt="Banner {{ $registration->eventner->nama_event }}" class="w-full h-full object-cover">
                </div>
            </div>
        </div>
    @endif

    {{-- ========== HEADER PORTAL KONTINGEN ========== --}}
    <div class="container-landing pt-6">
        <div class="max-w-4xl mx-auto">
            <div class="surface-card p-5 md:p-6">
                {{-- Baris 1: Identitas & Status --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-outline-variant/30">
                    <div class="flex items-center gap-3.5 min-w-0">
                        @if($registration->logo_sekolah)
                            <img src="{{ asset('storage/' . $registration->logo_sekolah) }}" alt="{{ $registration->nama_sekolah }}" class="h-12 w-12 rounded-xl object-cover border border-outline-variant/30 shrink-0 shadow-xs">
                        @elseif($registration->eventner->logo_event)
                            <img src="{{ asset('storage/' . $registration->eventner->logo_event) }}" alt="{{ $registration->eventner->nama_event }}" class="h-12 w-12 rounded-xl object-cover border border-outline-variant/30 shrink-0 shadow-xs">
                        @else
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary border border-primary/20 shrink-0">
                                <i class="ti ti-school text-2xl"></i>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-primary block">Portal Kontingen</span>
                            <h1 class="font-display text-lg sm:text-xl font-bold text-deep-slate m-0 truncate leading-tight">
                                {{ $registration->display_name }}
                            </h1>
                            <p class="text-xs text-on-surface-variant font-medium m-0 mt-0.5">
                                Event: <strong class="text-deep-slate">{{ $registration->eventner->nama_event }}</strong>
                                &middot; {{ $registration->competitionCategory->full_name }}
                            </p>
                        </div>
                    </div>

                    {{-- Status Badge --}}
                    @php
                        $isHardcopy = $registration->verification_method === 'hardcopy';
                        $statusConfig = [
                            'booking' => ['bg-amber-500/10 text-amber-700 border-amber-500/30', 'ti-clock', 'Booking Slot'],
                            'confirmed' => ['bg-blue-500/10 text-blue-700 border-blue-500/30', 'ti-send', 'Menunggu Verifikasi'],
                            'Terverifikasi' => [
                                'bg-emerald-500/10 text-emerald-700 border-emerald-500/30',
                                $isHardcopy ? 'ti-shield-check' : 'ti-circle-check',
                                $isHardcopy ? 'Terverifikasi (Hardcopy)' : 'Terverifikasi'
                            ],
                            'Ditolak' => ['bg-red-500/10 text-red-700 border-red-500/30', 'ti-alert-triangle', 'Perlu Revisi'],
                        ];
                        $sc = $statusConfig[$registration->status_berkas] ?? $statusConfig['booking'];
                    @endphp
                    <div class="shrink-0 self-start sm:self-center">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border {{ $sc[0] }}">
                            <i class="ti {{ $sc[1] }} text-sm"></i>
                            <span>{{ $sc[2] }}</span>
                        </span>
                    </div>
                </div>

                {{-- Baris 2: Link Akses & NPSN --}}
                <div class="pt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-2 min-w-0 flex-1 bg-surface-container-low px-3 py-2 rounded-xl border border-outline-variant/30">
                        <i class="ti ti-link text-primary shrink-0"></i>
                        <span class="text-[11px] font-bold text-on-surface-variant shrink-0">Link Portal:</span>
                        <input type="text" value="{{ $portalUrl }}" readonly class="w-full bg-transparent border-0 text-xs font-mono text-deep-slate outline-none truncate" onclick="this.select()">
                        <div x-data="{ copied: false }" class="shrink-0">
                            <button type="button" x-on:click="navigator.clipboard.writeText('{{ $portalUrl }}').then(() => { copied = true; setTimeout(() => copied = false, 2000); })" class="btn-ghost py-1 px-2.5 text-[11px] font-bold leading-normal inline-flex items-center gap-1 cursor-pointer">
                                <i class="ti" :class="copied ? 'ti-check' : 'ti-copy'"></i>
                                <span x-text="copied ? 'Tersalin' : 'Salin'">Salin</span>
                            </button>
                        </div>
                    </div>

                    @if($registration->npsn)
                        <div class="shrink-0 text-on-surface-variant font-medium text-xs sm:text-right">
                            NPSN: <strong class="font-mono text-deep-slate">{{ $registration->npsn }}</strong>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ========== MAIN CONTENT PORTAL ========== --}}
    <div class="container-landing py-6">
        <div class="max-w-4xl mx-auto flex flex-col gap-5">

            {{-- ========== PEMILIH PASUKAN ==========
                 Satu magic_token dipakai bersama semua pasukan sekolah ini.
                 Pemilihnya duduk paling atas — sebelum kartu apa pun yang isinya
                 bergantung pada pasukan yang sedang aktif — supaya pendaftar tahu
                 ada pasukan lain sebelum membaca rekap nilai atau unduhan. --}}
            @php
                $titikStatus = [
                    'booking' => 'bg-amber-500',
                    'confirmed' => 'bg-blue-500',
                    'Terverifikasi' => 'bg-emerald-500',
                    'Ditolak' => 'bg-red-500',
                ];
            @endphp

            @if($siblingRegistrations->count() > 1)
                <div class="surface-card p-4 sm:p-5">
                    <div class="flex items-baseline justify-between gap-3 mb-3">
                        <h2 class="font-display text-sm font-bold text-deep-slate m-0">Pilih Pasukan</h2>
                        <span class="text-[11px] font-semibold text-on-surface-variant">{{ $siblingRegistrations->count() }} pasukan &middot; satu tautan portal</span>
                    </div>
                    <div class="flex gap-2.5 overflow-x-auto pb-1" role="tablist" aria-label="Pilih pasukan">
                        @foreach($siblingRegistrations as $sib)
                            @php
                                $tabAktif = $activeRegId == $sib->id;
                                // Judul tab dibaca dari label_pasukan ("A", "B", …) —
                                // nama mata lomba tidak bisa membedakan dua pasukan
                                // sekolah yang sama di kategori yang sama.
                                $tabLabel = trim((string) $sib->label_pasukan) !== ''
                                    ? 'Pasukan ' . $sib->label_pasukan
                                    : $sib->competitionCategory->full_name;
                            @endphp
                            <button type="button"
                                wire:key="tab-pasukan-{{ $sib->id }}"
                                wire:click="switchRegistration({{ $sib->id }})"
                                role="tab"
                                aria-selected="{{ $tabAktif ? 'true' : 'false' }}"
                                title="{{ $sib->display_name }} — {{ $statusConfig[$sib->status_berkas][2] ?? $sib->status_berkas }}"
                                class="flex-1 sm:flex-none min-w-[9.5rem] sm:min-w-[10.5rem] text-left rounded-xl px-3.5 py-2.5 border transition cursor-pointer {{ $tabAktif ? 'border-primary bg-primary/5 shadow-[inset_3px_0_0_var(--color-primary)]' : 'border-outline-variant/60 bg-surface-container-lowest hover:border-primary/40' }}">
                                <span class="flex items-center gap-1.5 min-w-0">
                                    <span class="h-1.5 w-1.5 rounded-full shrink-0 {{ $titikStatus[$sib->status_berkas] ?? 'bg-outline' }}"></span>
                                    <span class="text-xs font-bold truncate {{ $tabAktif ? 'text-primary' : 'text-deep-slate' }}">{{ $tabLabel }}</span>
                                    @if($sib->participants->count() > 0)
                                        <span class="ml-auto shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-bold {{ $tabAktif ? 'bg-primary text-white' : 'bg-surface-container text-on-surface-variant' }}">{{ $sib->participants->count() }}</span>
                                    @endif
                                </span>
                                <span class="mt-0.5 block text-[11px] font-medium text-on-surface-variant truncate">{{ $sib->competitionCategory->full_name }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="surface-card px-4 sm:px-5 py-3.5 flex flex-wrap items-center gap-x-2 gap-y-1">
                    <i class="ti ti-flag-2 text-primary"></i>
                    <span class="text-xs font-bold text-deep-slate">1 pasukan</span>
                    <span class="text-xs font-medium text-on-surface-variant">{{ $registration->competitionCategory->full_name }} — sekolah ini hanya mendaftarkan satu pasukan.</span>
                </div>
            @endif

            {{-- ========== DOKUMEN & UNDUHAN ==========
                 Tiga berkas yang dulu tersebar di kartu sertifikat, kotak
                 pembayaran, dan tabel data terverifikasi kini satu kartu.
                 Baris yang belum bisa dipakai tetap tampil beserta alasannya —
                 kalimatnya sama dengan pesan abort(403) di controller, jadi yang
                 dibaca pendaftar memang syarat yang ditegakkan server. --}}
            <div class="surface-card overflow-hidden">
                <div class="flex items-center justify-between gap-3 px-5 py-3.5 border-b border-outline-variant/40 bg-surface-container-low">
                    <h2 class="font-display text-sm font-bold text-deep-slate inline-flex items-center gap-2 m-0">
                        <i class="ti ti-folder-open text-primary"></i> Dokumen &amp; Unduhan
                    </h2>
                    <span class="text-[11px] font-semibold text-on-surface-variant hidden sm:inline">Berkas dibuat saat tombol ditekan</span>
                </div>
                <div class="divide-y divide-outline-variant/30">
                    @foreach($this->unduhan as $d)
                        <div wire:key="unduhan-{{ $d['key'] }}"
                             class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-4 {{ $d['terbuka'] ? '' : 'bg-surface-container-low' }}">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl shrink-0 {{ $d['aksen'] === 'champion' ? 'bg-amber-500/10 text-amber-600' : ($d['terbuka'] ? 'bg-primary/10 text-primary' : 'bg-surface-container text-on-surface-variant') }}">
                                <i class="ti {{ $d['ikon'] }} text-lg"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="text-sm font-bold {{ $d['terbuka'] ? 'text-deep-slate' : 'text-on-surface-variant' }}">{{ $d['judul'] }}</span>
                                    <span class="rounded-md border border-outline-variant/40 bg-surface-container px-2 py-0.5 text-[10px] font-bold text-on-surface-variant">{{ $d['cakupan'] }}</span>
                                    @if($d['gelar'])
                                        <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-bold {{ $d['aksen'] === 'champion' ? 'bg-amber-500/10 text-amber-700' : 'bg-surface-container text-on-surface-variant' }}">
                                            <i class="ti {{ $d['aksen'] === 'champion' ? 'ti-trophy' : 'ti-user-check' }}"></i> {{ $d['gelar'] }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs font-medium text-on-surface-variant m-0 mt-1 leading-normal">{{ $d['keterangan'] }}</p>
                            </div>
                            <div class="shrink-0 w-full sm:w-auto">
                                @if($d['terbuka'])
                                    @php $gayaUtama = $d['aksen'] === 'champion' || $d['key'] === 'formulir'; @endphp
                                    <a href="{{ $d['url'] }}" target="_blank"
                                       class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl font-bold text-xs transition-colors text-decoration-none {{ $d['aksen'] === 'champion' ? 'bg-amber-500 text-white hover:brightness-105' : ($gayaUtama ? 'btn-primary cursor-pointer' : 'btn-ghost') }}">
                                        <i class="ti ti-file-download"></i> {{ $d['tombol'] }}
                                    </a>
                                @else
                                    <span class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl border border-outline-variant/50 bg-surface-container text-on-surface-variant font-bold text-xs cursor-not-allowed">
                                        <i class="ti ti-lock"></i> {{ $d['berlaku'] ? 'Terkunci' : 'Tidak berlaku' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Flash Messages --}}
            @if(session()->has('success'))
                <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 text-xs font-semibold flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-circle-check text-base"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session()->has('error'))
                <div class="p-3.5 rounded-xl bg-red-500/10 border border-red-500/20 text-red-600 text-xs font-semibold flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-alert-circle text-base"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            {{-- Booking mode banner --}}
            @if(($registration->eventner->registration_status ?? 'open') == 'booking')
                <div class="p-3.5 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-900 text-xs font-medium flex items-center gap-2">
                    <i class="ti ti-info-circle text-base text-blue-600 shrink-0"></i>
                    <span>Mode Booking Aktif: slot telah dipesan. Pengisian dan verifikasi lengkap data pasukan dibuka oleh panitia setelah masa booking selesai.</span>
                </div>
            @endif

            {{-- Verified Only: Score & Vote Recap --}}
            @if($registration->status_berkas === 'Terverifikasi')
                <div class="grid gap-6 md:grid-cols-2">
                    {{-- Score Recap Card --}}
                    @if($this->is_scoring_finalized)
                        <div class="surface-card p-6 flex flex-col h-full">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                                    <i class="ti ti-trophy text-xl"></i>
                                </div>
                                <h3 class="font-display text-sm font-bold text-deep-slate mb-0">Rekap Hasil Nilai</h3>
                            </div>
                            <p class="text-xs text-on-surface-variant font-medium mb-4 leading-normal">Penilaian juri telah selesai dan difinalisasi.</p>

                            @php
                                $finalScores = $this->finalScores;
                                $categories = $this->scoreCategories;
                                $judges = $this->scoreJudges;
                                $deductions = $this->score_deductions;

                                // Hitung terbobot (skor × bobot kriteria) — sama
                                // seperti halaman hasil resmi dan perhitungan juara.
                                $scoreTable = [];
                                foreach($finalScores as $score) {
                                    $crit = $score->assessmentCriteria;
                                    if (!$crit) continue;
                                    $jid = $score->judge_id;
                                    $cid = $crit->subCategory?->assessment_category_id;
                                    if (!$cid) continue;
                                    $weighted = \App\Support\ScoreOptions::value($score->score) * (float) ($crit->weight ?? 1);
                                    $scoreTable[$jid][$cid] = ($scoreTable[$jid][$cid] ?? 0) + $weighted;
                                }

                                $totalAllJudges = 0;
                            @endphp

                            <div class="bg-surface-container-low border border-outline-variant/40 rounded-xl p-4 overflow-x-auto">
                                @foreach($judges as $judge)
                                    <div class="mb-4 pb-3 border-b border-dashed border-outline-variant/60 last:border-b-0 last:pb-0 last:mb-0">
                                        <div class="font-bold text-xs text-deep-slate mb-2 inline-flex items-center gap-1.5">
                                            <i class="ti ti-user-star text-on-surface-variant"></i> {{ $judge->name }}
                                        </div>
                                        @php $jTotal = 0; @endphp
                                        @foreach($categories as $cat)
                                            @php
                                                $val = $scoreTable[$judge->id][$cat->id] ?? null;
                                                if ($val !== null) $jTotal += $val;
                                            @endphp
                                            @if($val !== null)
                                                <div class="flex justify-between text-xs font-semibold text-on-surface-variant mb-1 ml-5">
                                                    <span>{{ $cat->name }}</span>
                                                    <span class="text-deep-slate">{{ \App\Support\ScoreOptions::format($val) }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                        <div class="flex justify-between text-xs font-bold text-deep-slate ml-5 mt-2 border-t border-outline-variant/30 pt-1.5">
                                            <span>Subtotal Juri</span>
                                            <span class="text-primary">{{ \App\Support\ScoreOptions::format($jTotal) }}</span>
                                        </div>
                                        @php $totalAllJudges += $jTotal; @endphp
                                    </div>
                                @endforeach
                                <div class="mt-4 p-3 bg-primary text-white rounded-lg flex justify-between items-center">
                                    <span class="font-bold text-xs">TOTAL KESELURUHAN</span>
                                    <span class="font-extrabold text-sm">{{ \App\Support\ScoreOptions::format($totalAllJudges) }}</span>
                                </div>
                                @php $totalDeduction = (float) $deductions->sum(fn ($d) => $d->magnitude); @endphp
                                @if($deductions->isNotEmpty())
                                    @foreach($deductions as $deduction)
                                        <div class="flex justify-between text-xs font-semibold text-red-600 mb-1 ml-5 mt-2">
                                            <span>
                                                Pengurangan {{ $deduction->label }}
                                                @if($deduction->assessmentCategory)
                                                    <span class="text-[10px] text-primary">({{ $deduction->assessmentCategory->name }})</span>
                                                @endif
                                            </span>
                                            <span>-{{ \App\Support\ScoreOptions::format($deduction->magnitude) }}</span>
                                        </div>
                                    @endforeach
                                    <div class="flex justify-between text-xs font-bold text-deep-slate ml-5 mt-2 border-t border-outline-variant/30 pt-1.5">
                                        <span>Total Setelah Pengurangan</span>
                                        <span class="text-primary">{{ \App\Support\ScoreOptions::format($totalAllJudges - $totalDeduction) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Vote Recap Card --}}
                    @if($registration->eventner->vote_active)
                        <div class="surface-card p-6 flex flex-col h-full">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 shrink-0">
                                    <i class="ti ti-heart text-xl"></i>
                                </div>
                                <h3 class="font-display text-sm font-bold text-deep-slate mb-0">Rekap Jumlah Vote</h3>
                            </div>
                            <p class="text-xs text-on-surface-variant font-medium mb-4 leading-normal">Total dukungan (vote online) yang berhasil dikumpulkan.</p>
                            <div class="bg-amber-500/5 border border-dashed border-amber-500/30 rounded-xl p-5 text-center my-auto">
                                <h2 class="text-4xl font-extrabold text-amber-500 m-0">{{ number_format($this->vote_total, 0, ',', '.') }}</h2>
                                <span class="text-[10px] text-amber-600 font-bold uppercase tracking-wider block mt-1">Total Vote</span>
                            </div>
                            {{-- Komentar pendukung --}}
                            @if($this->vote_comment_count > 0)
                                <div class="mt-5 pt-5 border-t border-outline-variant/40">
                                    <h4 class="font-display text-xs font-bold text-deep-slate mb-3 inline-flex items-center gap-1.5">
                                        <i class="ti ti-message-circle text-amber-500"></i>
                                        Komentar Pendukung
                                        <span class="inline-block py-0.5 px-1.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-700">{{ number_format($this->vote_comment_count, 0, ',', '.') }}</span>
                                    </h4>
                                    <div class="flex flex-col gap-2.5">
                                        @foreach($this->vote_comments as $vc)
                                            <div class="rounded-xl bg-surface-container-lowest border border-outline-variant/30 p-3">
                                                <div class="flex items-center justify-between gap-2 mb-1">
                                                    <span class="text-xs font-bold text-deep-slate truncate">{{ $vc->voter_name ?: 'Anonim' }}</span>
                                                    @if($vc->votes_earned > 0)
                                                        <span class="text-[10px] font-bold text-amber-600 shrink-0 inline-flex items-center gap-0.5">
                                                            <i class="ti ti-heart-filled"></i> {{ number_format($vc->votes_earned, 0, ',', '.') }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-on-surface-variant leading-normal m-0 break-words">{{ $vc->comment }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                    @if($this->vote_comment_count > $this->vote_comments->count())
                                        <p class="mt-3 mb-0 text-[10px] text-on-surface-variant text-center">
                                            Menampilkan {{ $this->vote_comments->count() }} dari {{ number_format($this->vote_comment_count, 0, ',', '.') }} komentar.
                                        </p>
                                    @endif
                                </div>
                            @endif

                            <div class="mt-6 text-center shrink-0">
                                <a href="{{ event_url($registration->eventner, 'vote') }}" class="text-xs font-bold text-primary hover:underline inline-flex items-center gap-1 text-decoration-none">
                                    Lihat Klasemen Vote <i class="ti ti-arrow-right text-[10px]"></i>
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ========== EVALUASI & CATATAN SUARA DEWAN JURI ========== --}}
            @if($this->judgeNotes->isNotEmpty())
                <div class="surface-card p-6">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                                <i class="ti ti-microphone text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-display text-sm font-bold text-deep-slate mb-0.5">Evaluasi &amp; Catatan Suara Dewan Juri</h3>
                                <p class="text-xs text-on-surface-variant font-medium m-0">
                                    Rekaman evaluasi suara dan catatan langsung dari dewan juri khusus untuk kontingen Anda.
                                </p>
                            </div>
                        </div>
                        <span class="chip bg-primary/10 text-primary py-0.5 px-2.5 text-xs font-bold leading-normal">
                            {{ $this->judgeNotes->count() }} Catatan
                        </span>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach($this->judgeNotes as $note)
                            <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-low p-4 flex flex-col justify-between"
                                 @if($note->audio_url)
                                     x-data="noteAudioPlayer('{{ $note->audio_url }}', {{ (int) $note->duration_seconds }})"
                                 @endif>

                                <div>
                                    {{-- Header Juri --}}
                                    <div class="flex items-center gap-3 mb-3">
                                        @if($note->judge?->photo)
                                            <img src="{{ asset('storage/' . $note->judge->photo) }}" alt="{{ $note->judge->name }}"
                                                 class="h-10 w-10 rounded-full object-cover border border-outline-variant/30 shadow-sm shrink-0">
                                        @else
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary border border-primary/20 shrink-0">
                                                <i class="ti ti-user text-lg"></i>
                                            </div>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-xs font-bold text-deep-slate leading-tight m-0 truncate">
                                                {{ $note->judge?->name ?? 'Dewan Juri' }}
                                            </h4>
                                            <p class="text-[10px] text-on-surface-variant m-0 mt-0.5">
                                                {{ $note->competitionRound ? 'Babak ' . $note->competitionRound->name : 'Dewan Juri Penilai' }}
                                            </p>
                                        </div>
                                    </div>

                                    {{-- Voice Note Player --}}
                                    @if($note->audio_url)
                                        <div class="bg-white rounded-xl border border-outline-variant/30 p-2.5 mb-3 flex items-center gap-3 shadow-xs">
                                            <button type="button" @click="togglePlay()"
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary text-white shadow-xs hover:brightness-110 active:scale-95 transition cursor-pointer">
                                                <i class="ti text-base" :class="isPlaying ? 'ti-player-pause' : 'ti-player-play'"></i>
                                            </button>

                                            <div class="flex-1 min-w-0">
                                                <div class="w-full bg-slate-100 rounded-full h-1.5 cursor-pointer relative overflow-hidden"
                                                     @click="seek($event)">
                                                    <div class="bg-primary h-1.5 rounded-full transition-all duration-100"
                                                         :style="`width: ${progress}%`"></div>
                                                </div>
                                                <div class="flex justify-between items-center mt-1 text-[10px] text-on-surface-variant font-mono">
                                                    <span x-text="formatTime(currentTime)">00:00</span>
                                                    <span>{{ $note->formatted_duration }}</span>
                                                </div>
                                            </div>

                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-primary bg-primary/5 px-2 py-1 rounded-md shrink-0">
                                                <i class="ti ti-volume"></i> Audio
                                            </span>
                                        </div>
                                    @endif

                                    {{-- Catatan Teks (jika ada) --}}
                                    @if($note->notes)
                                        <div class="bg-white/80 rounded-xl border border-outline-variant/20 p-3 text-xs text-deep-slate italic leading-relaxed">
                                            &ldquo;{{ $note->notes }}&rdquo;
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Link Rincian Nilai Resmi --}}
            @if($registration->eventner && $registration->is_finalized)
                <div class="surface-card p-5 border-t-4 border-t-primary">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                                <i class="ti ti-chart-bar text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-display text-sm font-bold text-deep-slate mb-0.5">Rincian Nilai Resmi</h3>
                                <p class="text-xs text-on-surface-variant font-medium leading-normal m-0">
                                    Lihat rekapitulasi poin dan rincian kriteria nilai dewan juri di halaman hasil resmi.
                                </p>
                            </div>
                        </div>
                        <a href="{{ event_url($registration->eventner, 'results') . '/' . $registration->id }}"
                           target="_blank"
                           class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-primary text-white font-bold text-xs hover:brightness-105 transition-colors shrink-0 text-decoration-none">
                            <span>Buka Rincian Nilai</span>
                            <i class="ti ti-external-link"></i>
                        </a>
                    </div>
                </div>
            @endif

            {{-- Active Category Header --}}
            <div class="surface-card overflow-hidden">
                <div class="bg-primary/5 px-5 py-3 border-b border-outline-variant/40">
                    <h3 class="font-display text-sm font-bold text-primary inline-flex items-center gap-1.5 mb-0">
                        <i class="ti ti-medal"></i> {{ $registration->competitionCategory->full_name }}
                    </h3>
                </div>
                <div class="p-4 px-5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded-md bg-surface-container px-2.5 py-1 text-xs font-semibold text-deep-slate border border-outline-variant/30">
                            <i class="ti ti-school text-on-surface-variant"></i> {{ $registration->display_name }}
                        </span>
                        @if($registration->nama_pelatih)
                            <span class="inline-flex items-center gap-1 rounded-md bg-surface-container px-2.5 py-1 text-xs font-semibold text-deep-slate border border-outline-variant/30">
                                <i class="ti ti-user text-on-surface-variant"></i> {{ $this->labelSumber('nama_pelatih') ?? 'Pelatih' }}: {{ $registration->nama_pelatih }}
                            </span>
                        @endif
                        @if($registration->participants->count() > 0)
                            <span class="inline-flex items-center gap-1 rounded-md bg-surface-container px-2.5 py-1 text-xs font-semibold text-deep-slate border border-outline-variant/30">
                                <i class="ti ti-users-group text-on-surface-variant"></i> {{ $registration->participants->count() }} Anggota
                            </span>
                        @endif
                        @if($registration->total_fee && $registration->payment_status !== 'free')
                            <span class="inline-flex items-center gap-1 rounded-md bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-600 border border-emerald-500/20">
                                Rp {{ number_format($registration->total_fee, 0, ',', '.') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Payment Section --}}
            @if($registration->total_fee > 0 || $registration->payment_status === 'unpaid' || $registration->payment_status === 'pending_verification')
                <div class="surface-card overflow-hidden border-t-4 border-t-emerald-500">
                    <div class="bg-surface-container px-5 py-4 border-b border-outline-variant/40">
                        <h3 class="font-display text-sm font-bold text-deep-slate inline-flex items-center gap-2 mb-0">
                            <i class="ti ti-wallet text-emerald-500"></i> Pembayaran Biaya Pendaftaran
                        </h3>
                    </div>
                    <div class="p-5">
                        @php
                            $bankAccounts = $registration->eventner->activeBankAccounts;
                        @endphp

                        @if($registration->payment_status === 'paid')
                            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 text-sm font-semibold flex items-center gap-2">
                                <i class="ti ti-circle-check-filled"></i>
                                <div>
                                    Pembayaran sudah diverifikasi panitia pada {{ $registration->payment_verified_at ? \Carbon\Carbon::parse($registration->payment_verified_at)->translatedFormat('d M Y H:i') : '—' }}.
                                    <span class="block mt-1 text-xs font-medium text-emerald-700/90">Invoice dapat diunduh di kartu <strong>Dokumen &amp; Unduhan</strong> di atas.</span>
                                </div>
                            </div>
                        @elseif($registration->payment_status === 'pending_verification')
                            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-700 text-sm font-semibold flex items-start gap-2">
                                <i class="ti ti-clock-filled mt-0.5"></i>
                                <div>
                                    Bukti pembayaran Anda sudah diunggah. Menunggu verifikasi panitia.
                                </div>
                            </div>
                        @elseif($registration->payment_status === 'unpaid')
                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <h4 class="text-sm font-bold text-deep-slate mb-3">Tagihan Pendaftaran</h4>
                                    <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-5 text-center">
                                        <span class="text-[10px] text-emerald-600 font-bold uppercase tracking-wider block">Total Biaya Pendaftaran</span>
                                        <h2 class="text-2xl font-extrabold text-emerald-600 m-0">Rp {{ number_format($registration->total_fee, 0, ',', '.') }}</h2>
                                    </div>

                                    @if($bankAccounts->isNotEmpty())
                                        <div class="mt-4">
                                            <h4 class="text-sm font-bold text-deep-slate mb-2">Transfer ke Rekening:</h4>
                                            @foreach($bankAccounts as $bank)
                                                <div class="bg-surface-container-low border border-outline-variant/40 rounded-xl p-3.5 mb-2 last:mb-0">
                                                    <div class="flex justify-between items-center">
                                                        <div>
                                                            <span class="text-xs font-bold text-deep-slate block">{{ $bank->bank_name }}</span>
                                                            <span class="text-sm font-mono font-extrabold text-deep-slate block mt-0.5">{{ $bank->account_number }}</span>
                                                            <span class="text-[10px] text-on-surface-variant block">a.n. {{ $bank->account_name }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div>
                                    <h4 class="text-sm font-bold text-deep-slate mb-3">Upload Bukti Transfer</h4>
                                    <p class="text-xs text-on-surface-variant font-medium mb-4 leading-normal">
                                        Setelah melakukan transfer, upload bukti pembayaran untuk diverifikasi panitia.
                                    </p>
                                    <div wire:ignore wire:key="pond-{{ $activeRegId }}-payment" x-data="{ pond: null }" x-init="
                                        pond = FilePond.create($refs.paymentProof, {
                                            credits: false,
                                            labelIdle: 'Tarik & Letakkan gambar atau <span class=\'filepond--label-action\'>Pilih File</span>',
                                            server: {
                                                process: (fieldName, file, metadata, load, error, progress, abort, transfer, options) => {
                                                    @this.upload('paymentProof', file, load, error, progress)
                                                },
                                                revert: (filename, load) => {
                                                    @this.removeUpload('paymentProof', filename, load)
                                                },
                                            },
                                        });
                                    ">
                                        <input type="file" x-ref="paymentProof" accept="image/*">
                                    </div>
                                    @error('paymentProof') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                                    @if($registration->payment_proof)
                                        <span class="text-xs font-bold text-emerald-600 mt-2 block inline-flex items-center gap-1"><i class="ti ti-circle-check-filled"></i> Bukti pembayaran sudah diunggah</span>
                                    @endif

                                    <button wire:click="submitPaymentProof" class="btn-primary w-full mt-4 py-3 px-6 font-bold text-sm inline-flex items-center justify-center gap-1.5 cursor-pointer">
                                        <i class="ti ti-upload"></i> Kirim Bukti Pembayaran
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- BOOKING STATE: TM Info Box --}}
            @if($registration->status_berkas === 'booking')
                <div class="surface-card p-5 border border-primary/20">
                    <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2 mb-3">
                        <i class="ti ti-info-circle text-primary"></i> Lengkapi Data &amp; Konfirmasi
                    </h3>

                    @if($registration->eventner->technical_meeting && now()->lt($registration->eventner->technical_meeting))
                        <div class="p-4 rounded-xl bg-amber-500/5 border border-amber-500/15 text-xs text-amber-800 leading-relaxed font-semibold">
                            <i class="ti ti-clock"></i>
                            Konfirmasi pendaftaran baru dibuka setelah Technical Meeting selesai dilaksanakan pada:
                            <strong class="text-deep-slate block mt-1">{{ \Carbon\Carbon::parse($registration->eventner->technical_meeting)->translatedFormat('d F Y, H:i') }} WIB</strong>
                            Anda tetap dapat melengkapi draf data pasukan di bawah ini terlebih dahulu.
                        </div>
                    @else
                        <div class="p-4 rounded-xl bg-emerald-500/5 border border-emerald-500/15 text-xs text-emerald-800 leading-relaxed font-semibold">
                            <i class="ti ti-circle-check"></i>
                            Masa Technical Meeting telah dilaksanakan. Silakan lengkapi berkas dan data pasukan di bawah ini, kemudian tekan tombol <strong>"Konfirmasi &amp; Kirim"</strong> untuk memproses berkas ke Panitia.
                        </div>
                    @endif
                </div>
            @endif

            {{-- Form Area --}}
            @if($registration->status_berkas === 'Terverifikasi')
                @if($registration->verification_method === 'hardcopy')
                    {{-- Hardcopy Verified View: Bersih, ringkas, tanpa baris berkas digital & tabel kosong --}}
                    <div class="surface-card overflow-hidden">
                        <div class="bg-surface-container px-5 py-4 border-b border-outline-variant/40 flex flex-wrap items-center justify-between gap-2">
                            <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2 mb-0">
                                <i class="ti ti-shield-check text-emerald-600"></i> Status Pendaftaran: Terverifikasi (Hardcopy)
                            </h3>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-700 border border-emerald-500/30">
                                <i class="ti ti-file-check"></i> Berkas Fisik Sah
                            </span>
                        </div>
                        <div class="p-5 sm:p-6 space-y-5">
                            {{-- Ringkasan Data Utama Pendaftaran --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div class="p-4 rounded-xl bg-surface-container-low border border-outline-variant/30">
                                    <span class="text-xs text-on-surface-variant block mb-1 font-semibold">Kategori Lomba</span>
                                    <strong class="text-deep-slate text-sm font-bold">{{ $registration->competitionCategory->full_name }}</strong>
                                </div>
                                <div class="p-4 rounded-xl bg-surface-container-low border border-outline-variant/30">
                                    <span class="text-xs text-on-surface-variant block mb-1 font-semibold">Sekolah / Kontingen</span>
                                    <strong class="text-deep-slate text-sm font-bold">{{ $registration->display_name }}</strong>
                                    @if($registration->npsn)
                                        <span class="text-xs text-on-surface-variant block mt-0.5 font-mono">NPSN: {{ $registration->npsn }}</span>
                                    @endif
                                </div>
                                <div class="p-4 rounded-xl bg-surface-container-low border border-outline-variant/30">
                                    <span class="text-xs text-on-surface-variant block mb-1 font-semibold">Pelatih / Penanggung Jawab</span>
                                    <strong class="text-deep-slate text-sm font-bold">{{ $registration->nama_pelatih ?: '-' }}</strong>
                                    @if($registration->no_hp)
                                        <span class="text-xs text-on-surface-variant block mt-0.5">No. HP / WA: {{ $registration->no_hp }}</span>
                                    @endif
                                </div>
                                <div class="p-4 rounded-xl bg-surface-container-low border border-outline-variant/30">
                                    <span class="text-xs text-on-surface-variant block mb-1 font-semibold">Verifikasi Sekretariat</span>
                                    <div class="text-xs text-emerald-700 font-bold flex items-center gap-1.5">
                                        <i class="ti ti-circle-check"></i> Berkas Fisik (Hardcopy) Terverifikasi di Meja Panitia
                                    </div>
                                    @if($registration->verification_notes)
                                        <span class="text-xs text-on-surface-variant block mt-1 italic">
                                            Catatan: "{{ $registration->verification_notes }}"
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Danton: Hanya tampil jika diisi --}}
                            @if($registration->danton_nama)
                                <div class="p-4 rounded-xl bg-surface-container-low border border-outline-variant/30">
                                    <span class="text-xs text-on-surface-variant block mb-2 font-bold uppercase tracking-wider">Komandan Pasukan (Danton)</span>
                                    <div class="flex items-center gap-3">
                                        @if($registration->danton_foto)
                                            <img src="{{ asset('storage/' . $registration->danton_foto) }}" class="h-10 w-10 rounded-lg object-cover border border-outline-variant/30">
                                        @endif
                                        <div>
                                            <strong class="text-deep-slate block text-sm">{{ $registration->danton_nama }}</strong>
                                            @if($registration->danton_nisn)
                                                <span class="text-xs text-on-surface-variant">NISN: {{ $registration->danton_nisn }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Daftar Pasukan: Hanya tampil jika ada data anggota pasukan --}}
                            @if($registration->participants->isNotEmpty())
                                @php
                                    $grupPeserta = $this->groupFields->first();
                                    $subNama = $grupPeserta?->subField('nama');
                                    $subNisn = $grupPeserta?->subField('nisn');
                                @endphp
                                <div class="border border-outline-variant/40 rounded-xl overflow-hidden">
                                    <div class="bg-surface-container px-4 py-2.5 border-b border-outline-variant/40">
                                        <h4 class="font-display text-xs font-bold text-deep-slate inline-flex items-center gap-1.5 mb-0">
                                            <i class="ti ti-users-group"></i> {{ $grupPeserta?->label ?? 'Daftar Pasukan' }} ({{ $registration->participants->count() }} Anggota)
                                        </h4>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs border-collapse text-left">
                                            <thead class="bg-surface-container-low border-b border-outline-variant/30 font-bold text-deep-slate">
                                                <tr>
                                                    <th class="px-4 py-2 text-center w-10">No</th>
                                                    <th class="px-4 py-2">{{ $subNama['label'] ?? 'Nama Lengkap' }}</th>
                                                    <th class="px-4 py-2 w-36">{{ $subNisn['label'] ?? 'NISN' }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-outline-variant/30">
                                                @foreach($registration->participants as $index => $p)
                                                    <tr>
                                                        <td class="px-4 py-2.5 text-center text-on-surface-variant font-medium">{{ $index + 1 }}</td>
                                                        <td class="px-4 py-2.5 font-bold text-deep-slate">{{ $p->nama }}</td>
                                                        <td class="px-4 py-2.5 text-on-surface-variant font-medium">{{ $p->nisn ?: '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif

                            {{-- Kartu Status Sah --}}
                            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                                <div class="text-sm font-bold text-emerald-800 flex items-center gap-2">
                                    <i class="ti ti-shield-check text-base"></i> DATA TERVERIFIKASI BERKAS FISIK (HARDCOPY)
                                </div>
                                <p class="text-xs text-emerald-700/90 mt-1 mb-0">
                                    Data pendaftaran resmi dan berkas fisik telah diserahkan serta diverifikasi langsung di meja panitia. Formulir dan berkas fisik sah tersimpan di sekretariat panitia.
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Verified Data Table View (Locked/Read Only untuk Digital Track) --}}
                    <div class="surface-card overflow-hidden">
                        <div class="bg-surface-container px-5 py-4 border-b border-outline-variant/40">
                            <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2">
                                <i class="ti ti-shield-check text-emerald-600"></i> Data Pendaftaran Terverifikasi
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border-collapse text-left">
                                <tbody class="divide-y divide-outline-variant/30">
                                    <tr>
                                        <th class="px-5 py-3.5 bg-surface-container-low text-on-surface-variant font-bold w-1/3">Kategori Lomba</th>
                                        <td class="px-5 py-3.5 text-deep-slate font-bold">{{ $registration->competitionCategory->full_name }}</td>
                                    </tr>
                                    <tr>
                                        <th class="px-5 py-3.5 bg-surface-container-low text-on-surface-variant font-bold">{{ $this->labelSumber('nama_sekolah') ?? 'Nama Sekolah' }}</th>
                                        <td class="px-5 py-3.5 text-deep-slate font-bold">{{ $registration->display_name }}</td>
                                    </tr>
                                    <tr>
                                        <th class="px-5 py-3.5 bg-surface-container-low text-on-surface-variant font-bold">{{ \App\Models\RegistrationField::SECTIONS['pelatih'] }}</th>
                                        <td class="px-5 py-3.5">
                                            <div class="flex items-center gap-3">
                                                @if($registration->foto_pelatih)
                                                    <img src="{{ asset('storage/' . $registration->foto_pelatih) }}" class="h-11 w-11 rounded-lg object-cover border border-outline-variant/30 shadow-sm shrink-0">
                                                @endif
                                                <div>
                                                    <div class="font-bold text-deep-slate leading-tight mb-0.5">{{ $registration->nama_pelatih }}</div>
                                                    <span class="text-xs text-on-surface-variant block">{{ $this->labelSumber('no_hp') ?? 'No. HP' }}: {{ $registration->no_hp }}</span>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="px-5 py-3.5 bg-surface-container-low text-on-surface-variant font-bold">Berkas Persyaratan</th>
                                        <td class="px-5 py-3.5">
                                            @php $berkasAda = $this->berkasFields->filter(fn ($b) => $b['path']); @endphp
                                            @if($berkasAda->isEmpty())
                                                <span class="text-xs text-on-surface-variant italic">Belum ada berkas diunggah.</span>
                                            @else
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach($berkasAda as $berkas)
                                                        <a href="{{ $berkas['url'] }}" target="_blank" class="btn-ghost py-1.5 px-3 text-xs leading-normal font-bold inline-flex items-center gap-1 text-decoration-none">
                                                            <i class="ti {{ $berkas['field']->type === 'image' ? 'ti-photo' : 'ti-file-text' }}"></i> {{ $berkas['field']->label }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="px-5 py-3.5 bg-surface-container-low text-on-surface-variant font-bold">{{ \App\Models\RegistrationField::SECTIONS['danton'] }}</th>
                                        <td class="px-5 py-3.5">
                                            <div class="flex items-center gap-3">
                                                @if($registration->danton_foto)
                                                    <img src="{{ asset('storage/' . $registration->danton_foto) }}" class="h-11 w-11 rounded-lg object-cover border border-outline-variant/30 shadow-sm shrink-0">
                                                @endif
                                                <div>
                                                    <div class="font-bold text-deep-slate leading-tight mb-0.5">{{ $registration->danton_nama }}</div>
                                                    <span class="text-xs text-on-surface-variant block">{{ $this->barisField('danton_nisn')?->label ?? 'NISN' }}: {{ $registration->danton_nisn ?: '-' }}</span>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @foreach($this->textFields as $field)
                                        @php $nilaiTampil = $this->nilaiField($field); @endphp
                                        @continue($field->isFile() || $field->section !== 'umum')
                                        @continue($nilaiTampil === '')
                                        <tr wire:key="ro-{{ $field->id }}">
                                            <th class="px-5 py-3.5 bg-surface-container-low text-on-surface-variant font-bold">{{ $field->label }}</th>
                                            <td class="px-5 py-3.5 text-deep-slate font-medium">
                                                @if($field->type === 'select')
                                                    {{ collect($field->options ?? [])->firstWhere('value', $nilaiTampil)['label'] ?? $nilaiTampil }}
                                                @elseif($field->type === 'wilayah')
                                                    {{ \App\Services\WilayahService::nama($nilaiTampil) }}
                                                @else
                                                    {{ $nilaiTampil }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @php
                            $grupPeserta = $this->groupFields->first();
                            $subNama = $grupPeserta?->subField('nama');
                            $subNisn = $grupPeserta?->subField('nisn');
                        @endphp
                        <div class="bg-surface-container px-5 py-3 border-t border-b border-outline-variant/40">
                            <h3 class="font-display text-sm font-bold text-deep-slate inline-flex items-center gap-1.5 mb-0">
                                <i class="ti ti-users-group"></i> {{ $grupPeserta?->label ?? 'Daftar Pasukan' }} ({{ $registration->participants->count() }} Anggota)
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs border-collapse text-left">
                                <thead class="bg-surface-container-low border-b border-outline-variant/30 font-bold text-deep-slate">
                                    <tr>
                                        <th class="px-5 py-2.5 text-center w-12">No</th>
                                        <th class="px-5 py-2.5 w-16">{{ $grupPeserta?->subField('foto')['label'] ?? 'Foto' }}</th>
                                        <th class="px-5 py-2.5">{{ $subNama['label'] ?? 'Nama Lengkap' }}</th>
                                        <th class="px-5 py-2.5 w-40">{{ $subNisn['label'] ?? 'NISN' }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/30">
                                    @foreach($registration->participants as $index => $p)
                                        <tr>
                                            <td class="px-5 py-3 text-center text-on-surface-variant font-medium">{{ $index + 1 }}</td>
                                            <td class="px-5 py-3">
                                                @if($p->foto)
                                                    <img src="{{ asset('storage/' . $p->foto) }}" class="h-9 w-9 rounded object-cover border border-outline-variant/30 shadow-sm">
                                                @else
                                                    <div class="flex h-9 w-9 items-center justify-center rounded bg-outline-variant/20 text-on-surface-variant border border-outline-variant/30">
                                                        <i class="ti ti-user text-sm"></i>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-5 py-3 font-bold text-deep-slate text-sm">{{ $p->nama }}</td>
                                            <td class="px-5 py-3 text-on-surface-variant font-medium">{{ $p->nisn ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="text-center mt-8">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-6 py-2.5 text-sm font-bold text-emerald-600 border border-emerald-500/25">
                            <i class="ti ti-circle-check-filled"></i> DATA TERVERIFIKASI PANITIA
                        </span>
                        <p class="text-xs font-semibold text-on-surface-variant mt-2 leading-normal">Data di atas adalah data resmi yang dikunci dan akan digunakan pada saat perlombaan. Formulir pendaftaran dapat diunduh di kartu <strong>Dokumen &amp; Unduhan</strong> di atas.</p>
                    </div>
                @endif
            @else

                {{-- FORM VIEW: (for non-verified/booking/rejected states) --}}
                @php
                    $isLocked = ($registration->is_finalized && $registration->status_berkas !== 'Ditolak');
                @endphp

                @if($registration->status_berkas === 'booking')
                    {{-- Booking Preparation Info (Data form is disabled on client unless TM is done) --}}
                    <div class="surface-card overflow-hidden">
                        <div class="bg-surface-container px-5 py-4 border-b border-outline-variant/40">
                            <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2">
                                <i class="ti ti-clipboard-list text-primary"></i>
                                Persiapan Data &amp; Berkas
                            </h3>
                        </div>
                        <div class="p-6">
                            <p class="text-sm text-on-surface-variant mb-6 leading-relaxed">Pengisian berkas &amp; anggota belum dibuka secara resmi. Silakan persiapkan berkas berikut untuk mempermudah proses input nanti:</p>

                            @php
                                // Daftar persiapan ini disusun dari definisi field
                                // event — panitia mengubah label di builder, isi
                                // pratinjau ikut berubah.
                                $teksPratinjau = $this->textFields->groupBy(fn ($f) => $f->section ?: 'umum');
                                $berkasPratinjau = collect($this->berkasFields)->groupBy(fn ($b) => $b['field']->section ?: 'umum');
                                $grupPratinjau = $this->groupFields->first();
                                $ikonPratinjau = ['umum' => 'ti-clipboard-text', 'pelatih' => 'ti-user', 'danton' => 'ti-medal-2'];
                            @endphp

                            <div class="grid gap-5 sm:grid-cols-2">
                                @foreach(\App\Models\RegistrationField::SECTIONS as $slug => $judulSection)
                                    @php
                                        $teksSection = $teksPratinjau->get($slug, collect());
                                        $berkasSection = $berkasPratinjau->get($slug, collect());
                                    @endphp
                                    @continue($teksSection->isEmpty() && $berkasSection->isEmpty())

                                    <div class="bg-primary/5 border border-primary/10 rounded-xl p-4 flex flex-col gap-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                                <i class="ti {{ $ikonPratinjau[$slug] ?? 'ti-clipboard-text' }} text-base"></i>
                                            </div>
                                            <h4 class="font-display text-sm font-bold text-deep-slate m-0">{{ $judulSection }}</h4>
                                        </div>
                                        <ul class="list-disc pl-4 text-xs text-on-surface-variant space-y-1.5 leading-relaxed m-0 font-medium">
                                            @foreach($teksSection as $field)
                                                <li>{{ $field->label }}@if($field->is_required) <span class="text-red-500">*</span> @endif</li>
                                            @endforeach
                                            @foreach($berkasSection as $berkas)
                                                <li>
                                                    {{ $berkas['field']->label }} ({{ $berkas['field']->type === 'image' ? 'JPG/PNG' : 'PDF/JPG/PNG' }})
                                                    @if($berkas['field']->is_required) <span class="text-red-500">*</span> @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach

                                @if($grupPratinjau)
                                    <div class="bg-emerald-500/5 border border-emerald-500/10 rounded-xl p-4 flex flex-col gap-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
                                                <i class="ti ti-users-group text-base"></i>
                                            </div>
                                            <h4 class="font-display text-sm font-bold text-deep-slate m-0">{{ $grupPratinjau->label }}</h4>
                                        </div>
                                        <ul class="list-disc pl-4 text-xs text-on-surface-variant space-y-1.5 leading-relaxed m-0 font-medium">
                                            @foreach($grupPratinjau->sub_fields['items'] ?? [] as $sub)
                                                <li>{{ $sub['label'] }}@if($sub['is_required'] ?? false) <span class="text-red-500">*</span> @endif</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>

                            <div class="mt-5 p-4 bg-primary/5 rounded-xl border border-primary/20 text-xs text-primary leading-normal font-semibold">
                                <i class="ti ti-info-circle"></i> Simpan / bookmark link halaman portal pendaftaran mandiri ini. Anda dapat mengaksesnya kembali sewaktu-waktu.
                            </div>
                        </div>
                    </div>
                @endif

                @php
                    $regStatus = $registration->eventner->registration_status ?? 'open';
                    $beforeTm = $registration->status_berkas === 'booking'
                        && $registration->eventner->technical_meeting
                        && now()->lt($registration->eventner->technical_meeting);
                    // Form shown when: registration is open, OR (booking mode + TM passed)
                    $showForm = $regStatus === 'open' || !$beforeTm;
                @endphp

                @if($showForm)
                @php
                    // Kartu dirender per section, isinya loop field builder.
                    // Field yang dinonaktifkan panitia tidak muncul di sini —
                    // termasuk nama pelatih, foto pelatih, dan danton.
                    $teksPerSection = $this->textFields->groupBy(fn ($f) => $f->section ?: 'umum');
                    $berkasPerSection = collect($this->berkasFields)->groupBy(fn ($b) => $b['field']->section ?: 'umum');
                    $grupPeserta = $this->groupFields->first();
                    $dantonAktif = $this->textFields->where('section', 'danton')->isNotEmpty()
                        || collect($this->berkasFields)->contains(fn ($b) => $b['field']->section === 'danton');
                @endphp
                <fieldset {{ $isLocked ? 'disabled' : '' }} class="flex flex-col gap-6">

                    <form wire:submit.prevent="submit(false)" class="flex flex-col gap-6">
                        @foreach(\App\Models\RegistrationField::SECTIONS as $slug => $judulSection)
                            {{-- Section danton punya kartunya sendiri di bawah:
                                 inputnya terikat ke properti dantonNama/dantonNisn,
                                 bukan ke fieldValues. Merendernya di sini juga
                                 membuat satu field tampil dua kali. --}}
                            @continue($slug === 'danton')

                            @php
                                $teksSection = $teksPerSection->get($slug, collect());
                                $berkasSection = $berkasPerSection->get($slug, collect());
                            @endphp
                            @continue($teksSection->isEmpty() && $berkasSection->isEmpty())

                            <div class="surface-card overflow-hidden">
                                <div class="bg-surface-container px-5 py-4 border-b border-outline-variant/40">
                                    <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2">
                                        <i class="ti {{ $slug === 'pelatih' ? 'ti-user' : ($slug === 'danton' ? 'ti-star text-amber-500' : 'ti-clipboard-text text-primary') }}"></i> {{ $judulSection }}
                                    </h3>
                                </div>
                                <div class="p-6">
                                    <div class="grid gap-5 md:grid-cols-2">
                                        @foreach($teksSection as $field)
                                            @php
                                                $kunci = 'fieldValues.' . $field->field_key;
                                                $nilai = $this->nilaiField($field);
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
                                                @if($field->help_text && ! in_array($field->type, ['textarea']))
                                                    <span class="text-[10px] text-on-surface-variant font-medium mt-1 block leading-normal">{{ $field->help_text }}</span>
                                                @endif
                                            </div>
                                        @endforeach

                                        @foreach($berkasSection as $berkas)
                                            @php
                                                $field = $berkas['field'];
                                                $fieldId = 'pond-' . $activeRegId . '-field-' . $field->id;
                                                $uploadUrl = route('magic.link.field.upload', ['token' => $token, 'fieldId' => $field->id]);
                                                $deleteUrl = route('magic.link.field.destroy', ['token' => $token, 'fieldId' => $field->id]);
                                                // Dua-duanya HARUS MIME, dan HARUS sama.
                                                //
                                                // FilePondPluginFileValidateType memetakan
                                                // atribut `accept` ke opsi `acceptedFileTypes`
                                                // (SET_ATTRIBUTE_TO_OPTION_MAP), dan core merge
                                                // atribut SETELAH opsi JS — jadi `accept` menimpa
                                                // array yang kita tulis di FilePond.create().
                                                // Memberi ekstensi di `accept` membuat daftar
                                                // yang benar-benar dipakai berisi ".pdf", yang
                                                // tidak pernah cocok dengan file.type
                                                // ("application/pdf") — plugin hanya mengerti
                                                // MIME dan wildcard, ekstensi "not enabled yet".
                                                // Efeknya berkas yang benar ditolak "File is of
                                                // invalid type" tanpa pernah sampai ke server.
                                                //
                                                // Wildcard image/* dulu lolos karena kedua sisi
                                                // kebetulan sama; daftar eksplisit tidak.
                                                $tipes = $field->type === 'image'
                                                    ? 'image/*'
                                                    : 'application/pdf,image/jpeg,image/png';
                                                $mimeDiterima = $field->type === 'image'
                                                    ? '[\'image/*\']'
                                                    : '[\'application/pdf\', \'image/jpeg\', \'image/png\']';
                                                $batas = number_format($field->max_kb ?: 5120, 0, ',', '.');
                                            @endphp
                                            {{-- Unggahan lewat endpoint HTTP, bukan properti Livewire:
                                                 field builder jumlahnya dinamis sedangkan properti
                                                 upload Livewire harus ada saat compile. --}}
                                            <div wire:key="{{ $fieldId }}" class="md:col-span-2">
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
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @if($dantonAktif)
                        <div class="surface-card overflow-hidden">
                            <div class="bg-surface-container px-5 py-4 border-b border-outline-variant/40">
                                <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2">
                                    <i class="ti ti-star text-amber-500"></i> {{ \App\Models\RegistrationField::SECTIONS['danton'] }}
                                </h3>
                            </div>
                            <div class="p-6">
                                <div class="grid gap-5 md:grid-cols-12">
                                    @php
                                        $dantonNamaField = $this->barisField('danton_nama');
                                        $dantonNisnField = $this->barisField('danton_nisn');
                                        $dantonFotoField = $this->barisField('danton_foto');
                                        $fotoBerkas = collect($this->berkasFields)->firstWhere('field.id', $dantonFotoField?->id);
                                    @endphp
                                    @if($dantonNamaField)
                                    <div class="md:col-span-5">
                                        <label class="text-sm font-bold text-deep-slate block mb-1.5">
                                            {{ $dantonNamaField->label }}
                                            @if($dantonNamaField->is_required) <span class="text-red-500">*</span> @endif
                                        </label>
                                        <input type="text" wire:model="dantonNama" class="field-input w-full" placeholder="{{ $dantonNamaField->help_text ?: $dantonNamaField->label }}">
                                        @error('dantonNama') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    @endif
                                    @if($dantonNisnField)
                                    <div class="md:col-span-3">
                                        <label class="text-sm font-bold text-deep-slate block mb-1.5">
                                            {{ $dantonNisnField->label }}
                                            @if($dantonNisnField->is_required) <span class="text-red-500">*</span> @endif
                                        </label>
                                        <input type="text" wire:model="dantonNisn" class="field-input w-full" placeholder="{{ $dantonNisnField->help_text ?: $dantonNisnField->label }}">
                                        @error('dantonNisn') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    @endif
                                    @if($dantonFotoField && $fotoBerkas)
                                    <div class="md:col-span-4">
                                        <label class="text-sm font-bold text-deep-slate block mb-1.5">
                                            {{ $dantonFotoField->label }}
                                            @if($dantonFotoField->is_required) <span class="text-red-500">*</span> @endif
                                            <span class="font-normal text-on-surface-variant">(maks. {{ number_format($dantonFotoField->max_kb ?: 5120, 0, ',', '.') }} KB)</span>
                                        </label>
                                        {{-- Unggahan lewat endpoint HTTP generik, sama seperti
                                             berkas lain — bukan properti upload Livewire. --}}
                                        <div wire:ignore wire:key="pond-{{ $activeRegId }}-danton" x-data="{ pond: null }" x-init="
                                            pond = FilePond.create($refs.input, {
                                                credits: false,
                                                labelIdle: 'Tarik & Letakkan gambar atau <span class=\'filepond--label-action\'>Pilih File</span>',
                                                server: {
                                                    process: (fieldName, file, metadata, load, error, progress, abort) => {
                                                        const data = new FormData();
                                                        data.append('file', file);
                                                        data.append('_token', '{{ csrf_token() }}');

                                                        const request = new XMLHttpRequest();
                                                        request.open('POST', '{{ route('magic.link.field.upload', ['token' => $token, 'fieldId' => $dantonFotoField->id]) }}');
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

                                                        return { abort: () => { request.abort(); abort(); } };
                                                    },
                                                    revert: (uniqueFileId, load, error) => {
                                                        fetch('{{ route('magic.link.field.destroy', ['token' => $token, 'fieldId' => $dantonFotoField->id]) }}', {
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
                                            <input type="file" x-ref="input" accept="image/*">
                                        </div>
                                        @error('fields.' . $dantonFotoField->id) <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                                        @if($registration->danton_foto)
                                            <span class="text-xs font-bold text-emerald-600 mt-2 block inline-flex items-center gap-1"><i class="ti ti-circle-check-filled"></i> Foto danton berhasil diunggah</span>
                                            <div class="mt-2">
                                                <a href="{{ asset('storage/' . $registration->danton_foto) }}" target="_blank" title="Lihat foto">
                                                    <img src="{{ asset('storage/' . $registration->danton_foto) }}" class="h-24 w-20 rounded-lg object-cover border border-outline-variant/30 shadow-sm" alt="{{ $dantonFotoField->label }}">
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Anggota Pasukan — sub-field & jumlah baris dari builder group --}}
                        @php
                            $subNamaPeserta = $grupPeserta?->subField('nama');
                            $subNisnPeserta = $grupPeserta?->subField('nisn');
                            $subFotoPeserta = $grupPeserta?->subField('foto');
                        @endphp
                        @if($grupPeserta)
                        <div class="surface-card overflow-hidden">
                            <div class="bg-surface-container px-5 py-4 border-b border-outline-variant/40 flex justify-between items-center">
                                <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2 mb-0">
                                    <i class="ti ti-users-group text-primary"></i> {{ $grupPeserta->label }}
                                </h3>
                                @if(!$isLocked)
                                    <button type="button" wire:click="addParticipant" class="btn-ghost py-1.5 px-3.5 text-xs font-bold leading-normal inline-flex items-center gap-1 transition cursor-pointer">
                                        <i class="ti ti-plus"></i> Tambah Anggota
                                    </button>
                                @endif
                            </div>
                            <div class="p-6 flex flex-col gap-6 divide-y divide-outline-variant/30">
                                @foreach($participants as $index => $participant)
                                    <div wire:key="participant-{{ $index }}" class="flex gap-4 items-start flex-wrap pt-6 first:pt-0">
                                        <div class="h-8 w-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs shrink-0 mt-8">
                                            {{ $index + 1 }}
                                        </div>
                                        @if($subNamaPeserta)
                                        <div class="flex-1 min-w-[200px]">
                                            <label class="text-xs font-bold text-on-surface-variant block mb-1.5 uppercase tracking-wider">
                                                {{ $subNamaPeserta['label'] }}
                                                @if($subNamaPeserta['is_required'] ?? false) <span class="text-red-500">*</span> @endif
                                            </label>
                                            <input type="text" wire:model="participants.{{ $index }}.nama" class="field-input w-full" placeholder="{{ $subNamaPeserta['label'] }}">
                                            @error('participants.'.$index.'.nama') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                        @endif
                                        @if($subNisnPeserta)
                                        <div class="flex-1 min-w-[120px]">
                                            <label class="text-xs font-bold text-on-surface-variant block mb-1.5 uppercase tracking-wider">
                                                {{ $subNisnPeserta['label'] }}
                                                @if($subNisnPeserta['is_required'] ?? false) <span class="text-red-500">*</span> @endif
                                            </label>
                                            <input type="text" wire:model="participants.{{ $index }}.nisn" class="field-input w-full" placeholder="{{ $subNisnPeserta['label'] }}">
                                            @error('participants.'.$index.'.nisn') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                        @endif
                                        @if($subFotoPeserta)
                                        <div class="flex-1 min-w-[160px]">
                                            <label class="text-xs font-bold text-on-surface-variant block mb-1.5 uppercase tracking-wider">
                                                {{ $subFotoPeserta['label'] }}
                                                @if($subFotoPeserta['is_required'] ?? false) <span class="text-red-500">*</span> @endif
                                            </label>
                                            <div wire:ignore wire:key="pond-{{ $activeRegId }}-p-{{ $index }}" x-data="{ pond: null }" x-init="
                                                pond = FilePond.create($refs.input, {
                                                    credits: false,
                                                    labelIdle: 'Tarik & Letakkan gambar atau <span class=\'filepond--label-action\'>Pilih File</span>',
                                                    server: {
                                                        process: (fieldName, file, metadata, load, error, progress, abort, transfer, options) => {
                                                            @this.upload('participants.{{ $index }}.foto', file, load, error, progress)
                                                        },
                                                        revert: (filename, load) => {
                                                            @this.removeUpload('participants.{{ $index }}.foto', filename, load)
                                                        },
                                                    },
                                                });
                                            ">
                                                <input type="file" x-ref="input" accept="image/*">
                                            </div>
                                            @if(isset($participant['existing_foto']) && $participant['existing_foto'])
                                                <span class="text-xs font-bold text-emerald-600 mt-2 block inline-flex items-center gap-1"><i class="ti ti-circle-check-filled"></i> Foto diunggah</span>
                                                <div class="mt-2">
                                                    <a href="{{ asset('storage/' . $participant['existing_foto']) }}" target="_blank" title="Lihat foto">
                                                        <img src="{{ asset('storage/' . $participant['existing_foto']) }}" class="h-20 w-16 rounded object-cover border border-outline-variant/30 shadow-sm" alt="{{ $subFotoPeserta['label'] }}">
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                        @endif
                                        @if(count($participants) > 1 && !$isLocked)
                                            <div class="pt-8">
                                                <button type="button" wire:click="removeParticipant({{ $index }})" class="flex h-10 w-10 items-center justify-center rounded-xl border border-red-500/30 text-red-500 hover:bg-red-50 transition cursor-pointer">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Action Buttons --}}
                        @if(!$isLocked)
                            <div class="flex flex-wrap gap-3 justify-center mt-6">
                                <button type="submit" class="btn-ghost py-2.5 px-5 font-bold text-xs inline-flex items-center gap-1.5 cursor-pointer">
                                    <i class="ti ti-device-floppy"></i> Simpan Draft
                                </button>

                                @if($registration->status_berkas === 'booking' && ($regStatus === 'open' || !$registration->eventner->technical_meeting || now()->gte($registration->eventner->technical_meeting)))
                                    <button type="button" onclick="confirmAction()" class="btn-primary !bg-emerald-600 hover:!bg-emerald-700 !text-white border-none py-2.5 px-5 font-bold text-xs inline-flex items-center gap-1.5 cursor-pointer shadow-xs">
                                        <i class="ti ti-circle-check"></i> Konfirmasi &amp; Kirim
                                    </button>
                                @elseif($registration->status_berkas !== 'booking')
                                    <button type="button" onclick="confirmFinalization()" class="btn-primary py-2.5 px-5 font-bold text-xs inline-flex items-center gap-1.5 cursor-pointer shadow-xs">
                                        <i class="ti ti-send"></i> Finalisasi Data
                                    </button>
                                @endif
                            </div>
                        @else
                            <div class="text-center mt-8">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-500/10 px-6 py-2.5 text-sm font-bold text-blue-600 border border-blue-500/25">
                                    <i class="ti ti-clock"></i> MENUNGGU VERIFIKASI PANITIA
                                </span>
                            </div>
                        @endif
                    </form>
                </fieldset>
                @endif
            @endif

        </div>
    </div>

    {{-- SweetAlert2 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmFinalization() {
            Swal.fire({
                title: 'Finalisasi Data Pendaftaran?',
                text: "Semua data anggota akan dikunci untuk verifikasi panitia dan tidak dapat diubah kembali.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0062ff',
                cancelButtonColor: '#ba1a1a',
                confirmButtonText: 'Ya, Finalisasi!',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('submit', true);
                }
            })
        }

        function confirmAction() {
            Swal.fire({
                title: 'Konfirmasi Kirim Berkas?',
                text: "Pastikan data pelatih, berkas persyaratan, dan danton sudah diisi lengkap untuk diverifikasi panitia.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#ba1a1a',
                confirmButtonText: 'Ya, Konfirmasi!',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('confirm');
                }
            })
        }

        function noteAudioPlayer(audioUrl, durationSec) {
            return {
                audio: null,
                isPlaying: false,
                currentTime: 0,
                duration: durationSec || 0,
                progress: 0,

                init() {
                    this.audio = new Audio(audioUrl);
                    this.audio.addEventListener('timeupdate', () => {
                        this.currentTime = Math.floor(this.audio.currentTime);
                        const dur = this.duration || (this.audio.duration || 1);
                        this.progress = Math.min(100, (this.audio.currentTime / dur) * 100);
                    });
                    this.audio.addEventListener('ended', () => {
                        this.isPlaying = false;
                        this.currentTime = 0;
                        this.progress = 0;
                    });
                },

                togglePlay() {
                    if (!this.audio) return;
                    if (this.isPlaying) {
                        this.audio.pause();
                        this.isPlaying = false;
                    } else {
                        const p = this.audio.play();
                        if (p && typeof p.then === 'function') {
                            p.then(() => { this.isPlaying = true; }).catch(() => { this.isPlaying = false; });
                        } else {
                            this.isPlaying = true;
                        }
                    }
                },

                seek(e) {
                    if (!this.audio) return;
                    const rect = e.currentTarget.getBoundingClientRect();
                    const pos = (e.clientX - rect.left) / rect.width;
                    const target = pos * (this.duration || this.audio.duration);
                    this.audio.currentTime = target;
                },

                formatTime(seconds) {
                    const s = Math.max(0, parseInt(seconds || 0));
                    const m = Math.floor(s / 60);
                    const sec = s % 60;
                    return `${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
                }
            };
        }
    </script>
    @push('scripts')
        <script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js"></script>
        <script src="https://unpkg.com/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.js"></script>
        <script src="https://unpkg.com/filepond/dist/filepond.js"></script>
        <script>
            FilePond.registerPlugin(FilePondPluginImagePreview, FilePondPluginFileValidateType);
        </script>
    @endpush
</div>
