<div wire:poll.3s>
    <div class="row justify-content-center">
        <div class="col-lg-10">

            {{-- Header --}}
            <div class="card bg-warning-subtle shadow-none position-relative overflow-hidden mb-4">
                <div class="card-body px-4 py-4 text-center">
                    <h2 class="fw-bold mb-2">
                        <i class="ti ti-table text-warning me-2"></i>
                        Hasil Pengundian
                    </h2>
                    <p class="text-muted fs-3 mb-0">{{ $eventner->nama_event }}</p>
                </div>
            </div>

            {{-- Info Bar --}}
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-3 py-2 fs-3">
                    <i class="ti ti-player-play me-1"></i> LIVE
                    <small class="ms-1 opacity-75">Update otomatis</small>
                </span>
                <span class="badge bg-primary-subtle text-primary border border-primary rounded-pill px-3 py-2 fs-3">
                    {{ $results->count() }} / {{ $totalSchools }} Ditentukan
                </span>
            </div>

            {{-- Category Select --}}
            @if(count($categories) > 1)
                <div class="mb-4">
                    <div class="input-group mx-auto" style="max-width: 400px;">
                        <span class="input-group-text bg-primary text-white"><i class="ti ti-category"></i></span>
                        <select class="form-select" wire:model.live="activeTab" wire:change="switchTab($event.target.value)">
                            @foreach($categories as $cat)
                                @php $label = !empty($cat['parent']) ? $cat['parent']['name'] . ' — ' . $cat['name'] : $cat['name']; @endphp
                                <option value="{{ $cat['id'] }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            {{-- Results Table --}}
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="card-title fw-semibold mb-0">
                        <i class="ti ti-list-numbers text-warning me-2"></i> Urutan Tampil
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($results->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-bottom-0 text-center" width="80px">
                                            <h6 class="fw-semibold mb-0">Urutan</h6>
                                        </th>
                                        <th class="border-bottom-0" width="70px">
                                            <h6 class="fw-semibold mb-0">Logo</h6>
                                        </th>
                                        <th class="border-bottom-0">
                                            <h6 class="fw-semibold mb-0">Sekolah</h6>
                                        </th>
                                        <th class="border-bottom-0" width="140px">
                                            <h6 class="fw-semibold mb-0">NPSN</h6>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($results as $reg)
                                        <tr class="{{ $loop->last ? 'table-success' : '' }}">
                                            <td class="text-center">
                                                <span class="badge bg-primary text-white rounded-pill px-3 py-2 fs-4">{{ $reg->urutan_tampil }}</span>
                                            </td>
                                            <td>
                                                @if($reg->logo_sekolah)
                                                    <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" class="rounded-circle border" width="40" height="40" style="object-fit:cover;" alt="">
                                                @else
                                                    <div class="bg-primary bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                                        <i class="ti ti-school text-primary"></i>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="fw-semibold">{{ $reg->display_name }}</span>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $reg->npsn }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="ti ti-hourglass-empty fs-10 text-muted d-block mb-3"></i>
                            <h5 class="fw-semibold text-muted">Menunggu Pengundian...</h5>
                            <p class="text-muted">Hasil akan muncul otomatis saat pengundian dilakukan.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Footer --}}
            <div class="text-center mt-3 mb-4">
                <a href="{{ event_url($eventner, 'detail') }}" class="btn btn-sm btn-outline-primary px-3 me-1">
                    <i class="ti ti-arrow-left me-1"></i> Detail Event
                </a>
                <a href="{{ event_url($eventner, 'drawing.spin') }}" class="btn btn-sm btn-primary px-3">
                    <i class="ti ti-arrows-shuffle me-1"></i> Layar Spin
                </a>
            </div>

        </div>
    </div>
</div>
