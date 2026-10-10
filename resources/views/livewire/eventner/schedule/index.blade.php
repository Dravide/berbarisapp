<div>
    {{-- Page Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <h4 class="fw-semibold mb-8">Jadwal Pertandingan</h4>
            <p class="mb-0 text-muted fs-2">
                Pertandingan per tingkat, grup, dan venue — tampil di halaman Rundown publik.
            </p>
        </div>
    </div>

    <div class="row">
        {{-- Daftar Jadwal --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-semibold mb-0 text-white">
                        <i class="ti ti-calendar-time me-2"></i> Daftar Jadwal ({{ $items->count() }})
                    </h5>
                    @if($items->count() > 0)
                        <a href="{{ event_url($eventner, 'rundown') }}" target="_blank" class="btn btn-sm btn-light">
                            <i class="ti ti-external-link me-1"></i> Lihat di Landing
                        </a>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        @if($items->count() > 0)
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-bottom-0 ps-4" width="60px"><h6 class="fw-semibold mb-0">#</h6></th>
                                        <th class="border-bottom-0" width="110px"><h6 class="fw-semibold mb-0">Tanggal</h6></th>
                                        <th class="border-bottom-0" width="120px"><h6 class="fw-semibold mb-0">Jam</h6></th>
                                        <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Pertandingan</h6></th>
                                        <th class="border-bottom-0" width="110px"><h6 class="fw-semibold mb-0">Venue</h6></th>
                                        <th class="border-bottom-0 text-center" width="150px"><h6 class="fw-semibold mb-0">Aksi</h6></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $i => $item)
                                        <tr wire:key="schedule-{{ $item->id }}">
                                            <td class="ps-4 text-muted">{{ $i + 1 }}</td>
                                            <td class="text-muted small">
                                                {{ $item->tanggal?->format('d M Y') ?? \Carbon\Carbon::parse($eventner->tanggal)->format('d M Y') }}
                                            </td>
                                            <td>
                                                <span class="badge bg-primary-subtle text-primary font-monospace px-2 py-1">
                                                    {{ $item->start_time?->format('H:i') }}
                                                    @if($item->end_time)
                                                        – {{ $item->end_time->format('H:i') }}
                                                    @endif
                                                </span>
                                            </td>
                                            <td>
                                                <h6 class="fw-semibold mb-0">{{ $item->title ?? $item->category?->name }}</h6>
                                                <span class="text-muted small">
                                                    {{ $item->category?->parent?->name ? $item->category->parent->name . ' — ' . $item->category->name : $item->category?->name }}
                                                    @if($item->group) · {{ $item->group->name }} @endif
                                                    @if($item->round) · {{ $item->round->name }} @endif
                                                </span>
                                            </td>
                                            <td class="text-muted small">
                                                @if($item->venue)
                                                    <i class="ti ti-map-pin me-1"></i>{{ $item->venue->name }}
                                                @else
                                                    —
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
                                                    <button class="btn btn-sm btn-outline-danger p-1" wire:click="delete({{ $item->id }})" wire:confirm="Hapus jadwal ini?" title="Hapus">
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
                                <i class="ti ti-calendar-time fs-10 text-muted d-block mb-3"></i>
                                <h5 class="fw-semibold text-muted">Belum Ada Jadwal</h5>
                                <p class="text-muted">Tambahkan manual atau generate dari hasil undian.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Tambah / Edit --}}
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title fw-semibold mb-0">
                        <i class="ti ti-edit me-1"></i> {{ $editingId ? 'Edit Jadwal' : 'Tambah Jadwal' }}
                    </h5>
                </div>
                <div class="card-body">
                    <form wire:submit="save">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tingkat Lomba <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" wire:model="categoryId">
                                <option value="">-- Pilih Tingkat --</option>
                                @foreach($categories as $cat)
                                    @php $catLabel = !empty($cat['parent']) ? $cat['parent']['name'] . ' — ' . $cat['name'] : $cat['name']; @endphp
                                    <option value="{{ $cat['id'] }}">{{ $catLabel }}</option>
                                @endforeach
                            </select>
                            @error('categoryId') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Grup</label>
                                <select class="form-select form-select-sm" wire:model="groupId">
                                    <option value="">— Semua —</option>
                                    @foreach($groups as $g)
                                        <option value="{{ $g['id'] }}">{{ $g['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Babak</label>
                                <select class="form-select form-select-sm" wire:model="roundId">
                                    <option value="">— Penyisihan —</option>
                                    @foreach($rounds as $r)
                                        <option value="{{ $r['id'] }}">{{ $r['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
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
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Tanggal</label>
                                <input type="date" class="form-control form-control-sm" wire:model="tanggal">
                                @error('tanggal') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Venue</label>
                                <select class="form-select form-select-sm" wire:model="venueId">
                                    <option value="">— Tanpa venue —</option>
                                    @foreach($venues as $v)
                                        <option value="{{ $v['id'] }}">{{ $v['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Judul</label>
                            <input type="text" class="form-control form-control-sm" wire:model="title" placeholder="Opsional — bila kosong pakai nama tingkat">
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
                    <p class="text-muted small">Buat jadwal otomatis dari urutan undian tingkat terpilih. Venue diambil dari pengaturan venue tingkat; jam disusun berantai sesuai durasi.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tingkat Lomba <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" wire:model="importCategoryId">
                            <option value="">-- Pilih Tingkat --</option>
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
                            <input type="number" min="1" max="600" class="form-control form-control-sm" wire:model="importDefaultDuration" placeholder="30">
                            @error('importDefaultDuration') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <button type="button"
                        class="btn btn-warning btn-sm w-100 fw-semibold"
                        wire:click="generateFromDrawing"
                        wire:confirm="Jadwal generate lama untuk tingkat ini akan dihapus dan dibuat ulang. Lanjutkan?">
                        <i class="ti ti-wand me-1"></i> Generate
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
