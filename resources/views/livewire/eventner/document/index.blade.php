<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Juknis &amp; Dokumen</h4>
                    <p class="mb-0 text-muted small">Unggah juknis lomba, jadwal, denah, atau berkas lain. Dokumen akan tampil dan bisa diunduh di halaman publik event.</p>
                </div>
                <div class="col-3 text-end mb-n5"><img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" class="img-fluid mb-n4" style="max-height: 80px;"></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card"><div class="card-body">
                @if($editingId)
                    <h5 class="card-title fw-semibold mb-4">Ubah Dokumen</h5>
                    <form wire:submit="simpanPerubahan">
                        <div class="mb-3">
                            <label class="form-label">Judul</label>
                            <input class="form-control" wire:model="title" required>
                            @error('title')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" wire:model="description" rows="3" placeholder="Opsional"></textarea>
                            @error('description')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary" wire:click="batalkan">Batal</button>
                            <button class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                @else
                    <h5 class="card-title fw-semibold mb-4">Unggah Dokumen</h5>
                    <form wire:submit="upload">
                        <div class="mb-3">
                            <label class="form-label">Judul</label>
                            <input class="form-control" wire:model="title" placeholder="mis. Juknis PBB Umum" required>
                            @error('title')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Berkas</label>
                            <input type="file" class="form-control" wire:model="newFile"
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                            <div class="form-text">PDF, Word, Excel, atau gambar. Maksimal 10 MB.</div>
                            @error('newFile')<span class="text-danger small">{{ $message }}</span>@enderror
                            <div wire:loading wire:target="newFile" class="text-info small mt-1">Mengunggah...</div>
                            @if($newFile && in_array(strtolower($newFile->getClientOriginalExtension()), ['jpg','jpeg','png']))
                                <img src="{{ $newFile->temporaryUrl() }}" class="img-fluid rounded mt-2" style="max-height: 150px;" alt="Pratinjau berkas">
                            @endif
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" wire:model="description" rows="3" placeholder="Opsional"></textarea>
                            @error('description')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>
                        <button class="btn btn-primary w-100" wire:loading.attr="disabled" wire:target="newFile">
                            <i class="ti ti-upload me-1"></i> Unggah
                        </button>
                    </form>
                @endif
            </div></div>

            @if($replacingId)
                <div class="card"><div class="card-body">
                    <h6 class="fw-semibold mb-3">Ganti Berkas</h6>
                    <form wire:submit="gantiBerkas">
                        <input type="file" class="form-control mb-2" wire:model="replacementFile"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                        @error('replacementFile')<span class="text-danger small">{{ $message }}</span>@enderror
                        <div wire:loading wire:target="replacementFile" class="text-info small mb-2">Mengunggah...</div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary btn-sm" wire:click="batalkanGantiBerkas">Batal</button>
                            <button class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="replacementFile">Simpan Berkas</button>
                        </div>
                    </form>
                </div></div>
            @endif
        </div>

        <div class="col-lg-8">
            <div class="card"><div class="card-body p-4">
                @if($documents->isEmpty())
                    <div class="text-center py-5 text-muted">Belum ada dokumen.</div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">No</th>
                                    <th>Judul</th>
                                    <th style="width: 90px;">Ukuran</th>
                                    <th style="width: 90px;">Unduhan</th>
                                    <th style="width: 210px;" class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($documents as $d)
                                    <tr wire:key="doc-{{ $d->id }}">
                                        <td class="text-muted">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="d-flex align-items-start gap-2">
                                                <i class="ti {{ $d->ikon }} fs-5 text-muted"></i>
                                                <div class="min-w-0">
                                                    <div class="fw-semibold">
                                                        {{ $d->title }}
                                                        @if($d->is_primary)
                                                            <span class="badge bg-primary-subtle text-primary ms-1">Utama</span>
                                                        @endif
                                                    </div>
                                                    <small class="text-muted d-block text-truncate" style="max-width: 260px;" title="{{ $d->original_name }}">{{ $d->original_name }}</small>
                                                    @if($d->description)
                                                        <small class="text-muted d-block">{{ $d->description }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-muted small">{{ $d->ukuran_terbaca }}</td>
                                        <td class="text-muted small">{{ $d->download_count }}</td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                @unless($d->is_primary)
                                                    <button class="btn btn-outline-primary" title="Jadikan utama" wire:click="jadikanUtama({{ $d->id }})"><i class="ti ti-star"></i></button>
                                                @endunless
                                                <button class="btn btn-outline-secondary" title="Naikkan" wire:click="naikkan({{ $d->id }})"><i class="ti ti-arrow-up"></i></button>
                                                <button class="btn btn-outline-secondary" title="Turunkan" wire:click="turunkan({{ $d->id }})"><i class="ti ti-arrow-down"></i></button>
                                                <button class="btn btn-outline-primary" title="Ubah judul" wire:click="edit({{ $d->id }})"><i class="ti ti-pencil"></i></button>
                                                <button class="btn btn-outline-primary" title="Ganti berkas" wire:click="mulaiGantiBerkas({{ $d->id }})"><i class="ti ti-refresh"></i></button>
                                                <button class="btn btn-outline-danger" title="Hapus" wire:click="delete({{ $d->id }})" wire:confirm="Hapus dokumen ini? Berkasnya ikut terhapus."><i class="ti ti-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div></div>
        </div>
    </div>
</div>
