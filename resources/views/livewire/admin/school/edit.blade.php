<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Edit Sekolah</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.schools.index') }}">Data Sekolah</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.schools.show', $npsn) }}">{{ $nama_sekolah }}</a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">Edit</li>
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

    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form wire:submit.prevent="save">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title fw-semibold mb-4">Informasi Sekolah</h5>

                        <div class="mb-4">
                            <label for="nama_sekolah" class="form-label">Nama Sekolah</label>
                            <input type="text" class="form-control @error('nama_sekolah') is-invalid @enderror" id="nama_sekolah" wire:model="nama_sekolah">
                            @error('nama_sekolah') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="no_hp" class="form-label">No. Handphone</label>
                            <input type="text" class="form-control @error('no_hp') is-invalid @enderror" id="no_hp" wire:model="no_hp">
                            @error('no_hp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="school_email" class="form-label">Email Sekolah</label>
                            <input type="email" class="form-control @error('school_email') is-invalid @enderror" id="school_email" wire:model="school_email">
                            @error('school_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title fw-semibold mb-4">Logo Sekolah</h5>

                        @if($existing_logo)
                            <div class="mb-3 text-center">
                                <img src="{{ Storage::url($existing_logo) }}" alt="Logo saat ini" class="img-thumbnail" style="max-height: 120px;">
                                <p class="text-muted mt-2 mb-0" style="font-size: 0.75rem;">Logo saat ini</p>
                            </div>
                        @endif

                        @if($newLogo)
                            <div class="mb-3 text-center">
                                <img src="{{ $newLogo->temporaryUrl() }}" alt="Preview" class="img-thumbnail" style="max-height: 120px;">
                                <p class="text-success mt-2 mb-0" style="font-size: 0.75rem;">Preview logo baru</p>
                            </div>
                        @endif

                        <div class="mb-0">
                            <label for="newLogo" class="form-label">Upload Logo Baru</label>
                            <input type="file" class="form-control @error('newLogo') is-invalid @enderror" id="newLogo" wire:model="newLogo" accept="image/*">
                            @error('newLogo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Format: JPG, PNG, SVG, GIF, WebP. Maks 3MB. Akan dikonversi ke PNG transparan (max 512px).</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title fw-semibold mb-4">Aksi</h5>
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="ti ti-device-floppy me-1"></i> Simpan
                        </button>
                        <a href="{{ route('admin.schools.show', $npsn) }}" class="btn btn-outline-secondary w-100">
                            <i class="ti ti-arrow-left me-1"></i> Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
