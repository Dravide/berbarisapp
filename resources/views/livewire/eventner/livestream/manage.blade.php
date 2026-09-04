<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h4 class="text-lg font-semibold text-deep-slate inline-flex items-center gap-2 mb-1">
                <i class="ti ti-video text-primary"></i> Livestream Overlay
            </h4>
            <p class="text-xs text-on-surface-variant mb-0">Kelola overlay untuk live streaming event Anda (1920×1080).</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center gap-2 py-3 px-4 mb-4" role="alert">
            <i class="ti ti-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- ========== PRESET MODES ========== --}}
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-5">
            <h5 class="fs-6 fw-bold text-deep-slate mb-1">Mode Preset</h5>
            <p class="text-xs text-on-surface-variant mb-4">Klik untuk membuka overlay publik di tab baru.</p>

            <div class="row g-3">
                @php $slug = $eventner->slug; @endphp

                <div class="col-6 col-lg-3">
                    <a href="{{ event_url($eventner, 'overlay') }}?mode=full" target="_blank"
                       class="d-block p-4 rounded-2xl text-center text-decoration-none transition"
                       style="background: #f0f4ff; border: 2px solid #dbe4f0;"
                       onmouseover="this.style.borderColor='#0062ff';this.style.background='#e8f0ff'"
                       onmouseout="this.style.borderColor='#dbe4f0';this.style.background='#f0f4ff'">
                        <span class="d-inline-flex align-items-center justify-center" style="width: 48px; height: 48px; border-radius: 14px; background: #0062ff15; color: #0062ff; font-size: 24px; margin-bottom: 8px;">
                            <i class="ti ti-layout"></i>
                        </span>
                        <h6 class="fw-bold text-deep-slate mb-0" style="font-size: 13px;">Full</h6>
                        <span class="text-xs text-on-surface-variant">Chroma + Side</span>
                    </a>
                </div>

                <div class="col-6 col-lg-3">
                    <a href="{{ event_url($eventner, 'overlay') }}?mode=vote" target="_blank"
                       class="d-block p-4 rounded-2xl text-center text-decoration-none transition"
                       style="background: #fef9f0; border: 2px solid #f5e6d0;"
                       onmouseover="this.style.borderColor='#f59e0b';this.style.background='#fff3e0'"
                       onmouseout="this.style.borderColor='#f5e6d0';this.style.background='#fef9f0'">
                        <span class="d-inline-flex align-items-center justify-center" style="width: 48px; height: 48px; border-radius: 14px; background: #f59e0b15; color: #f59e0b; font-size: 24px; margin-bottom: 8px;">
                            <i class="ti ti-heart-filled"></i>
                        </span>
                        <h6 class="fw-bold text-deep-slate mb-0" style="font-size: 13px;">Vote</h6>
                        <span class="text-xs text-on-surface-variant">Leaderboard</span>
                    </a>
                </div>

                <div class="col-6 col-lg-3">
                    <a href="{{ event_url($eventner, 'overlay') }}?mode=comments" target="_blank"
                       class="d-block p-4 rounded-2xl text-center text-decoration-none transition"
                       style="background: #fdf0f4; border: 2px solid #f0d0dd;"
                       onmouseover="this.style.borderColor='#ec4899';this.style.background='#fce4ef'"
                       onmouseout="this.style.borderColor='#f0d0dd';this.style.background='#fdf0f4'">
                        <span class="d-inline-flex align-items-center justify-center" style="width: 48px; height: 48px; border-radius: 14px; background: #ec489915; color: #ec4899; font-size: 24px; margin-bottom: 8px;">
                            <i class="ti ti-message-chatbot"></i>
                        </span>
                        <h6 class="fw-bold text-deep-slate mb-0" style="font-size: 13px;">Komentar</h6>
                        <span class="text-xs text-on-surface-variant">Komentar Vote Only</span>
                    </a>
                </div>

                <div class="col-6 col-lg-3">
                    <a href="{{ event_url($eventner, 'overlay') }}?mode=kegiatan" target="_blank"
                       class="d-block p-4 rounded-2xl text-center text-decoration-none transition"
                       style="background: #f5fdf5; border: 2px solid #d0e6d0;"
                       onmouseover="this.style.borderColor='#22c55e';this.style.background='#eafaea'"
                       onmouseout="this.style.borderColor='#d0e6d0';this.style.background='#f5fdf5'">
                        <span class="d-inline-flex align-items-center justify-center" style="width: 48px; height: 48px; border-radius: 14px; background: #22c55e15; color: #22c55e; font-size: 24px; margin-bottom: 8px;">
                            <i class="ti ti-calendar-stats"></i>
                        </span>
                        <h6 class="fw-bold text-deep-slate mb-0" style="font-size: 13px;">Kegiatan</h6>
                        <span class="text-xs text-on-surface-variant">Data Kategori</span>
                    </a>
                </div>

                <div class="col-6 col-lg-3">
                    <a href="{{ event_url($eventner, 'overlay') }}?mode=greenscreen" target="_blank"
                       class="d-block p-4 rounded-2xl text-center text-decoration-none transition"
                       style="background: #f0faf0; border: 2px solid #c0e0c0;"
                       onmouseover="this.style.borderColor='#00FF00';this.style.background='#e0fae0'"
                       onmouseout="this.style.borderColor='#c0e0c0';this.style.background='#f0faf0'">
                        <span class="d-inline-flex align-items-center justify-center" style="width: 48px; height: 48px; border-radius: 14px; background: #00FF0015; color: #00cc00; font-size: 24px; margin-bottom: 8px;">
                            <i class="ti ti-color-picker"></i>
                        </span>
                        <h6 class="fw-bold text-deep-slate mb-0" style="font-size: 13px;">Greenscreen</h6>
                        <span class="text-xs text-on-surface-variant">Chroma Key Only</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== PER KATEGORI OVERLAYS ========== --}}
    @if($categories->isNotEmpty())
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-5">
            <h5 class="fs-6 fw-bold text-deep-slate mb-1">Overlay Per Kategori</h5>
            <p class="text-xs text-on-surface-variant mb-4">Rank vote + komentar, scoped ke satu kategori — cocok untuk OBS per segmen lomba.</p>

            <div class="row g-3">
                @foreach($categories as $cat)
                    <div class="col-6 col-lg-3">
                        <a href="{{ event_url($eventner, 'overlay') }}?mode=category&selectedCategoryId={{ $cat->id }}" target="_blank"
                           class="d-block p-4 rounded-2xl text-decoration-none transition"
                           style="background: #f0f4f8; border: 2px solid #d0dae4;"
                           onmouseover="this.style.borderColor='#6366f1';this.style.background='#e8ecf4'"
                           onmouseout="this.style.borderColor='#d0dae4';this.style.background='#f0f4f8'">
                            <span class="d-inline-flex align-items-center justify-center" style="width: 48px; height: 48px; border-radius: 14px; background: #6366f115; color: #6366f1; font-size: 24px; margin-bottom: 8px;">
                                <i class="ti ti-award"></i>
                            </span>
                            <h6 class="fw-bold text-deep-slate mb-0 text-truncate" style="font-size: 13px;">
                                {{ $cat->parent ? $cat->parent->name . ' — ' : '' }}{{ $cat->name }}
                            </h6>
                            <span class="text-xs text-on-surface-variant">{{ $cat->registrations_count }} kontingen</span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- ========== CUSTOM OVERLAY SETTINGS ========== --}}
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-5">
            <h5 class="fs-6 fw-bold text-deep-slate mb-1">Kustom Overlay</h5>
            <p class="text-xs text-on-surface-variant mb-4">Atur komponen yang tampil di overlay kustom Anda.</p>

            <form wire:submit="saveSettings">
                <div class="d-flex flex-column gap-4">
                    {{-- Components Toggle --}}
                    <div class="d-flex flex-column gap-3">
                        <label class="d-flex align-items-center gap-3 p-3 rounded-xl border" style="border-color: #e5e7eb;">
                            <input type="checkbox" wire:model="show_header" class="form-check-input" style="width: 20px; height: 20px;">
                            <div>
                                <span class="fw-semibold text-deep-slate" style="font-size: 14px;">Header</span>
                                <p class="text-xs text-on-surface-variant mb-0">Logo event, nama, jam, dan badge LIVE</p>
                            </div>
                        </label>

                        <label class="d-flex align-items-center gap-3 p-3 rounded-xl border" style="border-color: #e5e7eb;">
                            <input type="checkbox" wire:model="show_vote_leaderboard" class="form-check-input" style="width: 20px; height: 20px;">
                            <div>
                                <span class="fw-semibold text-deep-slate" style="font-size: 14px;">Vote Leaderboard</span>
                                <p class="text-xs text-on-surface-variant mb-0">Podium juara + peringkat kontingen</p>
                            </div>
                        </label>

                        <label class="d-flex align-items-center gap-3 p-3 rounded-xl border" style="border-color: #e5e7eb;">
                            <input type="checkbox" wire:model="show_kegiatan" class="form-check-input" style="width: 20px; height: 20px;">
                            <div>
                                <span class="fw-semibold text-deep-slate" style="font-size: 14px;">Data Kegiatan</span>
                                <p class="text-xs text-on-surface-variant mb-0">Daftar kategori lomba dan jumlah kontingen</p>
                            </div>
                        </label>

                        <label class="d-flex align-items-center gap-3 p-3 rounded-xl border" style="border-color: #e5e7eb;">
                            <input type="checkbox" wire:model="show_footer" class="form-check-input" style="width: 20px; height: 20px;">
                            <div>
                                <span class="fw-semibold text-deep-slate" style="font-size: 14px;">Footer</span>
                                <p class="text-xs text-on-surface-variant mb-0">Powered by BARIS APP</p>
                            </div>
                        </label>

                        <div class="p-3 rounded-xl border" style="border-color: #e5e7eb;">
                            <label class="fw-semibold text-deep-slate mb-1" style="font-size: 14px;">Teks Berjalan (Marquee)</label>
                            <p class="text-xs text-on-surface-variant mb-2">Teks yang berjalan di bagian bawah overlay.</p>
                            <input type="text" wire:model="marquee_text" class="form-control form-control-sm" placeholder="Contoh: Selamat datang di acara kami!" style="max-width: 500px;">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2.5 mt-4"
                        style="font-weight: 600; font-size: 14px; border-radius: 12px;">
                    <i class="ti ti-device-floppy"></i> Simpan Pengaturan
                </button>
            </form>
        </div>
    </div>

    {{-- ========== URL PREVIEW + COPY ========== --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-5">
            <h5 class="fs-6 fw-bold text-deep-slate mb-1">URL Overlay</h5>
            <p class="text-xs text-on-surface-variant mb-4">Gunakan URL ini di OBS (Browser Source, 1920×1080).</p>

            @php
                $overlayUrl = event_url($eventner, 'overlay') . '?mode=custom';
            @endphp

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <code class="flex-fill p-3 rounded-xl bg-light d-inline-block" style="font-size: 12px; word-break: break-all; max-width: 700px;">
                    {{ $overlayUrl }}
                </code>
                <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 px-4"
                        style="border-radius: 12px; font-weight: 600; font-size: 13px; border-color: #dbe4f0;"
                        onclick="navigator.clipboard.writeText('{{ $overlayUrl }}'); this.innerHTML='<i class=\'ti ti-check\'></i> Tersalin!'">
                    <i class="ti ti-copy"></i> Salin URL
                </button>
                <a href="{{ $overlayUrl }}" target="_blank" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4"
                   style="border-radius: 12px; font-weight: 600; font-size: 13px;">
                    <i class="ti ti-external-link"></i> Buka
                </a>
            </div>

            {{-- Stats Preview --}}
            <div class="row g-3 mt-4">
                <div class="col-4">
                    <div class="p-3 rounded-xl bg-light text-center">
                        <div class="fw-bold text-primary" style="font-size: 24px;">{{ number_format($totalVoteCount, 0, ',', '.') }}</div>
                        <span class="text-xs text-on-surface-variant">Total Vote</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-3 rounded-xl bg-light text-center">
                        <div class="fw-bold" style="font-size: 24px; color: #22c55e;">{{ number_format($totalParticipants) }}</div>
                        <span class="text-xs text-on-surface-variant">Peserta</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-3 rounded-xl bg-light text-center">
                        <div class="fw-bold" style="font-size: 24px; color: #f59e0b;">{{ count($categories) }}</div>
                        <span class="text-xs text-on-surface-variant">Kategori</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
