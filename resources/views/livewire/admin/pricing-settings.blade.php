<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Harga & Paket SaaS</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">Harga & Paket</li>
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

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <form wire:submit="save">
                {{-- Harga --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title fw-semibold mb-4">Harga</h5>
                        <div class="mb-3">
                            <label for="plan_price" class="form-label">Harga Paket "Event Penuh" (Rp)</label>
                            <input type="number" class="form-control @error('plan_price') is-invalid @enderror"
                                id="plan_price" wire:model="plan_price" min="0" step="1000">
                            @error('plan_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Bayar sekali per event via QRIS. Tampil di /pricing dan landing.</div>
                        </div>
                        <div class="mb-0">
                            <label for="registration_fee" class="form-label">Biaya Pendaftaran Eventner (Rp)</label>
                            <input type="number" class="form-control @error('registration_fee') is-invalid @enderror"
                                id="registration_fee" wire:model="registration_fee" min="0" step="1000">
                            @error('registration_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Dibayar saat mendaftar dengan paket berbayar. Biaya 0 = gratis.</div>
                        </div>
                    </div>
                </div>

                {{-- Fitur Paket --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title fw-semibold mb-2">Fitur Paket Berbayar</h5>
                        <p class="text-muted fs-3 mb-3">Centang fitur yang dipamerkan sebagai bagian paket berbayar di halaman harga.</p>
                        <div class="row">
                            @foreach($premium_features as $key => $included)
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" wire:model="premium_features.{{ $key }}" id="pf_{{ $key }}">
                                        <label class="form-check-label" for="pf_{{ $key }}">{{ config("eventner_features.{$key}.label") }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-text mb-0">Fitur terkunci otomatis setelah trial 3 hari berakhir. Tambah fitur baru di config/eventner_features.php.</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary px-4" wire:loading.attr="disabled">
                    <span wire:loading.remove><i class="ti ti-device-floppy me-1"></i> Simpan</span>
                    <span wire:loading><span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...</span>
                </button>
            </form>
        </div>
    </div>
</div>