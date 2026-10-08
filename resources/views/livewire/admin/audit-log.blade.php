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
</div>
