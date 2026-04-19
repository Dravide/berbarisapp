<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Kategori Lomba (Tingkat)</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Kategori Tingkat Lomba</li>
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

    <div class="row">
        <!-- Panel List Tingkatan -->
        <div class="col-lg-8">
            <div class="card w-100 position-relative overflow-hidden">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white fw-semibold">Daftar Tingkat Lomba</h5>
                </div>
                <div class="card-body p-4">
                    @if($this->categories->isEmpty())
                        <div class="text-center py-5">
                            <h5 class="fw-semibold text-muted">Belum ada Kategori Lomba</h5>
                            <p>Contoh tingkat lomba: "SD / MI Sederajat", "SMP Sederajat". Silakan tambahkan pada form.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle text-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-0 border-0 fw-semibold text-dark">Nama Kategori Tingkat</th>
                                        <th class="border-0 fw-semibold text-dark">Tanggal Pelaksanaan</th>
                                        <th class="border-0 fw-semibold text-dark text-center">Kuota Peserta</th>
                                        <th class="border-0 fw-semibold text-dark">Juri yang Bertugas</th>
                                        <th class="border-0 fw-semibold text-dark text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($this->categories as $cat)
                                        <tr>
                                            <td class="ps-0">
                                                <h6 class="fw-semibold mb-1 text-primary">{{ $cat->name }}</h6>
                                            </td>
                                            <td>
                                                @if($cat->tanggal_pelaksanaan)
                                                    <span class="badge bg-light-primary text-primary"><i class="ti ti-calendar"></i> {{ \Carbon\Carbon::parse($cat->tanggal_pelaksanaan)->translatedFormat('d M Y') }}</span>
                                                @else
                                                    <span class="text-muted fs-2">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($cat->kuota)
                                                    <span class="fw-semibold">{{ $cat->registrations()->count() }} / {{ $cat->kuota }}</span>
                                                @else
                                                    <span class="text-muted fs-2">Tanpa Batas</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    @if($cat->judges->isEmpty())
                                                        <span class="badge bg-warning-subtle text-warning">Belum ada juri ditugaskan</span>
                                                    @else
                                                        @foreach($cat->judges as $judge)
                                                            <span class="badge bg-info-subtle text-info border border-info border-opacity-25">{{ $judge->name }}</span>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="edit({{ $cat->id }})" title="Edit / Pilih Juri">
                                                    <i class="ti ti-edit fs-4"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger p-1" wire:click="delete({{ $cat->id }})" title="Hapus Kategori Lomba" onclick="return confirm('Hapus Kategori Lomba ini?') || event.stopImmediatePropagation()">
                                                    <i class="ti ti-trash fs-4"></i>
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

        <!-- Panel Form -->
        <div class="col-lg-4">
            <div class="card w-100">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-4">{{ $isEditMode ? 'Edit Tingkat Lomba' : 'Tambah Tingkat Lomba' }}</h5>
                    <form wire:submit="save">
                        <div class="mb-3">
                            <label class="form-label">Nama Tingkat Lomba</label>
                            <input type="text" class="form-control" wire:model="name" placeholder="Misal: SMP / Sederajat" required>
                            @error('name') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggal Pelaksanaan <span class="text-muted">(Opsional)</span></label>
                            <input type="date" class="form-control" wire:model="tanggal_pelaksanaan">
                            @error('tanggal_pelaksanaan') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Batas Kuota Peserta <span class="text-muted">(Opsional)</span></label>
                            <input type="number" class="form-control" wire:model="kuota" placeholder="Misal: 50" min="1">
                            <small class="form-text text-muted">Kosongkan jika tidak ada batasan kuota.</small>
                            @error('kuota') <span class="text-danger fs-2 d-block mt-1">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="alert alert-info bg-primary-subtle text-primary border-0 fs-2 py-2 mb-3">
                            Tentukan Juri siapa saja yang akan menilai penampilan peserta khusus untuk Tingkat ini.
                        </div>

                        <h6 class="fw-semibold mb-3">Pilih Juri Penilai:</h6>
                        <div class="mb-4">
                            @if($this->availableJudges->isEmpty())
                                <p class="text-muted fs-2"><i>Belum ada Juri. Silakan tambah juri di menu Daftar Juri.</i></p>
                            @else
                                <div class="bg-light p-3 rounded border" style="max-height: 250px; overflow-y: auto;">
                                    @foreach($this->availableJudges as $judge)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" wire:model="selectedJudges" value="{{ $judge->id }}" id="j_{{ $judge->id }}">
                                            <label class="form-check-label fw-medium d-block" for="j_{{ $judge->id }}">
                                                {{ $judge->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            @error('selectedJudges') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>

                        <div class="d-flex gap-2">
                            @if($isEditMode)
                                <button type="button" class="btn btn-secondary flex-fill" wire:click="resetForm">Batal</button>
                            @endif
                            <button type="submit" class="btn btn-primary flex-fill" wire:loading.attr="disabled">
                                <i class="ti ti-{{ $isEditMode ? 'device-floppy' : 'plus' }}"></i> {{ $isEditMode ? 'Simpan' : 'Tambahkan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
