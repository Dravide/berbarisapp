<style>
    @keyframes flashGreen {
        0% { background-color: rgba(39, 174, 96, 0.25); }
        100% { background-color: transparent; }
    }
    @keyframes flashRed {
        0% { background-color: rgba(192, 41, 43, 0.2); }
        100% { background-color: transparent; }
    }
    .rank-up-anim {
        animation: flashGreen 3s ease-out;
    }
    .rank-down-anim {
        animation: flashRed 3s ease-out;
    }
    .animate-pulse-slow {
        animation: pulseSlow 2s infinite;
    }
    @keyframes pulseSlow {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>

<div class="row justify-content-center">
    <div class="col-lg-8">

        {{-- Page Header --}}
        <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
            <div class="card-body px-4 py-3">
                <div class="row align-items-center">
                    <div class="col-9">
                        <h4 class="fw-semibold mb-8">
                            <i class="ti ti-trophy me-2"></i> Live Scoreboard
                            @if($selectedChampionCategoryId && $championCategory)
                                &mdash; <span class="text-primary">{{ $championCategory->name }}</span>
                            @endif
                        </h4>
                        <p class="text-muted fs-3 mb-0">
                            {{ $eventner->nama_event }}
                            @if(!$selectedChampionCategoryId)
                                @php
                                    $activeCat = collect($categories)->firstWhere('id', $selectedCategoryId);
                                @endphp
                                @if($activeCat)
                                    &mdash; <strong class="text-dark">{{ $activeCat->name }}</strong>
                                @endif
                            @endif
                        </p>
                    </div>
                    <div class="col-3 text-end">
                        <span class="badge bg-danger px-3 py-2">
                            <i class="ti ti-point-filled me-1"></i> LIVE
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Category Dropdown: hanya di mode kategori lomba. Saat menampilkan
             kategori juara (URL /champion/{id}?category_id=..), pilihan sudah
             ditentukan URL — dropdown tak perlu. --}}
        @if(!$selectedChampionCategoryId && (count($categories) > 1 || count($championCategories) > 0))
            <div class="card mb-4">
                <div class="card-body p-2">
                    <div class="mx-auto" style="max-width: 420px;">
                        <div class="input-group">
                            <span class="input-group-text bg-primary text-white"><i class="ti ti-list-numbers"></i></span>
                            <select class="form-select" wire:model.live="selectedOption">
                                @foreach($categories as $cat)
                                    @php $label = $cat->parent ? $cat->parent->name . ' — ' . $cat->name : $cat->name; @endphp
                                    <option value="cat:{{ $cat->id }}">{{ $label }}</option>
                                @endforeach
                                @foreach($championCategories as $champ)
                                    <option value="champion:{{ $champ->id }}">🏆 {{ $champ->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Rankings Table --}}
        <div wire:poll.5s>

            {{-- Podium Top 3 --}}
            @if(collect($rankings)->isNotEmpty())
                <div class="card mb-4 bg-white border">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-center align-items-end gap-2 gap-md-4 pt-3">
                            <!-- 2nd Place -->
                            @if(isset($rankings[1]))
                                @php $p2 = $rankings[1]; @endphp
                                <div class="text-center d-flex flex-column align-items-center p-2 rounded {{ ($p2['direction'] ?? '') === 'up' ? 'rank-up-anim' : (($p2['direction'] ?? '') === 'down' ? 'rank-down-anim' : '') }}" style="width: 30%; transition: all 0.3s;" wire:key="podium-2-{{ $p2['id'] }}">
                                    <div class="fw-bold text-truncate w-100" style="font-size: 0.85rem;" title="{{ $p2['nama_sekolah'] }}">{{ $p2['nama_sekolah'] }}</div>
                                    @if($p2['title'])
                                        <div class="badge bg-success-subtle text-success border border-success rounded-pill px-2 py-0 mt-1" style="font-size: 0.7rem;">{{ $p2['title'] }}</div>
                                    @endif
                                    <div class="text-primary fw-semibold small mb-2 mt-1">{{ number_format($p2['total'], 0) }}</div>
                                    <div class="bg-secondary bg-opacity-25 border border-secondary border-opacity-25 rounded-top d-flex flex-column justify-content-center align-items-center w-100" style="height: 100px; min-height: 80px;">
                                        <span class="badge bg-secondary text-dark rounded-circle fs-5" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">2</span>
                                    </div>
                                </div>
                            @endif

                            <!-- 1st Place -->
                            @if(isset($rankings[0]))
                                @php $p1 = $rankings[0]; @endphp
                                <div class="text-center d-flex flex-column align-items-center p-2 rounded {{ ($p1['direction'] ?? '') === 'up' ? 'rank-up-anim' : (($p1['direction'] ?? '') === 'down' ? 'rank-down-anim' : '') }}" style="width: 35%; transition: all 0.3s;" wire:key="podium-1-{{ $p1['id'] }}">
                                    <div class="fw-bold text-truncate w-100" style="font-size: 0.95rem; color: #b8860b;" title="{{ $p1['nama_sekolah'] }}">
                                        <i class="ti ti-crown text-warning fs-5"></i><br>
                                        {{ $p1['nama_sekolah'] }}
                                    </div>
                                    @if($p1['title'])
                                        <div class="badge bg-warning text-dark border border-warning rounded-pill px-2 py-0 mt-1" style="font-size: 0.75rem;">{{ $p1['title'] }}</div>
                                    @endif
                                    <div class="text-primary fw-bold mb-2 mt-1">{{ number_format($p1['total'], 0) }}</div>
                                    <div class="bg-warning bg-opacity-25 border border-warning rounded-top d-flex flex-column justify-content-center align-items-center w-100" style="height: 140px; min-height: 110px;">
                                        <span class="badge bg-warning text-dark rounded-circle fs-4" style="width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center;">1</span>
                                    </div>
                                </div>
                            @endif

                            <!-- 3rd Place -->
                            @if(isset($rankings[2]))
                                @php $p3 = $rankings[2]; @endphp
                                <div class="text-center d-flex flex-column align-items-center p-2 rounded {{ ($p3['direction'] ?? '') === 'up' ? 'rank-up-anim' : (($p3['direction'] ?? '') === 'down' ? 'rank-down-anim' : '') }}" style="width: 30%; transition: all 0.3s;" wire:key="podium-3-{{ $p3['id'] }}">
                                    <div class="fw-bold text-truncate w-100" style="font-size: 0.85rem;" title="{{ $p3['nama_sekolah'] }}">{{ $p3['nama_sekolah'] }}</div>
                                    @if($p3['title'])
                                        <div class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2 py-0 mt-1" style="font-size: 0.7rem;">{{ $p3['title'] }}</div>
                                    @endif
                                    <div class="text-primary fw-semibold small mb-2 mt-1">{{ number_format($p3['total'], 0) }}</div>
                                    <div class="bg-info-subtle border border-info-subtle rounded-top d-flex flex-column justify-content-center align-items-center w-100" style="height: 80px; min-height: 60px;">
                                        <span class="badge bg-info-subtle text-dark rounded-circle fs-5" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">3</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 flex-wrap gap-2">
                    <h6 class="fw-semibold mb-0 d-flex align-items-center flex-wrap gap-2">
                        <span><i class="ti ti-list-numbers me-1"></i> Peringkat Peserta</span>
                        @if($activeInputSchool)
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle animate-pulse-slow py-1 ms-lg-2">
                                <span class="spinner-grow spinner-grow-sm text-danger me-1" style="width: 8px; height: 8px;" role="status"></span>
                                Menilai: <strong>{{ $activeInputSchool }}</strong>
                            </span>
                        @endif
                    </h6>
                    <span class="badge bg-light text-dark border">
                        <i class="ti ti-clock me-1"></i> <span id="clock-display">00:00:00</span>
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        @if(collect($rankings)->count() > 3)
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-bottom-0 ps-4" width="60px">
                                            <h6 class="fw-semibold mb-0">Rank</h6>
                                        </th>
                                        <th class="border-bottom-0">
                                            <h6 class="fw-semibold mb-0">Sekolah</h6>
                                        </th>
                                        <th class="border-bottom-0 text-end pe-4" width="110px">
                                            <h6 class="fw-semibold mb-0">Total Skor</h6>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(collect($rankings)->slice(3) as $item)
                                        @php
                                            $rowClass = '';
                                            if (($item['direction'] ?? '') === 'up') {
                                                $rowClass .= 'rank-up-anim';
                                            } elseif (($item['direction'] ?? '') === 'down') {
                                                $rowClass .= 'rank-down-anim';
                                            }
                                        @endphp
                                        <tr wire:key="rank-row-{{ $item['id'] }}" class="{{ trim($rowClass) }}">
                                            <td class="ps-4">
                                                <span class="text-muted fw-semibold">{{ $item['rank'] }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center flex-wrap gap-2">
                                                    <span class="fw-semibold">{{ $item['nama_sekolah'] }}</span>
                                                    @if($item['title'])
                                                        <span class="badge bg-success-subtle text-success border border-success rounded-pill px-2 py-0" style="font-size: 0.7rem;">{{ $item['title'] }}</span>
                                                    @endif
                                                </div>
                                                <small class="text-muted">NPSN: {{ $item['npsn'] }}</small>
                                            </td>
                                            <td class="text-end pe-4">
                                                <span class="fw-semibold text-primary">{{ number_format($item['total'], 0) }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @elseif(collect($rankings)->count() <= 3 && collect($rankings)->isNotEmpty())
                            <div class="text-center py-4">
                                <p class="text-muted fs-3 mb-0">Semua peringkat aktif ditampilkan di podium utama.</p>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="ti ti-scoreboard fs-8 text-muted d-block mb-2"></i>
                                <h6 class="fw-semibold text-muted">Belum Ada Data Penilaian</h6>
                                <p class="text-muted fs-3">Scoreboard akan otomatis terupdate saat penilaian dimulai.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@script
<script>
    const updateLocalClock = () => {
        const el = document.getElementById('clock-display');
        if (el) {
            const now = new Date();
            const timeString = now.toTimeString().split(' ')[0];
            el.textContent = timeString;
        }
    };

    // Update immediately and then every second
    updateLocalClock();
    setInterval(updateLocalClock, 1000);
</script>
@endscript
