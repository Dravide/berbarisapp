<div class="min-h-screen bg-surface" x-data="{ showMobileForm: false }">

    {{-- ========== HERO ========== --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-primary via-[#0053da] to-tertiary text-white py-12 md:py-16">
        <div class="absolute -left-20 -top-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
        <div class="absolute -right-20 -bottom-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>

        <div class="container-landing relative z-10 flex flex-col items-center text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-white backdrop-blur-md border border-white/10 mb-3">
                <i class="ti ti-heart-filled text-emerald-400"></i>
                Voting Digital
            </span>
            <h1 class="font-display text-2xl font-extrabold tracking-tight sm:text-3xl md:text-4xl max-w-4xl leading-tight">
                Dukung Tim Jagoan Anda!
            </h1>
            <p class="mt-2.5 text-xs font-medium text-white/80 md:text-sm max-w-xl">
                Setiap vote sangat berarti untuk menentukan juara terfavorit di event <strong class="text-secondary font-semibold">{{ $eventner->nama_event }}</strong>.
            </p>
            <div class="mt-4">
                <a href="{{ route('event.detail', $eventner->slug) }}" class="btn-ghost !border-white/20 !text-white hover:!bg-white/10 text-xs py-2 px-4 leading-normal inline-flex items-center gap-1.5 text-decoration-none">
                    <i class="ti ti-arrow-left"></i> Kembali Ke Detail Event
                </a>
            </div>
        </div>
    </div>

    {{-- ========== MAIN CONTENT ========== --}}
    <div class="container-landing py-8">
        @if (session()->has('error'))
            <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-600 text-sm font-semibold flex items-center gap-2">
                <i class="ti ti-alert-circle text-lg"></i>
                {{ session('error') }}
            </div>
        @endif

        {{-- ========== VIEW: PAYMENT (QRIS) ========== --}}
        @if($view === 'payment')
            <div class="flex justify-center" wire:poll.5s="checkPaymentStatus">
                <div class="w-full max-w-md">
                    <div class="surface-card overflow-hidden">
                        {{-- Payment Header --}}
                        <div class="bg-primary text-white p-6 text-center relative overflow-hidden">
                            <div class="absolute -right-8 -top-8 h-24 w-24 rounded-full bg-white/5 blur-xl"></div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/20 text-white shadow-sm mb-3 mx-auto">
                                <i class="ti ti-qrcode text-2xl"></i>
                            </div>
                            <h3 class="font-display text-base font-bold text-white mb-0.5">Scan &amp; Bayar</h3>
                            <p class="text-xs text-white/80">Scan QR Code di bawah menggunakan aplikasi e-wallet Anda</p>
                        </div>

                        {{-- Payment Body --}}
                        <div class="p-6 text-center">
                            {{-- QR Code Image Box --}}
                            <div class="inline-block bg-white border-2 border-outline-variant/60 rounded-2xl p-4 shadow-sm mb-6">
                                <img src="{{ $qrImageUrl }}" alt="QRIS Payment" class="max-w-[220px] w-full mx-auto">
                            </div>

                            {{-- Payment Amount --}}
                            <div class="bg-emerald-500/5 border border-emerald-500/10 rounded-xl p-4 mb-4">
                                <span class="text-xs text-on-surface-variant font-medium block mb-1">Total Pembayaran</span>
                                <h2 class="text-2xl font-extrabold text-emerald-600 leading-tight">Rp {{ number_format($paymentAmount, 0, ',', '.') }}</h2>
                                <span class="text-[11px] text-on-surface-variant font-medium block mt-1">{{ $voteCount }} transaksi × Rp {{ number_format($eventner->vote_price ?? 1000, 0, ',', '.') }}</span>
                                @if($this->activeBooster)
                                <span class="text-[10px] text-amber-600 font-bold block mt-1">
                                    <i class="ti ti-bolt"></i> Booster {{ $this->activeBooster->vote_multiplier }}x aktif! = <strong>{{ $voteCount * $this->activeBooster->vote_multiplier }} vote</strong>
                                </span>
                                @endif
                            </div>

                            {{-- Timer --}}
                            <div class="mb-4" x-data="{
                                expiry: '{{ $expiryTime }}',
                                remaining: '',
                                expired: false,
                                init() {
                                    this.updateTimer();
                                    setInterval(() => this.updateTimer(), 1000);
                                },
                                updateTimer() {
                                    const exp = new Date(this.expiry).getTime();
                                    const now = Date.now();
                                    const diff = exp - now;
                                    if (diff <= 0) {
                                        this.remaining = '00:00';
                                        this.expired = true;
                                        return;
                                    }
                                    const m = Math.floor(diff / 60000);
                                    const s = Math.floor((diff % 60000) / 1000);
                                    this.remaining = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
                                }
                            }">
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-500/10 border border-amber-500/20 rounded-full text-xs font-bold text-amber-600">
                                    <i class="ti ti-clock"></i>
                                    Kedaluwarsa dalam: <span x-text="remaining" class="font-mono text-sm leading-none"></span>
                                </div>
                            </div>

                            {{-- Waiting Status --}}
                            <div class="flex items-center justify-center gap-2 py-3 px-4 bg-amber-500/5 border border-amber-500/15 rounded-xl mb-6">
                                <span class="h-4 w-4 border-2 border-amber-500 border-t-transparent rounded-full animate-spin shrink-0"></span>
                                <span class="text-amber-800 text-xs font-semibold">Menunggu Pembayaran...</span>
                            </div>

                            {{-- Instructions --}}
                            <div class="text-left bg-surface-container-low border border-outline-variant/40 rounded-xl p-4 mb-4">
                                <span class="text-xs font-bold text-deep-slate inline-flex items-center gap-1 mb-2">
                                    <i class="ti ti-info-circle text-emerald-600"></i> Cara Bayar:
                                </span>
                                <ol class="list-decimal pl-4 text-xs text-on-surface-variant space-y-1.5 leading-relaxed">
                                    <li>Buka aplikasi e-wallet (GoPay, OVO, DANA, dll) atau M-Banking</li>
                                    <li>Pilih menu <strong>Scan QRIS / Bayar</strong></li>
                                    <li>Scan QR Code di atas</li>
                                    <li>Periksa nominal pembayaran sudah sesuai dan konfirmasi</li>
                                </ol>
                            </div>

                            {{-- Cancel Button --}}
                            <button wire:click="resetPayment" class="text-xs font-bold text-on-surface-variant hover:text-red-500 transition inline-flex items-center gap-1 bg-transparent border-none cursor-pointer">
                                <i class="ti ti-arrow-left"></i> Batal &amp; Kembali
                            </button>

                            {{-- QRIS Logo --}}
                            <div class="mt-6 border-t border-outline-variant/30 pt-4 flex justify-center">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/a/a2/Logo_QRIS.svg" class="h-5 opacity-60" alt="QRIS">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        {{-- ========== VIEW: SUCCESS ========== --}}
        @elseif($view === 'success')
            <div class="flex justify-center">
                <div class="w-full max-w-md">
                    <div class="surface-card overflow-hidden">
                        {{-- Success Header --}}
                        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white p-8 text-center relative overflow-hidden">
                            <div class="absolute -right-8 -top-8 h-24 w-24 rounded-full bg-white/5 blur-xl"></div>
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/20 text-white shadow-sm mb-3 mx-auto">
                                <i class="ti ti-heart-filled text-3xl text-white"></i>
                            </div>
                            <h3 class="font-display text-lg font-bold text-white mb-0.5">Dukungan Berhasil!</h3>
                            <p class="text-sm text-white/80">Terima kasih atas partisipasi Anda</p>
                        </div>

                        {{-- Success Body --}}
                        <div class="p-6 text-center">
                            <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-5 mb-6 text-sm text-emerald-800">
                                <strong>{{ $voteCount }} vote</strong> untuk tim pilihan Anda telah berhasil ditambahkan dan dihitung di sistem real-time kami.
                            </div>
                            <a href="{{ route('event.detail', $eventner->slug) }}" class="btn-secondary py-3.5 px-6 font-bold text-sm w-full text-center text-decoration-none">
                                <i class="ti ti-arrow-left"></i> Kembali Ke Detail Event
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        {{-- ========== VIEW: NORMAL (CATEGORIES/PARTICIPANTS) ========== --}}
        @else
            @if(!$eventner->vote_active)
                <div class="mb-6 p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-800 text-sm font-semibold flex items-center gap-2">
                    <i class="ti ti-lock text-lg"></i>
                    <span><strong>Voting Ditutup.</strong> Layanan voting berbayar telah berakhir. Berikut hasil akhir voting.</span>
                </div>
            @endif

            <div class="grid gap-8 lg:grid-cols-12">
                {{-- Left: Selection Area --}}
                <div class="{{ $eventner->vote_active ? 'lg:col-span-8' : 'lg:col-span-12' }}">
                    @if($view == 'categories')
                        {{-- VIEW A: Categories selection --}}
                        <div class="mb-6">
                            <h2 class="font-display text-lg font-bold text-deep-slate inline-flex items-center gap-2">
                                <i class="ti ti-medal text-primary text-xl"></i>
                                Pilih Kategori Lomba
                            </h2>
                        </div>

                        <div class="grid gap-4 grid-cols-2 md:grid-cols-3">
                            @foreach($categories as $cat)
                                <div wire:click="selectCategory({{ $cat->id }})"
                                     class="surface-card surface-card-hover p-5 text-center cursor-pointer border border-outline-variant/50 flex flex-col items-center justify-center">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary mb-3">
                                        <i class="ti ti-trophy text-2xl"></i>
                                    </div>
                                    <h4 class="font-display text-sm font-bold text-deep-slate leading-tight mb-1 text-center truncate max-w-full" title="{{ $cat->name }}">{{ $cat->name }}</h4>
                                    <span class="text-xs text-on-surface-variant font-medium block mb-4">{{ $cat->registrations_count }} Kontingen</span>
                                    <span class="btn-ghost py-1.5 px-4 text-xs font-semibold leading-normal inline-flex items-center gap-1.5">
                                        Pilih Kategori <i class="ti ti-arrow-right text-[10px]"></i>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- VIEW B: Participants list --}}
                        @php
                            $top3 = collect();
                            $remainingParticipants = $participants;
                            if (!$search && $participants->count() > 0) {
                                $top3 = $participants->take(3);
                                $remainingParticipants = $participants->slice(3);
                            }
                        @endphp

                        <div class="flex items-center gap-3 mb-6">
                            <button wire:click="backToCategories" class="flex h-10 w-10 items-center justify-center rounded-xl border border-outline-variant/60 bg-white hover:bg-primary/5 transition text-on-surface-variant cursor-pointer">
                                <i class="ti ti-arrow-left text-lg"></i>
                            </button>
                            <nav class="text-sm font-medium text-on-surface-variant flex items-center gap-1.5">
                                <span>Kategori</span>
                                <span class="text-outline-variant">/</span>
                                <strong class="text-primary font-bold">{{ $selectedCategory->name }}</strong>
                            </nav>
                        </div>

                        {{-- ========== VOTE LEADERBOARD PODIUM (Juara 1-3) ========== --}}
                        @if($top3->isNotEmpty())
                            @php
                                $rank1 = $top3->values()->get(0);
                                $rank2 = $top3->values()->get(1);
                                $rank3 = $top3->values()->get(2);
                            @endphp
                            <div class="surface-card mb-8 border border-outline-variant/50 bg-white overflow-hidden">
                                <span class="overline text-center justify-center pt-6 pb-4">Pimpinan Klasemen</span>

                                <div class="flex items-end justify-center gap-3 sm:gap-6 max-w-lg mx-auto px-6 pb-4">

                                    {{-- 2nd Place --}}
                                    <div class="flex flex-col items-center flex-1 max-w-[140px]">
                                        @if($rank2)
                                            <div wire:click="selectTeam({{ $rank2->id }})" class="flex flex-col items-center cursor-pointer group w-full">
                                                @if($rank2->logo_sekolah)
                                                    <img src="{{ asset('storage/' . $rank2->logo_sekolah) }}" alt="" class="h-14 w-14 sm:h-16 sm:w-16 rounded-full object-cover border-2 border-slate-300 shadow-md mb-2 transition group-hover:scale-105 {{ $selectedRegistrationId == $rank2->id ? 'ring-4 ring-primary ring-offset-2' : '' }}">
                                                @else
                                                    <div class="flex h-14 w-14 sm:h-16 sm:w-16 items-center justify-center rounded-full bg-slate-100 text-slate-500 border-2 border-slate-300 shadow-md mb-2 {{ $selectedRegistrationId == $rank2->id ? 'ring-4 ring-primary ring-offset-2' : '' }}">
                                                        <i class="ti ti-school text-2xl"></i>
                                                    </div>
                                                @endif
                                                <h4 class="text-xs sm:text-sm font-bold text-deep-slate text-center leading-tight mb-1 line-clamp-2 transition group-hover:text-primary">{{ $rank2->nama_sekolah }}</h4>
                                                <span class="font-display font-extrabold text-primary text-sm mb-1">{{ number_format($rank2->total_votes ?? 0, 0, ',', '.') }}</span>
                                                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Vote</span>
                                                <div class="w-full bg-gradient-to-t from-slate-200 to-slate-100 border border-slate-200/80 rounded-t-xl mt-3 flex items-center justify-center transition group-hover:shadow-md" style="height: 80px;">
                                                    <span class="font-display text-3xl font-extrabold text-slate-400">2</span>
                                                </div>
                                            </div>
                                        @else
                                            <div class="w-full bg-slate-50 border border-slate-200/50 rounded-t-xl" style="height: 80px;"></div>
                                        @endif
                                    </div>

                                    {{-- 1st Place --}}
                                    <div class="flex flex-col items-center flex-1 max-w-[160px]">
                                        @if($rank1)
                                            <div wire:click="selectTeam({{ $rank1->id }})" class="flex flex-col items-center cursor-pointer group w-full">
                                                <div class="relative mb-2">
                                                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 z-10">
                                                        <i class="ti ti-crown-filled text-amber-400 text-2xl drop-shadow-sm"></i>
                                                    </div>
                                                    @if($rank1->logo_sekolah)
                                                        <img src="{{ asset('storage/' . $rank1->logo_sekolah) }}" alt="" class="h-18 w-18 sm:h-20 sm:w-20 rounded-full object-cover border-3 border-amber-400 shadow-lg ring-4 ring-amber-400/20 transition group-hover:scale-105 {{ $selectedRegistrationId == $rank1->id ? 'ring-4 ring-primary ring-offset-2' : '' }}">
                                                    @else
                                                        <div class="flex h-18 w-18 sm:h-20 sm:w-20 items-center justify-center rounded-full bg-amber-50 text-amber-500 border-3 border-amber-400 shadow-lg ring-4 ring-amber-400/20 {{ $selectedRegistrationId == $rank1->id ? 'ring-4 ring-primary ring-offset-2' : '' }}">
                                                            <i class="ti ti-school text-3xl"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <h4 class="text-xs sm:text-sm font-bold text-deep-slate text-center leading-tight mb-1 line-clamp-2 transition group-hover:text-primary">{{ $rank1->nama_sekolah }}</h4>
                                                <span class="font-display font-extrabold text-primary text-base mb-1">{{ number_format($rank1->total_votes ?? 0, 0, ',', '.') }}</span>
                                                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Vote</span>
                                                <div class="w-full bg-gradient-to-t from-amber-300 to-amber-200 border border-amber-300/80 rounded-t-xl mt-3 flex items-center justify-center transition group-hover:shadow-md" style="height: 110px;">
                                                    <span class="font-display text-4xl font-extrabold text-amber-500/80">1</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- 3rd Place --}}
                                    <div class="flex flex-col items-center flex-1 max-w-[140px]">
                                        @if($rank3)
                                            <div wire:click="selectTeam({{ $rank3->id }})" class="flex flex-col items-center cursor-pointer group w-full">
                                                @if($rank3->logo_sekolah)
                                                    <img src="{{ asset('storage/' . $rank3->logo_sekolah) }}" alt="" class="h-14 w-14 sm:h-16 sm:w-16 rounded-full object-cover border-2 border-sky-300 shadow-md mb-2 transition group-hover:scale-105 {{ $selectedRegistrationId == $rank3->id ? 'ring-4 ring-primary ring-offset-2' : '' }}">
                                                @else
                                                    <div class="flex h-14 w-14 sm:h-16 sm:w-16 items-center justify-center rounded-full bg-sky-50 text-sky-500 border-2 border-sky-300 shadow-md mb-2 {{ $selectedRegistrationId == $rank3->id ? 'ring-4 ring-primary ring-offset-2' : '' }}">
                                                        <i class="ti ti-school text-2xl"></i>
                                                    </div>
                                                @endif
                                                <h4 class="text-xs sm:text-sm font-bold text-deep-slate text-center leading-tight mb-1 line-clamp-2 transition group-hover:text-primary">{{ $rank3->nama_sekolah }}</h4>
                                                <span class="font-display font-extrabold text-primary text-sm mb-1">{{ number_format($rank3->total_votes ?? 0, 0, ',', '.') }}</span>
                                                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Vote</span>
                                                <div class="w-full bg-gradient-to-t from-sky-200 to-sky-100 border border-sky-200/80 rounded-t-xl mt-3 flex items-center justify-center transition group-hover:shadow-md" style="height: 60px;">
                                                    <span class="font-display text-3xl font-extrabold text-sky-400/80">3</span>
                                                </div>
                                            </div>
                                        @else
                                            <div class="w-full bg-sky-50 border border-sky-200/50 rounded-t-xl" style="height: 60px;"></div>
                                        @endif
                                    </div>

                                </div>
                            </div>
                        @endif

                        {{-- Search Input --}}
                        <div class="mb-6 relative">
                            <i class="ti ti-search absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg"></i>
                            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama sekolah / kontingen..." class="field-input w-full pl-11">
                        </div>

                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-display text-base font-bold text-deep-slate">Pilih Kontingen</h3>
                            <span class="chip py-0.5 px-2.5 text-xs font-bold leading-normal">{{ $participants->count() }} kontingen</span>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            @forelse($remainingParticipants as $reg)
                                @php $rank = $search ? ($loop->index + 1) : ($loop->index + 4); @endphp
                                @if($eventner->vote_active)
                                    <div wire:click="selectTeam({{ $reg->id }})"
                                         class="surface-card cursor-pointer p-4 transition duration-200 {{ $selectedRegistrationId == $reg->id ? 'ring-2 ring-primary bg-primary/5 border-transparent shadow-md' : 'border-outline-variant/50 hover:-translate-y-0.5 hover:shadow-sm' }}">
                                @else
                                    <div class="surface-card p-4 border-outline-variant/50">
                                @endif
                                    <div class="flex items-center gap-3.5">
                                        <div class="relative shrink-0">
                                            @if($reg->logo_sekolah)
                                                <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" alt="" class="h-11 w-11 rounded-full object-cover border border-outline-variant/30">
                                            @else
                                                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-primary/5 text-primary border border-outline-variant/30">
                                                    <i class="ti ti-school text-xl"></i>
                                                </div>
                                            @endif
                                            @if($selectedRegistrationId == $reg->id)
                                                <span class="absolute -top-1 -right-1 h-5 w-5 bg-primary text-white border-2 border-white rounded-full flex items-center justify-center shadow-sm">
                                                    <i class="ti ti-check text-[9px] font-bold"></i>
                                                </span>
                                            @else
                                                @if($rank <= 3)
                                                    <span class="absolute -top-1 -right-1 h-5 w-5 rounded-full flex items-center justify-center text-[9px] font-bold text-white shadow-sm border-2 border-white" style="background-color: {{ $rank === 1 ? '#f59e0b' : ($rank === 2 ? '#94a3b8' : '#cd7f32') }}">
                                                        {{ $rank }}
                                                    </span>
                                                @else
                                                    <span class="absolute -top-1 -right-1 h-5 w-5 rounded-full flex items-center justify-center text-[9px] font-bold text-on-surface-variant bg-surface-container border border-outline-variant/30 shadow-sm">
                                                        {{ $rank }}
                                                    </span>
                                                @endif
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-bold text-deep-slate leading-tight mb-0.5 truncate">{{ $reg->nama_sekolah }}</h4>
                                            <span class="text-xs text-on-surface-variant block truncate">Pelatih: {{ $reg->nama_pelatih ?? '—' }}</span>
                                        </div>
                                        <span class="chip py-1 px-3 !text-[11px] shrink-0 inline-flex items-center gap-1 bg-amber-500/10 !text-amber-700 font-bold" title="Total Vote Terkumpul">
                                            <i class="ti ti-heart-filled text-xs text-amber-600"></i> {{ number_format($reg->total_votes ?? 0, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-full text-center py-10 bg-white border border-outline-variant/40 rounded-2xl">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/5 text-primary mx-auto mb-3">
                                        <i class="ti ti-search text-xl"></i>
                                    </div>
                                    <p class="text-sm font-semibold text-on-surface-variant mb-2">Tidak ada kontingen ditemukan.</p>
                                    <button wire:click="$set('search', '')" class="text-xs font-bold text-primary bg-transparent border-none cursor-pointer hover:underline">Hapus Pencarian</button>
                                </div>
                            @endforelse
                        </div>

                        {{-- Mobile-only inline form --}}
                        @if($eventner->vote_active && $selectedRegistrationId)
                            <div class="lg:hidden mt-8 surface-card overflow-hidden" id="mobile-vote-form">
                                @include('livewire.public.partials._vote-form')
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Right: Sticky Sidebar Voting Form (Desktop Only) --}}
                @if($eventner->vote_active)
                    <div class="hidden lg:block lg:col-span-4">
                        <div class="surface-card overflow-hidden sticky top-24">
                            @include('livewire.public.partials._vote-form')
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- ========== STICKY BOTTOM FORM (Mobile Only) ========== --}}
    @if($view !== 'payment' && $view !== 'success' && $eventner->vote_active)
        <div class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-outline-variant/50 p-4 shadow-[0_-4px_24px_rgba(0,0,0,0.06)]" style="padding-bottom: calc(1rem + env(safe-area-inset-bottom));">
            @if($selectedRegistrationId)
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <span class="text-[10px] text-on-surface-variant font-bold uppercase tracking-wider block">Dukungan Untuk</span>
                        <h4 class="text-sm font-bold text-deep-slate truncate">{{ App\Models\Registration::find($selectedRegistrationId)?->nama_sekolah }}</h4>
                    </div>
                    <a href="#mobile-vote-form" class="btn-primary py-2.5 px-5 font-bold text-xs leading-normal shrink-0 text-decoration-none">
                        Isi Formulir Vote
                    </a>
                </div>
            @else
                <div class="text-center py-1">
                    <p class="text-xs font-semibold text-on-surface-variant inline-flex items-center gap-1.5 m-0">
                        <i class="ti ti-hand-finger text-primary"></i> Pilih kontingen terlebih dahulu untuk mulai voting
                    </p>
                </div>
            @endif
        </div>
    @endif

</div>
