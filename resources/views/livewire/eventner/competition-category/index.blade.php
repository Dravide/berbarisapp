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

    @if (session()->has('error'))
        <div class="alert alert-danger border-0 bg-danger-subtle text-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Panel Daftar (Tree) -->
        <div class="col-lg-8">
            <div class="card w-100 position-relative overflow-hidden">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white fw-semibold">Struktur Jenis & Tingkat Lomba</h5>
                    <small class="text-white/75"><i class="ti ti-drag-drop"></i> Drag &amp; drop untuk urutkan</small>
                </div>
                <div class="card-body p-4">
                    @if($this->parentCategories->isEmpty() && $this->orphanChildren->isEmpty())
                        <div class="text-center py-5">
                            <h5 class="fw-semibold text-muted">Belum ada Kategori Lomba</h5>
                            <p>Tambahkan Jenis Lomba (Parent) terlebih dahulu, lalu tambahkan Tingkat di dalamnya.</p>
                        </div>
                    @else
                        <div id="parent-sortable">
                            @foreach($this->parentCategories as $parent)
                                <div class="mb-3" data-id="{{ $parent->id }}">
                                    <div class="d-flex align-items-center bg-light p-3 rounded border sortable-handle">
                                        <i class="ti ti-grip-vertical text-muted me-2" style="cursor: grab;"></i>
                                        <button class="btn btn-sm btn-link text-decoration-none text-dark me-2 p-0" wire:click="toggleExpand({{ $parent->id }})">
                                            <i class="ti ti-{{ in_array($parent->id, $expandedParents) ? 'chevron-down' : 'chevron-right' }} fs-5"></i>
                                        </button>
                                        <div class="flex-grow-1">
                                            <h6 class="fw-bold mb-0 text-primary">{{ $parent->name }}</h6>
                                            <span class="text-muted fs-2">{{ $parent->children->count() }} tingkat lomba</span>
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="edit({{ $parent->id }})" title="Edit Jenis">
                                            <i class="ti ti-edit fs-4"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger p-1" wire:click="delete({{ $parent->id }})" title="Hapus Jenis Lomba"
                                            onclick="return confirm('Hapus jenis lomba ini? Pastikan semua tingkat di dalamnya sudah dihapus.') || event.stopImmediatePropagation()">
                                            <i class="ti ti-trash fs-4"></i>
                                        </button>
                                    </div>

                                    @if(in_array($parent->id, $expandedParents))
                                        <div class="ms-4 mt-2 border-start border-2 border-primary ps-3">
                                            @if($parent->children->isEmpty())
                                                <p class="text-muted fs-3 py-2"><i>Belum ada tingkat lomba. Gunakan form untuk menambahkan.</i></p>
                                            @else
                                                <div class="child-sortable" data-parent="{{ $parent->id }}">
                                                    @foreach($parent->children as $child)
                                                        <div class="d-flex align-items-center py-2 border-bottom" data-id="{{ $child->id }}">
                                                            <i class="ti ti-grip-vertical text-muted me-2" style="cursor: grab;"></i>
                                                            <i class="ti ti-corner-down-right text-muted me-2"></i>
                                                            <div class="flex-grow-1">
                                                                <h6 class="fw-semibold mb-0">{{ $child->name }}</h6>
                                                                <div class="d-flex flex-wrap gap-2 mt-1">
                                                                    @if($child->tanggal_pelaksanaan)
                                                                        <span class="badge bg-light-primary text-primary"><i class="ti ti-calendar"></i> {{ \Carbon\Carbon::parse($child->tanggal_pelaksanaan)->translatedFormat('d M Y') }}</span>
                                                                    @endif
                                                                    @if($child->kuota)
                                                                        <span class="badge bg-light-info text-info">{{ $child->registrations()->count() }} / {{ $child->kuota }}</span>
                                                                    @else
                                                                        <span class="badge bg-light text-muted">Tanpa Batas</span>
                                                                    @endif
                                                                    @if($child->registration_fee)
                                                                        <span class="badge bg-success-subtle text-success">Rp {{ number_format($child->registration_fee, 0, ',', '.') }}</span>
                                                                    @else
                                                                        <span class="badge bg-light text-muted border">Gratis</span>
                                                                    @endif
                                                                    <span class="badge bg-info-subtle text-info">Max {{ $child->max_registrations_per_school ?? 1 }} pasukan/sekolah</span>
                                                                </div>
                                                                @if($child->judges->isNotEmpty())
                                                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                                                        @foreach($child->judges as $judge)
                                                                            <span class="badge bg-secondary-subtle text-secondary border">{{ $judge->name }}</span>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <span class="badge bg-warning-subtle text-warning fs-2 mt-1">Belum ada juri</span>
                                                                @endif
                                                            </div>
                                                            <button class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="edit({{ $child->id }})" title="Edit Tingkat">
                                                                <i class="ti ti-edit fs-4"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger p-1" wire:click="delete({{ $child->id }})" title="Hapus Tingkat"
                                                                onclick="return confirm('Hapus tingkat lomba ini?') || event.stopImmediatePropagation()">
                                                                <i class="ti ti-trash fs-4"></i>
                                                            </button>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Orphan children (jika ada data lama tanpa parent) --}}
                        @if($this->orphanChildren->isNotEmpty())
                            <div class="mt-3 pt-3 border-top">
                                <h6 class="fw-semibold text-muted mb-2"><i class="ti ti-alert-circle me-1"></i>Tingkat Lomba Tanpa Jenis (Data Lama)</h6>
                                <div id="orphan-sortable" wire:ignore>
                                    @foreach($this->orphanChildren as $orphan)
                                        <div class="d-flex align-items-center py-2 border-bottom" data-id="{{ $orphan->id }}">
                                            <i class="ti ti-grip-vertical text-muted me-2" style="cursor: grab;"></i>
                                            <div class="flex-grow-1">
                                                <h6 class="fw-semibold mb-0">{{ $orphan->name }}</h6>
                                                <div class="d-flex flex-wrap gap-2 mt-1">
                                                    @if($orphan->kuota)
                                                        <span class="badge bg-light-info text-info">{{ $orphan->registrations()->count() }} / {{ $orphan->kuota }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="edit({{ $orphan->id }})" title="Edit">
                                                <i class="ti ti-edit fs-4"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger p-1" wire:click="delete({{ $orphan->id }})" title="Hapus"
                                                onclick="return confirm('Hapus kategori lomba ini?') || event.stopImmediatePropagation()">
                                                <i class="ti ti-trash fs-4"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <!-- Panel Form -->
        <div class="col-lg-4">
            <div class="card w-100">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-4">
                        {{ $isEditMode ? 'Edit Kategori' : 'Tambah Kategori' }}
                    </h5>
                    <form wire:submit="save">
                        {{-- Pilih Jenis Lomba (Parent) --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jenis Lomba <span class="text-muted">(Parent)</span></label>
                            <select class="form-select" wire:model="parentId" {{ $isEditMode ? 'disabled' : '' }}>
                                <option value="">― Tidak ada (Jenis Lomba Utama) ―</option>
                                @foreach($this->allParents as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                            @if($isEditMode && $parentId)
                                <input type="hidden" wire:model="parentId">
                            @endif
                            <small class="form-text text-muted">Pilih "Tidak ada" untuk membuat jenis lomba baru, atau pilih jenis yang sudah ada untuk menambahkan tingkat.</small>
                            @error('parentId') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">{{ is_null($parentId) ? 'Nama Jenis Lomba' : 'Nama Tingkat Lomba' }}</label>
                            <input type="text" class="form-control" wire:model="name" placeholder="{{ is_null($parentId) ? 'Misal: LOBA, Pengibaran' : 'Misal: SD, SMP, SMA' }}" required>
                            @error('name') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>

                        {{-- Field khusus Child --}}
                        @if(!is_null($parentId))
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

                            <div class="mb-3">
                                <label class="form-label">Max Pasukan per Sekolah</label>
                                <input type="number" class="form-control" wire:model="max_registrations_per_school" min="1" max="20">
                                <small class="form-text text-muted">Berapa pasukan yang boleh didaftarkan 1 sekolah.</small>
                                @error('max_registrations_per_school') <span class="text-danger fs-2 d-block mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Biaya Pendaftaran <span class="text-muted">(Opsional)</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" wire:model="registration_fee" placeholder="0" min="0" step="5000">
                                </div>
                                <small class="form-text text-muted">Kosongkan atau isi 0 jika pendaftaran gratis untuk kategori ini.</small>
                                @error('registration_fee') <span class="text-danger fs-2 d-block mt-1">{{ $message }}</span> @enderror
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
                        @endif

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

@push('scripts')
<script>
function initSortables() {
    // Destroy existing before re-create
    ['parent-sortable', 'orphan-sortable'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el && el._sortable) {
            el._sortable.destroy();
            delete el._sortable;
        }
    });
    document.querySelectorAll('.child-sortable').forEach(function(el) {
        if (el._sortable) {
            el._sortable.destroy();
            delete el._sortable;
        }
    });

    // Parents
    var parentEl = document.getElementById('parent-sortable');
    if (parentEl) {
        parentEl._sortable = new Sortable(parentEl, {
            handle: '.sortable-handle',
            animation: 200,
            onEnd: function(evt) {
                var ids = [];
                parentEl.querySelectorAll(':scope > .mb-3').forEach(function(el) {
                    ids.push(el.dataset.id);
                });
                Livewire.dispatch('updateParentSort', { orderedIds: ids });
            }
        });
    }

    // Children per parent
    document.querySelectorAll('.child-sortable').forEach(function(el) {
        el._sortable = new Sortable(el, {
            handle: '.ti-grip-vertical',
            animation: 200,
            onEnd: function(evt) {
                var ids = [];
                el.querySelectorAll(':scope > .d-flex').forEach(function(item) {
                    ids.push(item.dataset.id);
                });
                Livewire.dispatch('updateChildSort', { orderedIds: ids });
            }
        });
    });

    // Orphans
    var orphanEl = document.getElementById('orphan-sortable');
    if (orphanEl) {
        orphanEl._sortable = new Sortable(orphanEl, {
            handle: '.ti-grip-vertical',
            animation: 200,
            onEnd: function(evt) {
                var ids = [];
                orphanEl.querySelectorAll(':scope > .d-flex').forEach(function(item) {
                    ids.push(item.dataset.id);
                });
                Livewire.dispatch('updateChildSort', { orderedIds: ids });
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initSortables();
    Livewire.hook('morph.added', () => initSortables());
    Livewire.hook('morph.updated', () => initSortables());
});
</script>
@endpush
