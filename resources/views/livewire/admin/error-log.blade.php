<div>
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
                <div class="card-body px-4 py-3">
                    <div class="row align-items-center">
                        <div class="col-9">
                            <h4 class="fw-semibold mb-8">Log Error</h4>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item" aria-current="page">Log Error</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="col-3 text-end">
                            <a href="{{ route('admin.exports.error-logs') }}" class="btn btn-primary btn-sm">
                                <i class="ti ti-download me-1"></i> Ekspor CSV
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <input type="text" class="form-control" wire:model.live="search" placeholder="Cari kode (ER-98673), pesan, url...">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" wire:model.live="filterStatus">
                                <option value="">Semua Status</option>
                                <option value="open">Belum Selesai</option>
                                <option value="resolved">Selesai</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" wire:model.live="filterStatusHttp">
                                <option value="">Semua HTTP</option>
                                @foreach($this->httpStatusOptions as $http)
                                    <option value="{{ $http }}">{{ $http }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-secondary w-100" wire:click="$set('search', ''); $set('filterStatus', ''); $set('filterStatusHttp', '')">
                                <i class="ti ti-refresh me-1"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Table -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4" width="130px">Kode</th>
                                    <th width="120px">Terakhir</th>
                                    <th width="60px">×</th>
                                    <th width="70px">HTTP</th>
                                    <th width="170px">Exception</th>
                                    <th>Pesan</th>
                                    <th width="140px">User</th>
                                    <th width="110px">Status</th>
                                    <th width="60px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr wire:key="error-{{ $log->id }}">
                                        <td class="ps-4">
                                            <code class="fw-bold" style="font-size: 13px;">{{ $log->code }}</code>
                                        </td>
                                        <td>
                                            @php $terakhir = $log->last_seen_at ?? $log->created_at; @endphp
                                            <span class="fw-semibold">{{ $terakhir->format('d/m/Y') }}</span>
                                            <br><span class="text-muted fs-2" title="{{ $terakhir->format('H:i:s') }}">{{ $terakhir->diffForHumans() }}</span>
                                        </td>
                                        <td>
                                            @if(($log->occurrences ?? 1) > 1)
                                                <span class="badge bg-danger-subtle text-danger" title="Terjadi {{ $log->occurrences }} kali">{{ $log->occurrences }}×</span>
                                            @else
                                                <span class="text-muted">1×</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-danger-subtle text-danger">{{ $log->http_status ?? '—' }}</span>
                                        </td>
                                        <td class="text-truncate" style="max-width: 160px;" title="{{ $log->exception_class }}">
                                            {{ class_basename($log->exception_class) }}
                                        </td>
                                        <td class="text-truncate" style="max-width: 280px;" title="{{ $log->message }}">
                                            {{ \Illuminate\Support\Str::limit($log->message, 60) }}
                                        </td>
                                        <td>
                                            @if($log->user)
                                                {{ $log->user->name }}
                                                @if($log->eventner)
                                                    <small class="text-muted d-block fs-2">{{ \Illuminate\Support\Str::limit($log->eventner->nama_event, 20) }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->resolved_at)
                                                <span class="badge bg-success-subtle text-success"><i class="ti ti-check me-1"></i>Selesai</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning"><i class="ti ti-circle-dot me-1"></i>Belum</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1"
                                                wire:click="showDetail({{ $log->id }})" title="Detail">
                                                <i class="ti ti-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center p-4 text-muted">
                                            <i class="ti ti-bug fs-8"></i>
                                            <p class="mb-0 mt-2">Belum ada error tercatat.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($logs->hasPages())
                <div class="card-footer bg-white">
                    {{ $logs->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal detail — bukan di luar root: Livewire tidak merender markup di luar elemen root-nya. --}}
    @if($detail)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);" wire:key="error-detail-{{ $detail->id }}">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="ti ti-bug me-2"></i><code class="fw-bold">{{ $detail->code }}</code>
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeDetail"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6 class="text-muted fs-3 mb-1">Waktu</h6>
                                @php $terakhir = $detail->last_seen_at ?? $detail->created_at; @endphp
                                <p class="fw-semibold mb-0">
                                    Pertama: {{ $detail->created_at->translatedFormat('d F Y, H:i:s') }}
                                    <span class="text-muted fs-3">({{ $detail->created_at->diffForHumans() }})</span>
                                </p>
                                <p class="fw-semibold mb-0">
                                    Terakhir: {{ $terakhir->translatedFormat('d F Y, H:i:s') }}
                                    <span class="text-muted fs-3">({{ $terakhir->diffForHumans() }})</span>
                                    <span class="badge bg-danger-subtle text-danger ms-1">Terjadi {{ $detail->occurrences ?? 1 }}×</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted fs-3 mb-1">Request</h6>
                                <p class="fw-semibold mb-0">
                                    <span class="badge bg-danger-subtle text-danger">{{ $detail->http_status ?? '—' }}</span>
                                    <span class="badge bg-secondary">{{ $detail->method ?? '—' }}</span>
                                    <span class="fs-3 d-block text-truncate text-muted">{{ $detail->url ?? '—' }}</span>
                                </p>
                            </div>
                            <div class="col-md-6 mt-2">
                                <h6 class="text-muted fs-3 mb-1">User / Event</h6>
                                <p class="fw-semibold mb-0">
                                    @if($detail->user)
                                        {{ $detail->user->name }}
                                        <span class="text-muted fs-3 d-block">({{ $detail->user->email }})</span>
                                    @else
                                        <span class="text-muted">Guest / Anonim</span>
                                    @endif
                                    @if($detail->eventner)
                                        <span class="text-muted fs-3 d-block">Event: {{ $detail->eventner->nama_event }}</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6 mt-2">
                                <h6 class="text-muted fs-3 mb-1">IP / User Agent</h6>
                                <p class="fw-semibold mb-0">
                                    {{ $detail->ip ?? '—' }}
                                    <span class="text-muted fs-3 d-block text-break">{{ \Illuminate\Support\Str::limit($detail->user_agent ?? '—', 80) }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <h6 class="text-muted fs-3 mb-1">Exception</h6>
                            <p class="fw-semibold mb-0">{{ $detail->exception_class }}</p>
                            @if($detail->file)
                                <span class="fs-3 text-muted d-block">
                                    {{ \Illuminate\Support\Str::of($detail->file)->replace(base_path() . DIRECTORY_SEPARATOR, '') }}:{{ $detail->line }}
                                </span>
                            @endif
                        </div>

                        <div class="mb-3">
                            <h6 class="text-muted fs-3 mb-1">Pesan</h6>
                            <p class="mb-0 p-2 bg-light border rounded text-break">{{ $detail->message }}</p>
                        </div>

                        @if($detail->trace)
                            <div class="mb-2">
                                <h6 class="text-muted fs-3 mb-1">Trace</h6>
                                <pre class="bg-light border rounded p-3 mb-0" style="max-height: 240px; overflow: auto; font-size: 12px;"><code>{{ $detail->trace }}</code></pre>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer justify-content-between">
                        <div class="text-start">
                            @if($detail->resolved_at)
                                <span class="text-success fs-3">
                                    <i class="ti ti-check"></i> Diselesaikan {{ $detail->resolved_at->diffForHumans() }}
                                    @if($detail->resolver) oleh {{ $detail->resolver->name }} @endif
                                </span>
                            @endif
                        </div>
                        <div>
                            @if($detail->resolved_at)
                                <button type="button" class="btn btn-outline-warning" wire:click="bukaKembali({{ $detail->id }})">Buka Kembali</button>
                            @else
                                <button type="button" class="btn btn-success" wire:click="tandaiSelesai({{ $detail->id }})">Tandai Selesai</button>
                            @endif
                            <button type="button" class="btn btn-outline-secondary" wire:click="closeDetail">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
