<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Pengaturan Vote</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Pengaturan Vote</li>
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

    <div class="card w-100">
        <div class="card-body p-4">
            <form wire:submit="save">
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" wire:model.live="vote_active" id="voteActive" style="width:48px;height:24px;">
                    <label class="form-check-label fw-semibold ms-2" for="voteActive">
                        Aktifkan Vote Online
                    </label>
                    <p class="text-muted small mb-0 mt-1">Jika aktif, pengunjung bisa membeli vote untuk mendukung peserta favorit di halaman event.</p>
                </div>

                @if($vote_active)
                <div class="border rounded p-4 mb-4">
                    <h6 class="fw-semibold mb-3">Jadwal Voting</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Voting Mulai <span class="text-muted">(Opsional)</span></label>
                            <input type="datetime-local" class="form-control" wire:model="vote_start">
                            <small class="form-text text-muted">Kosongkan jika langsung aktif.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Voting Berakhir <span class="text-muted">(Opsional)</span></label>
                            <input type="datetime-local" class="form-control" wire:model="vote_end">
                            <small class="form-text text-muted">Kosongkan jika tidak ada batas akhir.</small>
                        </div>
                    </div>
                    <hr>
                    <h6 class="fw-semibold mb-3">Harga Vote</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Harga per 1 Vote (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" wire:model="vote_price" placeholder="1000" min="0">
                            </div>
                            <small class="form-text text-muted">Biaya dasar yang dikenakan untuk setiap 1 poin vote. Bisa didiskon via Vote Booster.</small>
                        </div>
                    </div>
                </div>
                @endif

                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                    <i class="ti ti-device-floppy me-1"></i> Simpan Pengaturan
                </button>
            </form>
        </div>
    </div>
</div>
