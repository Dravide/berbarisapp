<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Profil Event</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Pengaturan Event</li>
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
        <div class="card-body">
            <form wire:submit="save">
                <div class="row">
                    <div class="col-md-4 mb-4 text-center">
                        <div class="mb-4">
                            <h6 class="fw-semibold mb-3">Logo Event</h6>
                            <div class="mb-3">
                                @if ($newLogo)
                                    <img src="{{ $newLogo->temporaryUrl() }}" class="img-fluid rounded border p-1" style="max-height: 150px; object-fit: contain;">
                                @elseif ($logo)
                                    <img src="{{ asset('storage/' . $logo) }}" class="img-fluid rounded border p-1" style="max-height: 150px; object-fit: contain;">
                                @else
                                    <div class="bg-light rounded border d-flex align-items-center justify-content-center p-4 mx-auto" style="height: 150px; width: 150px;">
                                        <div class="text-muted text-center">
                                            <i class="ti ti-photo fs-8 d-block mb-2"></i>
                                            Belum ada logo
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="mb-3">
                                <label for="newLogo" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="ti ti-upload me-1"></i> Pilih Gambar Logo
                                </label>
                                <input type="file" id="newLogo" wire:model="newLogo" class="d-none" accept="image/jpeg, image/png, image/jpg">
                                @error('newLogo') <span class="text-danger fs-2 d-block mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <hr>

                        <div class="mt-4">
                            <h6 class="fw-semibold mb-3">Poster Event</h6>
                            <div class="mb-3">
                                @if ($newPoster)
                                    <img src="{{ $newPoster->temporaryUrl() }}" class="img-fluid rounded border p-1" style="max-height: 300px; object-fit: contain;">
                                @elseif ($poster)
                                    <img src="{{ asset('storage/' . $poster) }}" class="img-fluid rounded border p-1" style="max-height: 300px; object-fit: contain;">
                                @else
                                    <div class="bg-light rounded border d-flex align-items-center justify-content-center p-4 mx-auto" style="height: 200px; width: 100%;">
                                        <div class="text-muted text-center">
                                            <i class="ti ti-camera fs-8 d-block mb-2"></i>
                                            Belum ada poster
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="mb-3">
                                <label for="newPoster" class="btn btn-outline-info btn-sm w-100">
                                    <i class="ti ti-photo me-1"></i> Pilih Gambar Poster
                                </label>
                                <input type="file" id="newPoster" wire:model="newPoster" class="d-none" accept="image/jpeg, image/png, image/jpg">
                                @error('newPoster') <span class="text-danger fs-2 d-block mt-1">{{ $message }}</span> @enderror
                                <div wire:loading wire:target="newPoster" class="text-info fs-2 mt-2">
                                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengunggah...
                                </div>
                                <small class="d-block text-muted mt-2">Format: JPG/PNG. Maks: 3MB.</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <h6 class="fw-semibold mb-3">Informasi Acara</h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Event <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model="nama_event" required>
                                @error('nama_event') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Penyelenggara <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model="diselenggarakan_oleh" required>
                                @error('diselenggarakan_oleh') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Deskripsi Event <span class="text-muted">(Opsional)</span></label>
                                <textarea class="form-control" wire:model="deskripsi" rows="3" placeholder="Ceritakan singkat tentang acara Anda..."></textarea>
                                @error('deskripsi') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Lokasi Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model="lokasi" required>
                                @error('lokasi') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Venue (Opsional)</label>
                                <input type="text" class="form-control" wire:model="venue" placeholder="Misal: GOR Siliwangi">
                                @error('venue') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Puncak Tanggal Pelaksanaan <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" wire:model="tanggal" required>
                                @error('tanggal') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jadwal Pendaftaran Akhir (Opsional)</label>
                                <input type="date" class="form-control" wire:model="tanggal_pendaftaran">
                                @error('tanggal_pendaftaran') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Jadwal Technical Meeting (Opsional)</label>
                                <input type="datetime-local" class="form-control" wire:model="technical_meeting">
                                @error('technical_meeting') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Tingkat Perlombaan Global (Opsional)</label>
                                <input type="text" class="form-control" wire:model="tingkat_perlombaan" placeholder="Misal: Se-Jawa Barat Terbuka">
                                @error('tingkat_perlombaan') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Latitude <span class="text-muted">(Cth: -6.9147)</span></label>
                                <input type="text" class="form-control" wire:model="latitude" placeholder="-6.9147">
                                @error('latitude') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label">Longitude <span class="text-muted">(Cth: 107.6098)</span></label>
                                <input type="text" class="form-control" wire:model="longitude" placeholder="107.6098">
                                @error('longitude') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-12 mb-4">
                                <div class="alert alert-warning mb-0 border-0 fs-3 py-3">
                                    <h6 class="fw-semibold mb-2"><i class="ti ti-lock"></i> Kunci Layar Pengundian (Opsional)</h6>
                                    <label class="form-label mb-1">Maukah Anda memproteksi Halaman Spin Pengundian agar tidak semua orang bisa memutarnya?</label>
                                    <input type="text" class="form-control mt-1 w-50" wire:model="drawing_code" placeholder="Misal: 123456">
                                    <small class="form-text mt-1 d-block">Kosongkan jika Anda ingin layar pengundian bisa langsung diakses tanpa kode.</small>
                                    @error('drawing_code') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="fw-semibold mb-3">Tautan Tambahan <small class="text-muted">(Semua Opsional)</small></h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="ti ti-brand-instagram text-danger me-1"></i>Link Instagram</label>
                                <input type="url" class="form-control" wire:model="link_instagram" placeholder="https://instagram.com/...">
                                @error('link_instagram') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="ti ti-brand-tiktok text-dark me-1"></i>Link TikTok</label>
                                <input type="url" class="form-control" wire:model="link_tiktok" placeholder="https://tiktok.com/@...">
                                @error('link_tiktok') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="ti ti-brand-whatsapp text-success me-1"></i>No. / Link WhatsApp Contact Person</label>
                                <input type="text" class="form-control" wire:model="link_whatsapp" placeholder="https://wa.me/628...">
                                @error('link_whatsapp') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="ti ti-video text-primary me-1"></i>Link Live Streaming (Youtube/dsb)</label>
                                <input type="url" class="form-control" wire:model="link_livestreaming" placeholder="https://youtube.com/live/...">
                                @error('link_livestreaming') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-2">
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                                <i class="ti ti-device-floppy me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
