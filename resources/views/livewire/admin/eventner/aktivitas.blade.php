<div>
    <div class="row">
        <div class="col-12">
            <!-- Header & Breadcrumb -->
            <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
                <div class="card-body px-4 py-3">
                    <div class="row align-items-center">
                        <div class="col-9">
                            <h4 class="fw-semibold mb-1">Aktivitas: {{ $eventner->nama_event }}</h4>
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                <span class="text-muted fs-2">
                                    Riwayat login/logout akun panitia dan perubahan data event.
                                </span>
                            </div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a class="text-muted text-decoration-none" href="{{ route('admin.eventner.index') }}">Eventner</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a class="text-muted text-decoration-none" href="{{ route('admin.eventner.show', $eventner->id) }}">{{ \Illuminate\Support\Str::limit($eventner->nama_event, 24) }}</a>
                                    </li>
                                    <li class="breadcrumb-item" aria-current="page">Aktivitas</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="col-3">
                            <div class="text-center mb-n5">
                                <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="card mb-4">
                <div class="card-body py-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-5">
                            <div class="position-relative">
                                <input type="text" class="form-control ps-5" placeholder="Cari deskripsi..." wire:model.live.debounce.300ms="search">
                                <i class="ti ti-search position-absolute top-50 translate-middle-y ms-3 text-muted"></i>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" wire:model.live="filterJenis">
                                <option value="">Semua Jenis</option>
                                <option value="login">Login</option>
                                <option value="logout">Logout</option>
                                <option value="created">Dibuat</option>
                                <option value="updated">Diubah</option>
                                <option value="deleted">Dihapus</option>
                            </select>
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
                                    <th class="ps-4" width="150px">Waktu</th>
                                    <th width="140px">Jenis</th>
                                    <th>Deskripsi</th>
                                    <th width="150px">Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($this->activities as $activity)
                                    <tr wire:key="act-{{ $activity->id }}">
                                        <td class="ps-4">
                                            <span class="fw-semibold">{{ $activity->created_at->format('d/m/Y') }}</span>
                                            <br><span class="text-muted fs-2">{{ $activity->created_at->format('H:i') }}</span>
                                        </td>
                                        <td>
                                            @if($activity->event === 'login')
                                                <span class="badge bg-success-subtle text-success"><i class="ti ti-login me-1"></i>Login</span>
                                            @elseif($activity->event === 'logout')
                                                <span class="badge bg-secondary-subtle text-secondary"><i class="ti ti-logout me-1"></i>Logout</span>
                                            @elseif($activity->event === 'created')
                                                <span class="badge bg-primary-subtle text-primary">Dibuat</span>
                                            @elseif($activity->event === 'updated')
                                                <span class="badge bg-info-subtle text-info">Diubah</span>
                                            @elseif($activity->event === 'deleted')
                                                <span class="badge bg-danger-subtle text-danger">Dihapus</span>
                                            @else
                                                <span class="badge bg-light text-dark border">{{ class_basename($activity->subject_type) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $activity->description }}
                                            @php
                                                // Nilai properti bisa berupa array (kolom
                                                // yang di-cast, mis. score_options) —
                                                // json-encode sebelum interpolasi.
                                                $formatNilai = fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v;
                                                $old = collect($activity->properties->get('old') ?? [])
                                                    ->map(fn ($v, $k) => $k.': '.$formatNilai($v));
                                                $new = collect($activity->properties->get('attributes') ?? [])
                                                    ->map(fn ($v, $k) => $k.': '.$formatNilai($v));
                                            @endphp
                                            @if($old->isNotEmpty() || $new->isNotEmpty())
                                                <br><span class="text-muted fs-2">
                                                    @if($old->isNotEmpty()) Lama: {{ $old->implode(', ') }} @endif
                                                    @if($old->isNotEmpty() && $new->isNotEmpty()) &bull; @endif
                                                    @if($new->isNotEmpty()) Baru: {{ $new->implode(', ') }} @endif
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($activity->causer)
                                                {{ $activity->causer->name ?? 'System' }}
                                            @else
                                                <span class="text-muted">System</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center p-4 text-muted">
                                            <i class="ti ti-history fs-8"></i>
                                            <p class="mb-0 mt-2">Belum ada aktivitas tercatat.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($this->activities->hasPages())
                    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                        <span class="text-muted fs-2">Halaman {{ $this->activities->currentPage() }} dari {{ $this->activities->lastPage() }}</span>
                        {{ $this->activities->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
