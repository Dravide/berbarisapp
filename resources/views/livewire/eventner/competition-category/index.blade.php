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

    <div class="row">
        <!-- Panel Daftar (Tree) -->
        <div class="col-lg-8">
            <div class="card w-100 position-relative overflow-hidden">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white fw-semibold">Struktur Jenis & Tingkat Lomba</h5>
                    <small class="text-white/75"><i class="ti ti-drag-drop"></i> Drag &amp; drop untuk urutkan</small>
                </div>
                <div class="card-body p-4">
                    @if($this->availableVenues->count() > 1)
                        <div class="alert alert-info bg-primary-subtle text-primary border-0 fs-2 py-2 mb-3">
                            <i class="ti ti-map-pin"></i>
                            Event ini punya {{ $this->availableVenues->count() }} tempat pelaksanaan. Tentukan tempat tiap tingkat lomba
                            supaya tertera di tiket peserta.
                        </div>
                    @endif
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
                                            wire:confirm="Hapus jenis lomba ini? Pastikan semua tingkat di dalamnya sudah dihapus.">
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
                                                        @php
                                                            $childGroups = $this->groupsByCategory->get($child->id, collect());
                                                            $childRounds = $this->roundsByCategory->get($child->id, collect());
                                                            $childPeserta = $child->registrations()->count();
                                                        @endphp
                                                        <div class="py-3 border-bottom" data-id="{{ $child->id }}">
                                                            <div class="d-flex align-items-center">
                                                                <i class="ti ti-grip-vertical text-muted me-2" style="cursor: grab;"></i>
                                                                <i class="ti ti-corner-down-right text-muted me-2"></i>
                                                                <div class="flex-grow-1">
                                                                    <h6 class="fw-semibold mb-0">{{ $child->name }}</h6>
                                                                    {{-- Satu baris fakta yang selalu ada: peserta, tanggal, juri.
                                                                         Sisa atribut opsional dipindah ke baris badge sehingga
                                                                         tiap tingkat selalu setinggi sama, bukan melar mengikuti
                                                                         berapa banyak yang kebetulan diisi panitia. --}}
                                                                    <span class="text-muted fs-2">
                                                                        {{ $childPeserta }} peserta
                                                                        @if($child->kuota) / {{ $child->kuota }} kuota @endif
                                                                        @if($child->tanggal_pelaksanaan)
                                                                            &bull; {{ \Carbon\Carbon::parse($child->tanggal_pelaksanaan)->translatedFormat('d M Y') }}
                                                                        @endif
                                                                        &bull; {{ $child->judges->isNotEmpty() ? $child->judges->pluck('name')->implode(', ') : 'belum ada juri' }}
                                                                    </span>
                                                                </div>
                                                                <button class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="edit({{ $child->id }})" title="Edit Tingkat">
                                                                    <i class="ti ti-edit fs-4"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-outline-danger p-1" wire:click="delete({{ $child->id }})" title="Hapus Tingkat"
                                                                    wire:confirm="Hapus tingkat lomba ini? Tingkat yang masih punya pendaftar tidak akan terhapus.">
                                                                    <i class="ti ti-trash fs-4"></i>
                                                                </button>
                                                            </div>
                                                            {{-- Baris badge: satu baris apa pun isinya. Grup & babak
                                                                 ikut di sini karena keduanya sekadar keadaan tingkat, bukan
                                                                 aksi; aksinya tetap satu tombol "Atur Babak & Grup". --}}
                                                            <div class="d-flex flex-wrap align-items-center gap-1 mt-1 ms-4 ps-3">
                                                                <span class="badge bg-light-info text-info">Max {{ $child->max_registrations_per_school ?? 1 }} pasukan/sekolah</span>
                                                                @if($child->registration_fee)
                                                                    <span class="badge bg-success-subtle text-success">Rp {{ number_format($child->registration_fee, 0, ',', '.') }}</span>
                                                                @else
                                                                    <span class="badge bg-light text-muted border">Gratis</span>
                                                                @endif
                                                                @if($child->venue)
                                                                    <span class="badge bg-light-warning text-warning"><i class="ti ti-map-pin"></i> {{ $child->venue->name }}</span>
                                                                @elseif($this->availableVenues->count() > 1)
                                                                    <span class="badge bg-danger-subtle text-danger"><i class="ti ti-map-pin-off"></i> Tempat belum ditentukan</span>
                                                                @endif
                                                                @foreach($childGroups as $group)
                                                                    <span class="badge bg-light-primary text-primary" title="Grup penilaian">
                                                                        <i class="ti ti-users-group"></i> {{ $group->name }} · {{ $group->registrations_count }} peserta
                                                                    </span>
                                                                @endforeach
                                                                @foreach($childRounds as $round)
                                                                    <span class="badge {{ $round->isFinal() ? 'bg-warning-subtle text-warning' : 'bg-light-secondary text-secondary' }}">
                                                                        <i class="ti ti-flag"></i> {{ $round->name }}
                                                                        @if($round->isFinal() && $round->round_registrations_count > 0)
                                                                            · {{ $round->round_registrations_count }} finalis
                                                                        @endif
                                                                    </span>
                                                                @endforeach
                                                                @if($child->judges->isEmpty())
                                                                    <span class="badge bg-warning-subtle text-warning">Belum ada juri</span>
                                                                @endif
                                                                {{-- Satu tombol untuk grup + babak: keduanya diatur di
                                                                     tempat yang sama, dan dua tombol terpisah di tiap
                                                                     tingkat membuat daftar terlihat jauh lebih ramai
                                                                     daripada isinya. Menampilkan yang belum dibuat itu
                                                                     yang sebelumnya mendorong tiap tingkat jadi 4 baris. --}}
                                                                <button class="btn btn-sm btn-outline-primary py-0 px-2 fs-2 ms-1" wire:click="openRoundPanel({{ $child->id }})">
                                                                    <i class="ti ti-settings"></i> Atur Babak &amp; Grup
                                                                </button>
                                                            </div>
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
                                                    @if($orphan->venue)
                                                        <span class="badge bg-light-warning text-warning"><i class="ti ti-map-pin"></i> {{ $orphan->venue->name }}</span>
                                                    @endif
                                                    @if($orphan->kuota)
                                                        <span class="badge bg-light-info text-info">{{ $orphan->registrations()->count() }} / {{ $orphan->kuota }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="edit({{ $orphan->id }})" title="Edit">
                                                <i class="ti ti-edit fs-4"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger p-1" wire:click="delete({{ $orphan->id }})" title="Hapus"
                                                wire:confirm="Hapus kategori lomba ini?">
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
                            <select class="form-select" wire:model="parentId">
                                <option value="">― Tidak ada (Jenis Lomba Utama) ―</option>
                                @foreach($this->allParents as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Saat edit, kamu bisa pindahkan kategori ini ke bawah Jenis Lomba lain (menjadi Tingkat/child).</small>
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
                                <label class="form-label">Tempat Lomba <span class="text-muted">(Opsional)</span></label>
                                <select class="form-select" wire:model="venueId">
                                    <option value="">&mdash; Belum ditentukan &mdash;</option>
                                    @foreach($this->availableVenues as $venue)
                                        <option value="{{ $venue->id }}">{{ $venue->name }}{{ $venue->alamat ? ' — ' . $venue->alamat : '' }}</option>
                                    @endforeach
                                </select>
                                @if($this->availableVenues->isEmpty())
                                    <small class="form-text text-muted">
                                        Belum ada tempat terdaftar.
                                        <a href="{{ route('eventner.venues.index') }}" class="text-primary">Tambah tempat</a> dulu
                                        bila lomba digelar di lebih dari satu lokasi.
                                    </small>
                                @else
                                    <small class="form-text text-muted">Pilih tempat tingkat lomba ini digelar. Kelola daftar tempat di menu Tempat Lomba.</small>
                                @endif
                                @error('venueId') <span class="text-danger fs-2 d-block mt-1">{{ $message }}</span> @enderror
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
                                Juri ditugaskan per <strong>grup peserta</strong>, bukan di layar ini:
                                buka tombol <strong>Atur</strong> pada tingkatnya, lalu centang jurinya di modal
                                Atur Babak, Grup &amp; Seri. Lembar nilai mengikuti seri masing-masing peserta.
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

    {{-- ── Modal Atur Babak, Grup & Seri ─────────────────────────────────── --}}
    @php
        $panelGroups = $this->panelCategory
            ? $this->groupsByCategory->get($this->panelCategory->id, collect())
            : collect();
        $panelSeries = $this->panelCategory
            ? $this->seriesByCategory->get($this->panelCategory->id, collect())
            : collect();
    @endphp
    @if($roundPanelCategoryId && $this->panelCategory && !$qualifyRoundId)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,.5); z-index:1050;">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-semibold">
                            Atur Babak, Grup &amp; Seri — {{ $this->panelCategory->name }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeRoundPanel"></button>
                    </div>
                    <div class="modal-body">

                        {{-- ── Bagian 1: Grup ────────────────────────────────────── --}}
                        <h6 class="fw-semibold mb-2"><i class="ti ti-users-group me-1"></i>Grup</h6>
                        <p class="text-muted fs-2">
                            Buat grup untuk membelah peserta jadi beberapa pool peringkat. Peserta yang belum
                            bergrup tetap dihitung pada peringkat umum. Grup <strong>tidak</strong> muncul di
                            formulir pendaftaran publik.
                        </p>

                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nama Grup</label>
                                <input type="text" class="form-control" wire:model="groupName" placeholder="Grup A">
                                @error('groupName') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Urutan</label>
                                <input type="number" class="form-control" wire:model="groupSortOrder" min="0" placeholder="0">
                                @error('groupSortOrder') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-primary w-100" wire:click="saveGroup">
                                    <i class="ti ti-{{ $editingGroupId ? 'device-floppy' : 'plus' }}"></i>
                                    {{ $editingGroupId ? 'Simpan' : 'Tambah Grup' }}
                                </button>
                            </div>
                        </div>

                        @if($editingGroupId)
                            <div class="mb-3">
                                <button type="button" class="btn btn-sm btn-light" wire:click="resetGroupForm">Batal edit</button>
                            </div>
                        @endif

                        @if($panelGroups->isEmpty() && $this->assignmentRows === [])
                            <p class="text-muted fs-2"><i>Belum ada grup. Tingkat ini dihitung sebagai satu peringkat.</i></p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Grup</th>
                                            <th>Peserta</th>
                                            <th>Juri yang menilai</th>
                                            <th class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($this->assignmentRows as $row)
                                            @php
                                                $juriBaris = $this->groupJudgeIds->get($row['key'], collect())->all();
                                                $adalahGrup = $row['scope'] === \App\Models\CompetitionGroup::SCOPE_GROUP;
                                            @endphp
                                            <tr>
                                                <td class="fw-semibold">
                                                    {{ $row['label'] }}
                                                    @unless($adalahGrup)
                                                        <span class="badge bg-light text-muted border ms-1">{{ $row['scope'] }}</span>
                                                    @endunless
                                                </td>
                                                <td>{{ $row['count'] }}</td>
                                                <td>
                                                    @if($this->availableJudges->isEmpty())
                                                        <span class="text-muted fs-2">Belum ada juri di event ini</span>
                                                    @else
                                                        <div class="d-flex flex-wrap gap-2">
                                                            @foreach($this->availableJudges as $juri)
                                                                <div class="form-check form-check-inline me-0">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        id="juri-{{ $row['key'] }}-{{ $juri->id }}"
                                                                        @checked(in_array($juri->id, $juriBaris, true))
                                                                        wire:change="toggleGroupJudge('{{ $row['scope'] }}', {{ $adalahGrup ? $row['group_id'] : 'null' }}, {{ $juri->id }}, $event.target.checked)">
                                                                    <label class="form-check-label fs-2" for="juri-{{ $row['key'] }}-{{ $juri->id }}">{{ $juri->name }}</label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        @if($juriBaris === [])
                                                            <span class="badge bg-warning-subtle text-warning mt-1">
                                                                <i class="ti ti-alert-triangle me-1"></i>Belum ada juri
                                                            </span>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($adalahGrup)
                                                        <button class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="editGroup({{ $row['group_id'] }})" title="Edit grup">
                                                            <i class="ti ti-edit fs-4"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger p-1" wire:click="deleteGroup({{ $row['group_id'] }})"
                                                            wire:confirm="Hapus grup {{ $row['label'] }}? Pesertanya tidak ikut terhapus, hanya kembali ke peringkat umum. Penugasan jurinya ikut dilepas." title="Hapus grup">
                                                            <i class="ti ti-trash fs-4"></i>
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <div class="alert alert-info bg-primary-subtle text-primary border-0 fs-2 py-2">
                            <i class="ti ti-info-circle"></i>
                            Centang juri yang menilai tiap baris di atas. <strong>Lembar nilainya tidak dipilih di
                            sini</strong>: setiap peserta memakai lembar sesuai <strong>serinya</strong>, yang
                            ditetapkan panitia saat <strong>Daftar Ulang</strong>. Satu juri grup karena itu bisa
                            menilai lebih dari satu seri.
                        </div>

                        <hr class="my-4">

                        {{-- ── Bagian 2: Seri ────────────────────────────────────── --}}
                        <h6 class="fw-semibold mb-2"><i class="ti ti-list-numbers me-1"></i>Seri</h6>
                        <p class="text-muted fs-2">
                            Seri adalah urutan perlombaan (mis. Seri A &amp; Seri B pada LOBB) dan
                            <strong>penentu lembar nilai</strong>: rubriknya mengikuti seri. Juri <strong>tidak</strong>
                            lagi diikat ke seri — penugasannya ada di bagian <strong>Grup</strong> di atas. Dua pasukan
                            di grup yang sama boleh memakai seri berbeda. Seri ditetapkan panitia saat daftar ulang.
                        </p>

                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nama Seri</label>
                                <input type="text" class="form-control" wire:model="seriesName" placeholder="Seri A">
                                @error('seriesName') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Urutan</label>
                                <input type="number" class="form-control" wire:model="seriesSortOrder" min="0" placeholder="0">
                                @error('seriesSortOrder') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-primary w-100" wire:click="saveSeries">
                                    <i class="ti ti-{{ $editingSeriesId ? 'device-floppy' : 'plus' }}"></i>
                                    {{ $editingSeriesId ? 'Simpan' : 'Tambah Seri' }}
                                </button>
                            </div>
                        </div>

                        @if($editingSeriesId)
                            <div class="mb-3">
                                <button type="button" class="btn btn-sm btn-light" wire:click="resetSeriesForm">Batal edit</button>
                            </div>
                        @endif

                        @if($panelSeries->isEmpty())
                            <p class="text-muted fs-2"><i>Belum ada seri. Seluruh peserta tingkat ini memakai lembar nilai yang sama.</i></p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Seri</th>
                                            <th>Peserta</th>
                                            <th>Rubrik</th>
                                            <th class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($panelSeries as $series)
                                            @php $seriesInfo = $this->seriesRubrics->get($series->id); @endphp
                                            <tr>
                                                <td class="fw-semibold">{{ $series->name }}</td>
                                                <td>{{ $series->registrations_count }}</td>
                                                <td>
                                                    @forelse($seriesInfo['rubrics'] ?? collect() as $nama)
                                                        <span class="badge bg-light-primary text-primary">{{ $nama }}</span>
                                                    @empty
                                                        <span class="text-muted fs-2">Belum ada — atur lewat Format Nilai</span>
                                                    @endforelse
                                                </td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="editSeries({{ $series->id }})" title="Edit seri">
                                                        <i class="ti ti-edit fs-4"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger p-1" wire:click="deleteSeries({{ $series->id }})"
                                                        wire:confirm="Hapus seri {{ $series->name }}? Pesertanya tidak ikut terhapus, hanya kembali memakai lembar nilai tanpa seri." title="Hapus seri">
                                                        <i class="ti ti-trash fs-4"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <div class="alert alert-info bg-primary-subtle text-primary border-0 fs-2 py-2">
                            <i class="ti ti-info-circle"></i>
                            Rubrik ditandai serinya di <strong>Format Nilai</strong>. Rubrik tanpa tanda seri
                            berlaku untuk semua seri.
                        </div>

                        <hr class="my-4">

                        {{-- ── Bagian 3: Babak ───────────────────────────────────── --}}
                        <h6 class="fw-semibold mb-2"><i class="ti ti-flag me-1"></i>Babak</h6>
                        <p class="text-muted fs-2">
                            Babak memisahkan rubrik penilaian. Babak bertipe <strong>Final</strong> hanya menilai
                            peserta yang diloloskan; juara final dihitung dari nilai babak final saja, sedangkan
                            nilai penyisihan hanya menentukan siapa yang lolos.
                        </p>

                        <div class="row g-2 align-items-end mb-4">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Nama Babak</label>
                                <input type="text" class="form-control" wire:model="roundName" placeholder="Final">
                                @error('roundName') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Jenis</label>
                                <select class="form-select" wire:model="roundType">
                                    <option value="preliminary">Penyisihan</option>
                                    <option value="final">Final</option>
                                </select>
                                @error('roundType') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Urutan</label>
                                <input type="number" class="form-control" wire:model="roundSortOrder" min="0" placeholder="0">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-primary w-100" wire:click="saveRound">
                                    <i class="ti ti-{{ $editingRoundId ? 'device-floppy' : 'plus' }}"></i>
                                    {{ $editingRoundId ? 'Simpan' : 'Tambah' }}
                                </button>
                            </div>
                        </div>
                        @if($editingRoundId)
                            <div class="mb-3">
                                <button type="button" class="btn btn-sm btn-light" wire:click="resetRoundForm">Batal edit</button>
                            </div>
                        @endif

                        <h6 class="fw-semibold mb-2">Daftar Babak</h6>
                        @php $panelRounds = $this->roundsByCategory->get($this->panelCategory->id, collect()); @endphp
                        @if($panelRounds->isEmpty())
                            <p class="text-muted fs-2"><i>Belum ada babak. Seluruh rubrik tingkat ini dianggap satu babak.</i></p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Babak</th>
                                            <th>Jenis</th>
                                            <th>Rubrik</th>
                                            <th>Finalis</th>
                                            <th class="text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($panelRounds as $round)
                                            @php
                                                $roundRubrics = $this->roundRubricNames->get($round->id, collect());
                                            @endphp
                                            <tr>
                                                <td class="fw-semibold">{{ $round->name }}</td>
                                                <td>
                                                    <span class="badge {{ $round->isFinal() ? 'bg-warning-subtle text-warning' : 'bg-light-secondary text-secondary' }}">
                                                        {{ $round->isFinal() ? 'Final' : 'Penyisihan' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @forelse($roundRubrics as $nama)
                                                        <span class="badge bg-light-primary text-primary">{{ $nama }}</span>
                                                    @empty
                                                        <span class="text-muted fs-2">Belum ditandai</span>
                                                    @endforelse
                                                </td>
                                                <td>
                                                    @if($round->isFinal())
                                                        {{ $round->round_registrations_count }} finalis
                                                    @else
                                                        <span class="text-muted fs-2">semua peserta</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($round->isFinal())
                                                        <button class="btn btn-sm btn-outline-warning py-0 px-2 fs-2 me-1" wire:click="openQualifyPanel({{ $round->id }})">
                                                            <i class="ti ti-arrow-up"></i> Loloskan Top-N
                                                        </button>
                                                    @endif
                                                    <button class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="editRound({{ $round->id }})" title="Edit babak">
                                                        <i class="ti ti-edit fs-4"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger p-1" wire:click="deleteRound({{ $round->id }})"
                                                        wire:confirm="Hapus babak {{ $round->name }}? Nilai yang sudah tersimpan tidak ikut terhapus." title="Hapus babak">
                                                        <i class="ti ti-trash fs-4"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <div class="alert alert-info bg-primary-subtle text-primary border-0 fs-2 py-2 mb-0">
                            <i class="ti ti-info-circle"></i>
                            Rubrik ditandai babaknya di <strong>Format Nilai</strong>. Tanpa tanda babak, rubrik
                            dianggap berlaku untuk semua babak.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" wire:click="closeRoundPanel">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Modal Loloskan Top-N ───────────────────────────────────────────── --}}
    @if($qualifyRoundId && $this->qualifyPreview['round'])
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,.5); z-index:1060;">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-semibold">
                            Loloskan ke Babak {{ $this->qualifyPreview['round']->name }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeQualifyPanel"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted fs-2">
                            Ambil N terbaik tiap grup dari nilai babak penyisihan. Daftar masih bisa diubah manual
                            dengan mencentang ulang sebelum disimpan.
                        </p>

                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Ambil N terbaik per grup</label>
                                <input type="number" class="form-control" wire:model.live="qualifyTopN" min="1">
                            </div>
                        </div>

                        @foreach($this->qualifyPreview['groups'] as $bucket)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-semibold mb-0">
                                        {{ $bucket['group']?->name ?? 'Belum bergrup' }}
                                        <span class="text-muted fw-normal fs-2">({{ count($bucket['rows']) }} peserta)</span>
                                    </h6>
                                    <button type="button" class="btn btn-sm btn-link py-0 px-1 fs-2 text-decoration-none"
                                        wire:click="toggleQualifyGroup({{ $bucket['group']?->id ?? '' }})">
                                        centang / lepas semua
                                    </button>
                                </div>
                                <div class="bg-light p-2 rounded border">
                                    @foreach($bucket['rows'] as $row)
                                        @php
                                            $rowId = $row['registration']->id;
                                            $sudahFinalis = in_array((int) $rowId, $this->qualifyPreview['sudahFinalis'], true);
                                        @endphp
                                        <div class="d-flex align-items-center gap-2 py-1">
                                            <input class="form-check-input mt-0" type="checkbox"
                                                wire:model="qualifySelection.{{ $rowId }}"
                                                wire:click.prevent="toggleQualifySelection({{ $rowId }})"
                                                id="q_{{ $rowId }}">
                                            <label class="form-check-label flex-grow-1" for="q_{{ $rowId }}">
                                                <span class="badge bg-light-secondary text-secondary">#{{ $row['rank'] }}</span>
                                                {{ $row['registration']->display_name }}
                                                <span class="text-muted fs-2">— nilai {{ $row['total'] }}</span>
                                                @if($sudahFinalis)
                                                    <span class="badge bg-success-subtle text-success">sudah finalis</span>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        @if(empty($this->qualifyPreview['groups']))
                            <p class="text-muted fs-2"><i>Belum ada peserta yang punya nilai pada babak penyisihan.</i></p>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" wire:click="closeQualifyPanel">Batal</button>
                        <button type="button" class="btn btn-primary" wire:click="saveQualify">
                            <i class="ti ti-arrow-up"></i> Loloskan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
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
