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

    {{-- ========== COVER BANNER / FALLBACK GRADIENT ========== --}}
    <div class="container-landing pt-6">
        @if($registration->eventner->header_banner)
            <div class="relative w-full aspect-[21/9] md:aspect-[3/1] rounded-2xl overflow-hidden shadow-md border border-outline-variant/30 bg-black/5">
                <img src="{{ asset('storage/' . $registration->eventner->header_banner) }}" alt="Banner {{ $registration->eventner->nama_event }}" class="w-full h-full object-cover">
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
        <div class="grid gap-6 md:grid-cols-3 items-center">
            {{-- Logo & Title --}}
            <div class="md:col-span-2 flex items-start gap-4">
                @if($registration->eventner->logo_event)
                    <img src="{{ asset('storage/' . $registration->eventner->logo_event) }}" class="h-16 w-16 md:h-20 md:w-20 rounded-2xl object-cover shadow-sm border border-outline-variant/30 shrink-0" alt="{{ $registration->eventner->nama_event }}">
                @else
                    <div class="flex h-16 w-16 md:h-20 md:w-20 items-center justify-center rounded-2xl bg-primary/10 text-primary border border-outline-variant/30 shrink-0">
                        <i class="ti ti-calendar-event text-3xl"></i>
                    </div>
                @endif
                <div class="min-w-0">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-0.5 text-[10px] font-bold uppercase tracking-wider text-primary border border-primary/20 mb-2">
                        <i class="ti ti-link"></i>
                        Portal Pendaftaran Mandiri
                    </span>
                    <h1 class="font-display text-2xl font-extrabold tracking-tight text-deep-slate leading-tight sm:text-3xl">
                        Kelola Pendaftaran Kontingen
                    </h1>
                    <p class="mt-2 text-sm font-semibold text-on-surface-variant">
                        Event: <span class="text-primary font-bold">{{ $registration->eventner->nama_event }}</span>
                    </p>
                </div>
            </div>

            {{-- School Details Box --}}
            <div class="md:col-span-1">
                <div class="surface-card p-4 border border-outline-variant/40 bg-white text-xs font-semibold">
                    <div class="flex justify-between items-center mb-2 pb-2 border-b border-outline-variant/30">
                        <span class="text-on-surface-variant">{{ $this->labelSumber('nama_sekolah') ?? 'Sekolah' }}</span>
                        <span class="text-deep-slate font-bold text-right truncate max-w-[150px]">{{ $registration->display_name }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-on-surface-variant">{{ $this->labelSumber('npsn') ?? \App\Models\RegistrationField::SECTIONS['umum'] }}</span>
                        <span class="text-deep-slate font-bold font-mono text-right">{{ $registration->npsn }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== SHARE LINK BAR ========== --}}
    <div class="container-landing pt-4">
        <div class="max-w-4xl mx-auto">
            <div class="surface-card p-3 flex items-center justify-between gap-3 border border-outline-variant/30">
                <div class="flex items-center gap-2 min-w-0 flex-1">
                    <i class="ti ti-link text-primary shrink-0"></i>
                    <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider hidden sm:inline">Link Portal:</span>
                    <input type="text" value="{{ $portalUrl }}" readonly class="w-full bg-transparent border-0 text-sm font-mono text-deep-slate outline-none truncate min-w-0" onclick="this.select()">
                </div>
                <div x-data="{ copied: false }">
                    <button type="button" x-on:click="navigator.clipboard.writeText('{{ $portalUrl }}').then(() => { copied = true; setTimeout(() => copied = false, 2000); })" class="btn-secondary py-1.5 px-3 text-xs font-bold leading-none whitespace-nowrap flex items-center gap-1.5">
                        <template x-if="!copied"><i class="ti ti-copy"></i></template>
                        <template x-if="copied"><i class="ti ti-check"></i></template>
                        <span x-text="copied ? 'Tersalin' : 'Salin'">Salin</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== MAIN CONTENT PORTAL ========== --}}
    <div class="container-landing py-8">
        <div class="max-w-4xl mx-auto flex flex-col gap-6">

            {{-- Flash Messages --}}
            @if(session()->has('success'))
                <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 text-sm font-semibold flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-circle-check text-lg"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session()->has('error'))
                <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-600 text-sm font-semibold flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-alert-circle text-lg"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            {{-- Booking mode banner --}}
            @if(($registration->eventner->registration_status ?? 'open') == 'booking')
                <div class="p-4 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-600 text-xs font-semibold flex gap-2.5 leading-normal">
                    <i class="ti ti-info-circle text-lg shrink-0 mt-0.5"></i>
                    <div>
                        <p class="font-bold text-blue-800 m-0">Mode Booking Aktif</p>
                        <p class="text-on-surface-variant font-medium mt-1 mb-0">
                            Saat ini pendaftaran hanya diperbolehkan untuk memesan slot (Booking). Pengisian data pasukan akan dibuka oleh panitia setelah masa pendaftaran resmi dibuka.
                        </p>
                    </div>
                </div>
            @endif

            {{-- Status Badge Card --}}
            @php
                $statusConfig = [
                    'booking' => ['bg-amber-500/10', 'text-amber-600', 'border-amber-500/20', 'ti-clock', 'Booking'],
                    'confirmed' => ['bg-blue-500/10', 'text-blue-600', 'border-blue-500/20', 'ti-send', 'Menunggu Verifikasi'],
                    'Terverifikasi' => ['bg-emerald-500/10', 'text-emerald-600', 'border-emerald-500/20', 'ti-circle-check', 'Terverifikasi'],
                    'Ditolak' => ['bg-red-500/10', 'text-red-600', 'border-red-500/20', 'ti-alert-triangle', 'Ditolak — Perlu Revisi'],
                ];
                $sc = $statusConfig[$registration->status_berkas] ?? $statusConfig['booking'];
            @endphp
            <div class="surface-card p-5 border {{ $sc[2] }} flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-full {{ $sc[0] }} {{ $sc[1] }} shrink-0">
                    <i class="ti {{ $sc[3] }} text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-display text-base font-bold text-deep-slate leading-tight mb-1">{{ $sc[4] }}</h3>
                    <p class="text-xs text-on-surface-variant leading-relaxed m-0 font-medium">
                        @if($registration->status_berkas === 'booking')
                            Slot Anda sudah dipesan. Siapkan data dan berkas pasukan sebelum pengisian dibuka oleh panitia.
                        @elseif($registration->status_berkas === 'confirmed')
                            Data telah dikirim ke panitia. Menunggu verifikasi.
                        @elseif($registration->status_berkas === 'Terverifikasi')
                            Pendaftaran Anda telah disetujui panitia. Sampai jumpa di hari perlombaan!
                        @elseif($registration->status_berkas === 'Ditolak')
                            Ada berkas/data yang perlu diperbaiki. Silakan revisi dan kirim ulang.
                        @endif
                    </p>
                </div>
            </div>

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
                                @php $totalDeduction = (float) $deductions->sum('amount'); @endphp
                                @if($deductions->isNotEmpty())
                                    @foreach($deductions as $deduction)
                                        <div class="flex justify-between text-xs font-semibold text-red-600 mb-1 ml-5 mt-2">
                                            <span>Pengurangan {{ $deduction->deductionCriteria?->name ?? 'Nilai' }}</span>
                                            <span>-{{ \App\Support\ScoreOptions::format(abs((float) $deduction->amount)) }}</span>
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

            {{-- Sertifikat tab aktif: juara bergelar juara, pasukan lain PESERTA --}}
            @if($cert = ($this->certificate_registrations[$activeRegId] ?? null))
                @php
                    $isChampion = $cert['is_champion'];
                @endphp
                <div class="surface-card overflow-hidden border-t-4 {{ $isChampion ? 'border-t-amber-400' : 'border-t-slate-300' }}">
                    <div class="p-5 flex flex-col gap-4">
                        <div class="flex items-center gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl shrink-0 {{ $isChampion ? 'bg-amber-500/10 text-amber-600' : 'bg-surface-container text-on-surface-variant' }}">
                                <i class="ti ti-certificate text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-display text-sm font-bold text-deep-slate mb-0.5">{{ $isChampion ? 'Sertifikat Juara' : 'Sertifikat Peserta' }}</h3>
                                <p class="text-xs text-on-surface-variant font-medium leading-normal m-0">
                                    {{ $isChampion
                                        ? 'Selamat! Pasukan ini masuk jajaran juara. Unduh sertifikat dalam format PDF.'
                                        : 'Unduh sertifikat keikutsertaan pasukan ini dalam format PDF.' }}
                                </p>
                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-bold {{ $isChampion ? 'bg-amber-500/10 text-amber-700' : 'bg-surface-container text-on-surface-variant' }}">
                                        <i class="ti {{ $isChampion ? 'ti-trophy' : 'ti-user-check' }}"></i> {{ $cert['title'] }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-md bg-surface-container px-2.5 py-1 text-xs font-semibold text-deep-slate border border-outline-variant/30">
                                        <i class="ti ti-medal"></i> {{ $registration->competitionCategory->full_name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:flex-wrap gap-2">
                            <a href="{{ route('magic.link.certificate.category', [$registration->magic_token, $cert['registration']->competition_category_id]) }}"
                                target="_blank"
                                class="inline-flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-xl text-white font-bold text-sm hover:brightness-105 transition-colors text-decoration-none {{ $isChampion ? 'bg-amber-500' : 'bg-primary' }}">
                                <i class="ti ti-file-download"></i> Unduh Sertifikat
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Team Tabs (if multiple registrations exist) --}}
            @if($siblingRegistrations->count() > 1)
                <div class="flex gap-2 overflow-x-auto pb-2">
                    @foreach($siblingRegistrations as $sib)
                        <button wire:click="switchRegistration({{ $sib->id }})"
                            class="whitespace-nowrap py-2.5 px-4 rounded-xl text-xs font-bold transition flex items-center gap-1.5 border-none outline-none cursor-pointer {{ $activeRegId == $sib->id ? 'bg-primary text-white shadow-sm' : 'bg-surface-container text-on-surface-variant hover:text-primary' }}">
                            <i class="ti ti-users-group"></i> {{ $sib->competitionCategory->full_name }}
                            @if($sib->participants->count() > 0)
                                <span class="inline-block py-0.5 px-1.5 rounded-full text-[10px] ml-1 font-bold {{ $activeRegId == $sib->id ? 'bg-white text-primary' : 'bg-outline-variant/30 text-on-surface-variant' }}">{{ $sib->participants->count() }}</span>
                            @endif
                        </button>
                    @endforeach
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
                                    <a href="{{ route('magic.link.invoice', $registration->magic_token) }}" target="_blank"
                                        class="mt-3 inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition-colors">
                                        <i class="ti ti-file-invoice"></i> Unduh Invoice (PDF)
                                    </a>
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
                {{-- Verified Data Table View (Locked/Read Only) --}}
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
                                    {{-- Kartu pelatih & danton di atas sudah menampilkan
                                         section-nya; di sini hanya isian umum. Berkas
                                         sudah punya barisnya sendiri di atas. --}}
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
                    <p class="text-xs font-semibold text-on-surface-variant mt-2 leading-normal">Data di atas adalah data resmi yang dikunci dan akan digunakan pada saat perlombaan.</p>
                    <a href="{{ route('magic.link.formulir', $registration->magic_token) }}" target="_blank" class="btn-primary inline-flex items-center justify-center gap-1.5 mt-4 px-6 py-3 font-bold text-sm cursor-pointer">
                        <i class="ti ti-file-download"></i> Unduh Formulir Pendaftaran
                    </a>
                </div>
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
                                                    <textarea wire:model="{{ $kunci }}" rows="3" class="field-input w-full" placeholder="{{ $field->help_text ?: $field->label }}">{{ $nilai }}</textarea>
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
                            <div class="flex flex-wrap gap-3 justify-center mt-8">
                                <button type="submit" class="btn-ghost py-3.5 px-6 font-bold text-sm inline-flex items-center gap-1.5 cursor-pointer shadow-sm">
                                    <i class="ti ti-save"></i> Simpan Draft
                                </button>

                                @if($registration->status_berkas === 'booking' && ($regStatus === 'open' || !$registration->eventner->technical_meeting || now()->gte($registration->eventner->technical_meeting)))
                                    <button type="button" onclick="confirmAction()" class="btn-primary !bg-emerald-500 hover:!bg-emerald-600 !text-white border-none py-3.5 px-6 font-bold text-sm inline-flex items-center gap-1.5 cursor-pointer shadow-md hover:shadow-lg">
                                        <i class="ti ti-circle-check"></i> Konfirmasi &amp; Kirim
                                    </button>
                                @elseif($registration->status_berkas !== 'booking')
                                    <button type="button" onclick="confirmFinalization()" class="btn-primary py-3.5 px-6 font-bold text-sm inline-flex items-center gap-1.5 cursor-pointer shadow-md hover:shadow-lg">
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
