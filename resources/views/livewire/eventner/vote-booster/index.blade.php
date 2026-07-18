<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Vote Booster</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Vote Booster</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="alert alert-success border-0 bg-success-subtle text-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Active Booster Alert --}}
    @if($activeNow)
    <div class="alert alert-warning border-0 bg-warning-subtle text-warning-emphasis d-flex align-items-center gap-2 mb-4">
        <i class="ti ti-bolt fs-5"></i>
        <span>Vote Booster aktif! <strong>{{ $activeNow->vote_multiplier }}x lipat</strong> hingga
            <strong>{{ $activeNow->ends_at->format('d M Y, H:i') }}</strong>.
            Harga Rp {{ number_format($eventner->vote_price ?? 1000, 0, ',', '.') }} = <strong>{{ $activeNow->vote_multiplier }} vote</strong>.
        </span>
    </div>
    @endif

    <div class="row">
        {{-- Form --}}
        <div class="col-lg-5">
            <div class="card w-100 mb-4">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-4">Tambah Jadwal Booster</h5>
                    <form wire:submit="save">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mulai</label>
                            <input type="datetime-local" class="form-control" wire:model="starts_at" required>
                            @error('starts_at') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Berakhir</label>
                            <input type="datetime-local" class="form-control" wire:model="ends_at" required>
                            @error('ends_at') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Multiplier (x lipat)</label>
                            <input type="number" class="form-control" wire:model="vote_multiplier" min="2" max="100" required>
                            <small class="form-text text-muted">Misal: 2 = 2x lipat (1000 dapat 2 vote).</small>
                            @error('vote_multiplier') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-plus me-1"></i> Tambah Booster
                        </button>
                    </form>
                </div>
            </div>

            <div class="card bg-primary-subtle border-0">
                <div class="card-body">
                    <h6 class="fw-semibold"><i class="ti ti-info-circle me-1"></i> Info</h6>
                    <p class="fs-2 mb-0">Vote Booster adalah program diskon vote dalam jadwal tertentu.
                        Harga dasar tetap {{ number_format($eventner->vote_price ?? 1000, 0, ',', '.') }} per transaksi,
                        tapi jumlah vote yang didapat dikalikan multiplier.</p>
                </div>
            </div>
        </div>

        {{-- List Booster --}}
        <div class="col-lg-7">
            <div class="card w-100">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white fw-semibold">Daftar Vote Booster</h5>
                </div>
                <div class="card-body p-4">
                    @if($boosters->isEmpty())
                        <div class="text-center py-5">
                            <i class="ti ti-bolt fs-10 text-muted d-block mb-2"></i>
                            <h5 class="fw-semibold text-muted">Belum ada Vote Booster</h5>
                            <p>Tambahkan jadwal booster di form sebelah kiri.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle text-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fw-semibold">Jadwal</th>
                                        <th class="fw-semibold text-center">Multiplier</th>
                                        <th class="fw-semibold text-center">Status</th>
                                        <th class="fw-semibold text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($boosters as $b)
                                    @php $isRunning = $b->is_active && $b->starts_at <= now() && $b->ends_at >= now(); @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $b->starts_at->format('d M Y, H:i') }}</div>
                                            <div class="text-muted fs-2">s/d {{ $b->ends_at->format('d M Y, H:i') }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning text-dark fs-3">{{ $b->vote_multiplier }}x Lipat</span>
                                        </td>
                                        <td class="text-center">
                                            @if($isRunning)
                                                <span class="badge bg-success">Berjalan</span>
                                            @elseif(!$b->is_active)
                                                <span class="badge bg-secondary">Nonaktif</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Terjadwal</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-secondary me-1" wire:click="toggleActive({{ $b->id }})">
                                                {{ $b->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" wire:click="delete({{ $b->id }})"
                                                onclick="return confirm('Hapus booster ini?') || event.stopImmediatePropagation()">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
