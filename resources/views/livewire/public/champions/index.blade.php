<div class="row justify-content-center">
    <div class="col-lg-10">

        {{-- Header --}}
        <div class="card bg-warning-subtle shadow-none position-relative overflow-hidden mb-4">
            <div class="card-body px-4 py-4 text-center">
                <h2 class="fw-bold mb-2">
                    <i class="ti ti-trophy text-warning me-2"></i>
                    Pengumuman Juara
                </h2>
                <p class="text-muted fs-3 mb-0">{{ $eventner->nama_event }}</p>
            </div>
        </div>

        {{-- Category Tabs --}}
        @if(count($categories) > 1)
        <ul class="nav nav-tabs nav-fill mb-4" role="tablist">
            @foreach($categories as $cat)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $selectedCategoryId == $cat['id'] ? 'active bg-primary text-white' : '' }}"
                        wire:click="switchCategory({{ $cat['id'] }})"
                        type="button" role="tab">
                        {{ $cat['name'] }}
                    </button>
                </li>
            @endforeach
        </ul>
        @endif

        {{-- Champion Rankings --}}
        @forelse($allRankings as $group)
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="card-title fw-semibold mb-0">
                        <i class="ti ti-trophy text-warning me-2"></i> {{ $group['champion']->name }}
                    </h5>
                </div>

                {{-- Rank Title Legend --}}
                @if($group['rankTitles']->count() > 0)
                    <div class="px-3 pt-3 pb-0">
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @foreach($group['rankTitles'] as $rt)
                                <span class="badge bg-warning-subtle text-dark border border-warning rounded-pill px-3 py-2">
                                    <i class="ti ti-medal me-1"></i> {{ $rt->title }}
                                    <small class="text-muted ms-1">(Rank {{ $rt->rank_start }}-{{ $rt->rank_end }})</small>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-bottom-0 text-center" width="60px">
                                        <h6 class="fw-semibold mb-0">Rank</h6>
                                    </th>
                                    <th class="border-bottom-0">
                                        <h6 class="fw-semibold mb-0">Sekolah</h6>
                                    </th>
                                    <th class="border-bottom-0 text-center" width="140px">
                                        <h6 class="fw-semibold mb-0">Gelar</h6>
                                    </th>
                                    <th class="border-bottom-0 text-end pe-4" width="100px">
                                        <h6 class="fw-semibold mb-0">Skor</h6>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($group['participants'] as $ps)
                                    @php
                                        $rowClass = '';
                                        if ($ps['rank'] == 1) $rowClass = 'table-warning';
                                        elseif ($ps['rank'] == 2) $rowClass = 'table-light';
                                        elseif ($ps['rank'] == 3) $rowClass = 'table-info';
                                    @endphp
                                    <tr class="{{ $rowClass }}">
                                        <td class="text-center">
                                            @if($ps['rank'] == 1)
                                                <span class="badge bg-warning text-dark rounded-pill px-2 py-1 fs-3">🥇 1</span>
                                            @elseif($ps['rank'] == 2)
                                                <span class="badge bg-secondary text-white rounded-pill px-2 py-1 fs-3">🥈 2</span>
                                            @elseif($ps['rank'] == 3)
                                                <span class="badge bg-info text-white rounded-pill px-2 py-1 fs-3">🥉 3</span>
                                            @else
                                                <span class="fw-semibold text-muted">{{ $ps['rank'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($ps['participant']->logo_sekolah)
                                                    <img src="{{ asset('storage/' . $ps['participant']->logo_sekolah) }}" class="rounded-circle border" width="32" height="32" style="object-fit:cover;" alt="">
                                                @else
                                                    <div class="bg-primary bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                                                        <i class="ti ti-school text-primary"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <span class="fw-semibold">{{ $ps['participant']->nama_sekolah }}</span>
                                                    <br><small class="text-muted">NPSN: {{ $ps['participant']->npsn }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($ps['title'])
                                                <span class="badge bg-success-subtle text-success border border-success rounded-pill px-2 py-1">
                                                    <i class="ti ti-award me-1"></i>{{ $ps['title'] }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">
                                            <span class="fw-semibold fs-3 text-primary">{{ number_format($ps['total'], 0) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="ti ti-trophy-off fs-10 text-muted d-block mb-3"></i>
                    <h5 class="fw-semibold text-muted">Belum Ada Pengumuman Juara</h5>
                    <p class="text-muted">Data juara akan muncul setelah penilaian selesai dan kategori juara dikonfigurasi.</p>
                </div>
            </div>
        @endforelse

        {{-- Footer --}}
        <div class="text-center mt-3 mb-4">
            <a href="{{ route('public.scoreboard', $eventner->scoring_code) }}" class="btn btn-sm btn-outline-primary px-3">
                <i class="ti ti-list-numbers me-1"></i> Lihat Scoreboard
            </a>
        </div>
    </div>
</div>
