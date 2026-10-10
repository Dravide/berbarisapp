<div>
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
                <div class="card-body px-4 py-3">
                    <div class="row align-items-center">
                        <div class="col-9">
                            <h4 class="fw-semibold mb-8">Audit Log</h4>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item" aria-current="page">Audit Log</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="text" class="form-control" wire:model.live="search" placeholder="Cari deskripsi, pelaku, atau model...">
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" wire:model.live="filterLog">
                                <option value="">Semua Log</option>
                                @foreach($this->logNames as $nama)
                                    <option value="{{ $nama }}">{{ $nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-secondary w-100" wire:click="$set('search', ''); $set('filterLog', '')">
                                <i class="ti ti-refresh me-1"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Table -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4" width="180px">Waktu</th>
                                    <th width="120px">Model</th>
                                    <th>Deskripsi</th>
                                    <th width="150px">Pelaku</th>
                                    <th width="100px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activities as $activity)
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-semibold">{{ $activity->created_at->format('d/m/Y') }}</span>
                                            <br><span class="text-muted fs-2">{{ $activity->created_at->format('H:i') }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ class_basename($activity->subject_type) }}</span>
                                        </td>
                                        <td>
                                            {{ $activity->description }}
                                            @if(!empty($activity->properties->toArray()))
                                                <small class="text-muted d-block fs-2">{{ json_encode($activity->properties->toArray(), JSON_UNESCAPED_UNICODE) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($activity->causer)
                                                {{ $activity->causer->name ?? 'System' }}
                                            @else
                                                <span class="text-muted">System</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($activity->event === 'created')
                                                <span class="badge bg-success-subtle text-success">Dibuat</span>
                                            @elseif($activity->event === 'updated')
                                                <span class="badge bg-info-subtle text-info">Diupdate</span>
                                            @elseif($activity->event === 'deleted')
                                                <span class="badge bg-danger-subtle text-danger">Dihapus</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($activity->event) }}</span>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-outline-secondary ms-1 py-0 px-1"
                                                wire:click="showDetail({{ $activity->id }})" title="Detail">
                                                <i class="ti ti-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center p-4 text-muted">
                                            <i class="ti ti-history fs-8"></i>
                                            <p class="mb-0 mt-2">Belum ada aktivitas tercatat.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($activities->hasPages())
                <div class="card-footer bg-white">
                    {{ $activities->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal detail — bukan di luar root: Livewire tidak merender markup di luar elemen root-nya. --}}
    @if($detail)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);" wire:key="detail-{{ $detail->id }}">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="ti ti-history me-2"></i>Detail Aktivitas #{{ $detail->id }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeDetail"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6 class="text-muted fs-3 mb-1">Pelaku (causer)</h6>
                                <p class="fw-semibold mb-0">
                                    @if($detail->causer)
                                        {{ $detail->causer->name ?? 'System' }}
                                        <span class="text-muted fs-3 d-block">{{ $detail->causer->email ?? $detail->causer->username ?? '' }}</span>
                                    @else
                                        <span class="text-muted">System</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted fs-3 mb-1">Waktu</h6>
                                <p class="fw-semibold mb-0">{{ $detail->created_at->translatedFormat('d F Y, H:i:s') }}</p>
                            </div>
                            <div class="col-md-6 mt-2">
                                <h6 class="text-muted fs-3 mb-1">Model (subject)</h6>
                                <p class="fw-semibold mb-0">
                                    {{ $detail->subject_type ? class_basename($detail->subject_type) : '—' }}
                                    @if($detail->subject)
                                        <span class="text-muted fs-3 d-block">ID #{{ $detail->subject_id }}</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6 mt-2">
                                <h6 class="text-muted fs-3 mb-1">Log / Event</h6>
                                <p class="fw-semibold mb-0">
                                    <span class="badge bg-secondary">{{ $detail->log_name ?? 'default' }}</span>
                                    <span class="badge bg-primary-subtle text-primary">{{ $detail->description }}</span>
                                </p>
                            </div>
                        </div>

                        @php
                            $props = $detail->properties->toArray();
                            $old = $props['old'] ?? [];
                            $new = $props['attributes'] ?? [];
                            $lain = collect($props)->except(['old', 'attributes']);
                        @endphp

                        @if(!empty($old) || !empty($new))
                            <h6 class="fw-semibold mb-2">Perubahan</h6>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-striped align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Kolom</th>
                                            <th>Lama</th>
                                            <th>Baru</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($new as $kolom => $nilai)
                                            <tr>
                                                <td class="fw-semibold">{{ $kolom }}</td>
                                                <td class="text-muted"><s>{{ is_array($old[$kolom] ?? null) ? json_encode($old[$kolom], JSON_UNESCAPED_UNICODE) : ($old[$kolom] ?? '—') }}</s></td>
                                                <td>{{ is_array($nilai) ? json_encode($nilai, JSON_UNESCAPED_UNICODE) : $nilai }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if($lain->isNotEmpty())
                            <h6 class="fw-semibold mt-3 mb-2">Properti Lain</h6>
                            <pre class="bg-light border rounded p-3 mb-0" style="max-height: 240px; overflow: auto;"><code>{{ json_encode($lain->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                        @endif

                        @if(empty($old) && empty($new) && $lain->isEmpty())
                            <p class="text-muted mb-0">Tidak ada properti tercatat.</p>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeDetail">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
