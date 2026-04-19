<div>
    <div class="row">
        <div class="col-12">
            <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
                <div class="card-body px-4 py-3">
                    <div class="row align-items-center">
                        <div class="col-9">
                            <h4 class="fw-semibold mb-8">Pengaturan Situs</h4>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                                    </li>
                                    <li class="breadcrumb-item" aria-current="page">Pengaturan</li>
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
                        <!-- General Settings -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title fw-semibold mb-4">Umum</h5>
                                <div class="mb-4">
                                    <label for="site_title" class="form-label">Judul Situs</label>
                                    <input type="text" class="form-control @error('site_title') is-invalid @enderror" id="site_title" wire:model="site_title">
                                    @error('site_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="mb-4">
                                    <label for="meta_description" class="form-label">Deskripsi Situs (SEO)</label>
                                    <textarea class="form-control @error('meta_description') is-invalid @enderror" id="meta_description" rows="3" wire:model="meta_description"></textarea>
                                    @error('meta_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="mb-0">
                                    <label for="meta_keywords" class="form-label">Keywords (SEO)</label>
                                    <input type="text" class="form-control @error('meta_keywords') is-invalid @enderror" id="meta_keywords" wire:model="meta_keywords" placeholder="keyword1, keyword2, ...">
                                    @error('meta_keywords') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Logo Settings -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title fw-semibold mb-4">Logo & Favicon</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Logo Gelap (Dark Mode Sidebar)</label>
                                        <div class="mb-3">
                                            @if ($new_logo_dark)
                                                @php
                                                    $previewUrl = null;
                                                    try { $previewUrl = $new_logo_dark->temporaryUrl(); } catch (\Exception $e) {}
                                                @endphp
                                                @if($previewUrl)
                                                    <img src="{{ $previewUrl }}" class="img-fluid rounded border p-2 mb-2" style="max-height: 100px;">
                                                @else
                                                    <div class="border rounded p-4 text-center text-muted mb-2">Pratinjau SVG tidak tersedia (Simpan untuk melihat)</div>
                                                @endif
                                            @elseif($logo_dark_path)
                                                <img src="{{ Storage::url($logo_dark_path) }}" class="img-fluid rounded border p-2 mb-2" style="max-height: 100px;">
                                            @else
                                                <div class="border rounded p-4 text-center text-muted mb-2">Belum ada logo</div>
                                            @endif
                                        </div>
                                        <input type="file" class="form-control @error('new_logo_dark') is-invalid @enderror" wire:model="new_logo_dark">
                                        @error('new_logo_dark') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        <div class="form-text">Rekomendasi format SVG atau PNG transparan.</div>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Logo Terang (Light Mode Sidebar)</label>
                                        <div class="mb-3">
                                            @if ($new_logo_light)
                                                @php
                                                    $previewUrl = null;
                                                    try { $previewUrl = $new_logo_light->temporaryUrl(); } catch (\Exception $e) {}
                                                @endphp
                                                @if($previewUrl)
                                                    <img src="{{ $previewUrl }}" class="img-fluid rounded border p-2 mb-2 bg-dark" style="max-height: 100px;">
                                                @else
                                                    <div class="border rounded p-4 text-center text-muted mb-2 bg-dark">Pratinjau SVG tidak tersedia (Simpan untuk melihat)</div>
                                                @endif
                                            @elseif($logo_light_path)
                                                <img src="{{ Storage::url($logo_light_path) }}" class="img-fluid rounded border p-2 mb-2 bg-dark" style="max-height: 100px;">
                                            @else
                                                <div class="border rounded p-4 text-center text-muted mb-2 bg-dark">Belum ada logo</div>
                                            @endif
                                        </div>
                                        <input type="file" class="form-control @error('new_logo_light') is-invalid @enderror" wire:model="new_logo_light">
                                        @error('new_logo_light') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        <div class="form-text">Rekomendasi format SVG atau PNG transparan.</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Favicon</label>
                                        <div class="mb-3 flex align-items-center gap-3">
                                            @if ($new_favicon)
                                                @php
                                                    $previewUrl = null;
                                                    try { $previewUrl = $new_favicon->temporaryUrl(); } catch (\Exception $e) {}
                                                @endphp
                                                @if($previewUrl)
                                                    <img src="{{ $previewUrl }}" class="rounded shadow-sm" style="width: 32px; height: 32px;">
                                                @else
                                                    <div class="border rounded p-1 text-center text-muted d-inline-block" style="width: 32px; height: 32px;">-</div>
                                                @endif
                                            @elseif($favicon_path)
                                                <img src="{{ Storage::url($favicon_path) }}" class="rounded shadow-sm" style="width: 32px; height: 32px;">
                                            @else
                                                <div class="border rounded p-1 text-center text-muted d-inline-block" style="width: 32px; height: 32px;">-</div>
                                            @endif
                                        </div>
                                        <input type="file" class="form-control @error('new_favicon') is-invalid @enderror" wire:model="new_favicon">
                                        @error('new_favicon') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        <div class="form-text">Format PNG/ICO, ukuran 32x32 atau 64x64.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title fw-semibold mb-4">Aksi</h5>
                                <p class="text-muted mb-4">Pastikan data yang Anda masukkan sudah benar sebelum menyimpan.</p>
                                <button type="submit" class="btn btn-primary w-100 py-2" wire:loading.attr="disabled">
                                    <span wire:loading.remove>Simpan Perubahan</span>
                                    <span wire:loading>Menyimpan...</span>
                                </button>
                                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary w-100 mt-2">Batal</a>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <div class="d-flex">
                                <i class="ti ti-info-circle fs-7 me-2"></i>
                                <div>
                                    <h6 class="alert-heading">Info</h6>
                                    <p class="mb-0 fs-2">Logo akan muncul di sidebar dan favicon akan muncul di tab browser setelah disimpan.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
