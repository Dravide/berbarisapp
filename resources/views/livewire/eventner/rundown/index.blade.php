<div>
    {{-- Page Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Rundown Acara</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Rundown Acara</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success border-0 bg-success-subtle text-success alert-dismissible fade show">
            <i class="ti ti-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger-subtle text-danger alert-dismissible fade show">
            <i class="ti ti-alert-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        {{-- Daftar Rundown --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-semibold mb-0 text-white">
                        <i class="ti ti-list-details me-2"></i> Daftar Rundown ({{ $items->count() }})
                    </h5>
                    @if($items->count() > 0)
                        <div class="d-flex gap-2">
                            <a href="{{ route('eventner.rundown.print') }}" target="_blank" class="btn btn-sm btn-light">
                                <i class="ti ti-file-type-pdf me-1"></i> Unduh PDF
                            </a>
                            <a href="{{ event_url($eventner, 'rundown') }}" target="_blank" class="btn btn-sm btn-light">
                                <i class="ti ti-external-link me-1"></i> Lihat di Landing
                            </a>
                        </div>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        @if($items->count() > 0)
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-bottom-0 ps-4" width="60px"><h6 class="fw-semibold mb-0">#</h6></th>
                                        <th class="border-bottom-0" width="140px"><h6 class="fw-semibold mb-0">Jam</h6></th>
                                        <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Judul</h6></th>
                                        <th class="border-bottom-0" width="100px"><h6 class="fw-semibold mb-0">Durasi</h6></th>
                                        <th class="border-bottom-0 text-center" width="150px"><h6 class="fw-semibold mb-0">Aksi</h6></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $i => $item)
                                        <tr wire:key="rundown-{{ $item->id }}">
                                            <td class="ps-4 text-muted">{{ $i + 1 }}</td>
                                            <td>
                                                <span class="badge bg-primary-subtle text-primary font-monospace px-2 py-1">
                                                    {{ $item->start_time?->format('H:i') }}
                                                    @if($item->end_time)
                                                        – {{ $item->end_time->format('H:i') }}
                                                    @endif
                                                </span>
                                            </td>
                                            <td>
                                                <h6 class="fw-semibold mb-0">{{ $item->title }}</h6>
                                                @if($item->description)
                                                    <span class="text-muted small">{{ $item->description }}</span>
                                                @endif
                                                @if($item->source_category_id)
                                                    <span class="badge bg-warning-subtle text-warning-emphasis mt-1 py-0 px-2" style="font-size: 0.65rem;">
                                                        <i class="ti ti-arrows-shuffle me-1"></i>{{ $item->sourceCategory?->parent?->name ? $item->sourceCategory->parent->name . ' — ' . $item->sourceCategory->name : $item->sourceCategory?->name ?? 'Undian' }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-light text-muted mt-1 py-0 px-2 border" style="font-size: 0.65rem;">
                                                        <i class="ti ti-edit me-1"></i>Manual
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($item->source_category_id)
                                                    <div class="d-flex align-items-center gap-1">
                                                        <input type="number" min="1" max="600"
                                                            wire:model="durations.{{ $item->id }}"
                                                            wire:change="updateDuration({{ $item->id }})"
                                                            class="form-control form-control-sm text-center" style="width:70px;"
                                                            title="Durasi tampil (menit)">
                                                        <span class="text-muted small">mnt</span>
                                                    </div>
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <button class="btn btn-sm btn-outline-secondary p-1" wire:click="moveUp({{ $item->id }})" title="Naik" @if($i === 0) disabled @endif>
                                                        <i class="ti ti-arrow-up"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary p-1" wire:click="moveDown({{ $item->id }})" title="Turun" @if($i === $items->count() - 1) disabled @endif>
                                                        <i class="ti ti-arrow-down"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-warning p-1" wire:click="edit({{ $item->id }})" title="Edit">
                                                        <i class="ti ti-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger p-1" wire:click="delete({{ $item->id }})" onclick="return confirm('Hapus item rundown ini?')" title="Hapus">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="text-center py-5">
                                <i class="ti ti-list-details fs-10 text-muted d-block mb-3"></i>
                                <h5 class="fw-semibold text-muted">Belum Ada Rundown</h5>
                                <p class="text-muted">Tambahkan item manual atau generate dari hasil undian.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Tambah / Edit Item Manual --}}
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title fw-semibold mb-0">
                        <i class="ti ti-edit me-1"></i> {{ $editingId ? 'Edit Item' : 'Tambah Item Manual' }}
                    </h5>
                </div>
                <div class="card-body">
                    <form wire:submit="save">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Judul <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" wire:model="title" placeholder="Misal: Pembukaan">
                            @error('title') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Mulai <span class="text-danger">*</span></label>
                                <input type="time" class="form-control form-control-sm" wire:model="startTime">
                                @error('startTime') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Selesai</label>
                                <input type="time" class="form-control form-control-sm" wire:model="endTime">
                                @error('endTime') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Keterangan</label>
                            <textarea class="form-control form-control-sm" wire:model="description" rows="2" placeholder="Opsional"></textarea>
                            @error('description') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="ti ti-device-floppy me-1"></i> {{ $editingId ? 'Perbarui' : 'Simpan' }}
                            </button>
                            @if($editingId)
                                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="$set('editingId', null)">
                                    Batal
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            {{-- Generate dari Undian --}}
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title fw-semibold mb-0">
                        <i class="ti ti-arrows-shuffle me-1"></i> Generate dari Undian
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Buat item rundown otomatis dari hasil undian (urutan tampil) kategori terpilih. Jam disusun berantai sesuai durasi tiap pasukan.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kategori Lomba <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" wire:model.live="importCategoryId">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $cat)
                                @php $catLabel = !empty($cat['parent']) ? $cat['parent']['name'] . ' — ' . $cat['name'] : $cat['name']; @endphp
                                <option value="{{ $cat['id'] }}">{{ $catLabel }}</option>
                            @endforeach
                        </select>
                        @error('importCategoryId') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Jam Mulai <span class="text-danger">*</span></label>
                            <input type="time" class="form-control form-control-sm" wire:model="importStartTime">
                            @error('importStartTime') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Durasi/Pasukan <span class="text-danger">*</span></label>
                            <input type="number" min="1" max="600" class="form-control form-control-sm" wire:model="importDefaultDuration" placeholder="10">
                            @error('importDefaultDuration') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <button type="button"
                        class="btn btn-warning btn-sm w-100 fw-semibold"
                        wire:click="generateFromDrawing"
                        wire:confirm="Item rundown dari undian kategori ini akan dibuat ulang (yang lama diganti). Lanjutkan?">
                        <i class="ti ti-arrows-shuffle me-1"></i> Generate Rundown
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
