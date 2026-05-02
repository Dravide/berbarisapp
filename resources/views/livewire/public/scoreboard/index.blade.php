<div class="row justify-content-center">
    <div class="col-lg-8">

        {{-- Page Header --}}
        <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
            <div class="card-body px-4 py-3">
                <div class="row align-items-center">
                    <div class="col-9">
                        <h4 class="fw-semibold mb-8">
                            <i class="ti ti-trophy me-2"></i> Live Scoreboard
                        </h4>
                        <p class="text-muted fs-3 mb-0">{{ $eventner->nama_event }}</p>
                    </div>
                    <div class="col-3 text-end">
                        <span class="badge bg-danger px-3 py-2">
                            <i class="ti ti-point-filled me-1"></i> LIVE
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Category Tabs --}}
        @if($categories->count() > 1)
        <ul class="nav nav-tabs nav-fill mb-4" role="tablist">
            @foreach($categories as $cat)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $selectedCategoryId == $cat->id ? 'active bg-primary text-white' : '' }}"
                        wire:click="$set('selectedCategoryId', {{ $cat->id }})"
                        type="button" role="tab">
                        {{ $cat->name }}
                    </button>
                </li>
            @endforeach
        </ul>
        @endif

        {{-- Rankings Table --}}
        <div wire:poll.5s>
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                    <h6 class="fw-semibold mb-0">
                        <i class="ti ti-list-numbers me-1"></i> Peringkat Peserta
                    </h6>
                    <span class="badge bg-light text-dark border">
                        <i class="ti ti-clock me-1"></i> {{ now()->format('H:i:s') }}
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        @if($rankings)
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
                                    @foreach($rankings as $item)
                                        <tr class="{{ $item['rank'] == 1 ? 'table-warning' : '' }}">
                                            <td class="ps-4">
                                                @if($item['rank'] == 1)
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="ti ti-crown me-1"></i>1
                                                    </span>
                                                @elseif($item['rank'] == 2)
                                                    <span class="badge bg-secondary bg-opacity-25 text-dark">2</span>
                                                @elseif($item['rank'] == 3)
                                                    <span class="badge bg-info-subtle text-dark">3</span>
                                                @else
                                                    <span class="text-muted fw-semibold">{{ $item['rank'] }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="fw-semibold">{{ $item['nama_sekolah'] }}</span>
                                                <br><small class="text-muted">NPSN: {{ $item['npsn'] }}</small>
                                            </td>
                                            <td class="text-end pe-4">
                                                <span class="fw-semibold text-primary">{{ number_format($item['total'], 0) }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
