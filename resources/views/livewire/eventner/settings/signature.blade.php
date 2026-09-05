<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">TTD &amp; Stempel</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Pengaturan Event</li>
                            <li class="breadcrumb-item" aria-current="page">TTD &amp; Stempel</li>
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

    <div class="alert alert-light border border-info-subtle fs-2 mb-4">
        <i class="ti ti-info-circle me-1 text-info"></i>
        Stempel terpilih dipakai pada kolom <strong>Penerima Pembayaran</strong> di kwitansi/invoice PDF.
        Kolom <strong>Ketua Pelaksana</strong> otomatis memakai QR event.
    </div>

    <div class="row">
        <div class="col-md-5">
            {{-- Upload --}}
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-3">Unggah TTD / Stempel</h5>
                    <form wire:submit="save">
                        <div class="mb-3">
                            <label class="form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model="name" placeholder="Contoh: Stempel Panitia, TTD Ketua Panitia">
                            @error('name') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">File PNG <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" wire:model="image" accept="image/png">
                            @error('image') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            <div class="form-text">PNG transparan disarankan, tinggi &ge; 300px, maksimal 2MB.</div>
                            @if($image)
                                <div class="mt-2">
                                    <img src="{{ $image->temporaryUrl() }}" alt="Preview" style="max-height: 100px;" class="rounded border bg-secondary-subtle p-1">
                                </div>
                            @endif
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-cloud-upload me-1"></i> Unggah
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title fw-semibold mb-0">Galeri TTD / Stempel</h5>
                </div>
                <div class="card-body">
                    @if($this->signatures->isEmpty())
                        <div class="text-center py-5">
                            <i class="ti ti-signature-off fs-10 text-muted"></i>
                            <p class="text-muted mt-3 mb-0">Belum ada TTD/Stempel terunggah. Unggah PNG di samping untuk mulai.</p>
                        </div>
                    @else
                        <div class="row g-3">
                            @foreach($this->signatures as $sig)
                                <div class="col-md-6">
                                    <div class="card h-100 {{ $sig->id === $activeSignatureId ? 'border-primary border-2' : '' }}">
                                        <div class="card-body text-center"
                                            style="background-color: #fff; background-image: linear-gradient(45deg, #e9ecef 25%, transparent 25%, transparent 75%, #e9ecef 75%, #e9ecef), linear-gradient(45deg, #e9ecef 25%, transparent 25%, transparent 75%, #e9ecef 75%, #e9ecef); background-size: 16px 16px; background-position: 0 0, 8px 8px;">
                                            <img src="{{ asset('storage/' . $sig->image) }}" alt="{{ $sig->name }}" style="max-height: 110px; max-width: 100%;">
                                        </div>
                                        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                                            <span class="fw-semibold fs-3 text-truncate">{{ $sig->name }}</span>
                                            <div class="d-flex gap-1">
                                                @if($sig->id === $activeSignatureId)
                                                    <span class="btn btn-sm btn-success rounded-pill px-3 disabled">
                                                        <i class="ti ti-checks me-1"></i> Aktif
                                                    </span>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="selectSignature({{ $sig->id }})" title="Pakai stempel ini">
                                                        <i class="ti ti-check me-1"></i> Pakai
                                                    </button>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-outline-danger" wire:click="delete({{ $sig->id }})" wire:confirm="Yakin ingin menghapus TTD/Stempel ini?">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
