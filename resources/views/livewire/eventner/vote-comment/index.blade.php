<div>
    {{-- Page Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Komentar Voting</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Komentar Voting</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card mb-0 bg-primary-subtle border-0">
                <div class="card-body p-3 text-center">
                    <p class="text-muted small mb-1 fw-semibold">Total Komentar</p>
                    <h3 class="fw-semibold text-primary mb-0">{{ number_format($summary->total_comments ?? 0) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mb-0 bg-success-subtle border-0">
                <div class="card-body p-3 text-center">
                    <p class="text-muted small mb-1 fw-semibold">Vote dari Komentar</p>
                    <h3 class="fw-semibold text-success mb-0">{{ number_format($summary->total_votes ?? 0) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mb-0 bg-info-subtle border-0">
                <div class="card-body p-3 text-center">
                    <p class="text-muted small mb-1 fw-semibold">Kontingen Terkomentari</p>
                    <h3 class="fw-semibold text-info mb-0">{{ number_format($summary->total_registrations ?? 0) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mb-0 bg-warning-subtle border-0">
                <div class="card-body p-3 text-center">
                    <p class="text-muted small mb-1 fw-semibold">Komentar Tier Atas (≥50)</p>
                    <h3 class="fw-semibold text-warning mb-0">{{ number_format($summary->top_tier_count ?? 0) }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">Cari</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent"><i class="ti ti-search text-muted"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Nama voter / isi komentar...">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted">Kontingen / Sekolah</label>
                    <select class="form-select" wire:model.live="filterRegistration">
                        <option value="">Semua Kontingen</option>
                        @foreach($registrations as $reg)
                            <option value="{{ $reg->id }}">{{ $reg->nama_sekolah }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted">Tier</label>
                    <select class="form-select" wire:model.live="filterTier">
                        <option value="">Semua Tier</option>
                        <option value="populer">⭐ Populer (≥10)</option>
                        <option value="hot">🔥 Hot (≥50)</option>
                        <option value="elite">💎 Elite (≥100)</option>
                        <option value="legend">⚡ Legend (≥500)</option>
                        <option value="mvp">👑 MVP (≥1000)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Rentang Tanggal (Dibayar)</label>
                    <div class="input-group">
                        <input type="date" class="form-control" wire:model.live="dateFrom">
                        <span class="input-group-text bg-light">-</span>
                        <input type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" wire:click="resetFilters" title="Reset Filter">
                        <i class="ti ti-refresh"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Efek tier (glow bertingkat + badge) — self-contained, tanpa build --}}
    <style>
        .vc-tier {
            border: 2px solid transparent;
            border-radius: 0.625rem;
            padding: 0.75rem 1rem;
        }
        .vc-tier--populer {
            border-color: #f59e0b;
            --vc-glow: rgba(245, 158, 11, 0.45);
            animation: vcGlow 2.4s ease-in-out infinite;
        }
        .vc-tier--hot {
            border-color: #f97316;
            --vc-glow: rgba(249, 115, 22, 0.6);
            animation: vcGlow 1.8s ease-in-out infinite;
        }
        .vc-tier--elite {
            border-color: #c026d3;
            background-color: rgba(192, 38, 211, 0.05);
            --vc-glow: rgba(192, 38, 211, 0.5);
            animation: vcGlow 2s ease-in-out infinite;
        }
        @property --vc-angle {
            syntax: '<angle>';
            initial-value: 0deg;
            inherits: false;
        }
        .vc-tier--legend {
            background:
                linear-gradient(#fff, #fff) padding-box,
                conic-gradient(from var(--vc-angle),
                    #f59e0b, #ef4444, #ec4899, #8b5cf6, #06b6d4, #22c55e, #eab308, #f59e0b) border-box;
            animation: vcSpin 3s linear infinite;
        }
        .vc-tier--mvp {
            background:
                linear-gradient(#fff, #fff) padding-box,
                linear-gradient(110deg, #b45309, #fbbf24, #fde68a, #fbbf24, #b45309) border-box;
            background-size: 100% 100%, 250% 100%;
            animation: vcShimmer 2.5s linear infinite, vcGlow 2s ease-in-out infinite;
            --vc-glow: rgba(250, 204, 21, 0.55);
        }
        @keyframes vcGlow {
            0%, 100% { box-shadow: 0 0 0 0 var(--vc-glow); }
            50%      { box-shadow: 0 0 10px 2px var(--vc-glow); }
        }
        @keyframes vcSpin {
            to { --vc-angle: 360deg; }
        }
        @keyframes vcShimmer {
            from { background-position: 0 0, 200% 0; }
            to   { background-position: 0 0, -200% 0; }
        }
        /* Badge tier */
        .vc-badge {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 11px; font-weight: 700; line-height: 1.4;
            padding: 3px 10px; border-radius: 9999px; white-space: nowrap;
        }
        .vc-badge--populer { background: #fef3c7; color: #b45309; }
        .vc-badge--hot     { background: #ffedd5; color: #c2410c; }
        .vc-badge--elite   { background: #fae8ff; color: #a21caf; }
        .vc-badge--legend  { background: linear-gradient(90deg,#ede9fe,#fce7f3); color: #7c3aed; }
        .vc-badge--mvp     { background: #fef9c3; color: #a16207; }
        @media (prefers-reduced-motion: reduce) {
            .vc-tier, .vc-badge { animation: none !important; }
        }
    </style>

    {{-- Daftar Komentar --}}
    <div class="card w-100">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold"><i class="ti ti-messages me-2"></i>Pesan &amp; Dukungan Voter</h5>
            <a href="{{ route('eventner.vote-comments.csv', [
                'search' => $search,
                'filterRegistration' => $filterRegistration,
                'filterTier' => $filterTier,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]) }}" class="btn btn-sm btn-primary">
                <i class="ti ti-file-export me-1"></i> Export CSV
            </a>
        </div>
        <div class="card-body p-4">
            @php
                $vcTiers = [
                    'mvp'     => ['icon' => 'ti-crown',       'label' => 'MVP'],
                    'legend'  => ['icon' => 'ti-bolt',        'label' => 'Legend'],
                    'elite'   => ['icon' => 'ti-diamond',     'label' => 'Elite'],
                    'hot'     => ['icon' => 'ti-flame',       'label' => 'Hot'],
                    'populer' => ['icon' => 'ti-star-filled', 'label' => 'Populer'],
                ];
            @endphp

            @forelse($comments as $c)
                @php $tier = \App\Livewire\Eventner\VoteComment\Index::tierOf((int) $c->votes_earned); @endphp
                <div class="mb-3" wire:key="comment-{{ $c->id }}">
                    <div @class(['border', 'rounded-3', 'bg-white', 'p-3', $tier ? 'vc-tier vc-tier--'.$tier : 'border-outline'])>
                        <div class="d-flex align-items-start gap-3">
                            <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:38px;height:38px;font-weight:700;">
                                {{ strtoupper(substr($c->voter_name, 0, 1)) }}
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                    <div>
                                        <h6 class="fw-semibold mb-0">{{ $c->voter_name ?: 'Guest / Anonim' }}</h6>
                                        <span class="text-muted" style="font-size: 0.75rem;">{{ $c->voter_email ?: '-' }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                        @if($tier)
                                            <span class="vc-badge vc-badge--{{ $tier }}">
                                                <i class="ti {{ $vcTiers[$tier]['icon'] }}"></i> {{ $vcTiers[$tier]['label'] }}
                                            </span>
                                        @endif
                                        <span class="badge bg-warning-subtle text-warning-emphasis fw-bold px-2 py-1" style="font-size: 0.75rem;" title="Total Vote">
                                            <i class="ti ti-heart-filled me-1"></i>{{ number_format($c->votes_earned, 0, ',', '.') }}
                                        </span>
                                        <span class="text-muted" style="font-size: 0.75rem;" title="{{ $c->paid_at?->translatedFormat('d M Y H:i') }} WIB">
                                            {{ $c->paid_at?->diffForHumans() }}
                                        </span>
                                    </div>
                                </div>
                                <p class="mb-1 mt-2 text-dark" style="font-size: 0.9rem; word-break: break-word;">&ldquo;{{ $c->comment }}&rdquo;</p>
                                @if($c->registration)
                                    <span class="text-primary fw-semibold" style="font-size: 0.8rem;">
                                        <i class="ti ti-school me-1"></i>{{ $c->registration->nama_sekolah }}
                                    </span>
                                    <span class="badge bg-primary-subtle text-primary py-0 px-2 ms-2" style="font-size: 0.7rem;">
                                        {{ $c->registration->competitionCategory->name ?? '-' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="ti ti-message-off fs-10 text-muted d-block mb-3"></i>
                    <h6 class="fw-semibold text-muted">Belum Ada Komentar</h6>
                    <p class="text-muted mb-0">Belum ada voter yang meninggalkan pesan dukungan, atau tidak ada yang cocok dengan filter.</p>
                </div>
            @endforelse
        </div>
        @if($comments->hasPages())
            <div class="card-footer bg-light">
                {{ $comments->links() }}
            </div>
        @endif
    </div>
</div>
