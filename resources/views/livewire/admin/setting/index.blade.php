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
                                    <label for="meta_description" class="form-label">Deskripsi Situs (Meta Description)</label>
                                    <textarea class="form-control @error('meta_description') is-invalid @enderror" id="meta_description" rows="3" wire:model="meta_description" placeholder="Platform manajemen event dan kompetisi terpadu..."></textarea>
                                    @error('meta_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="form-text">Direkomendasikan 120-160 karakter. Deskripsi ini akan muncul di hasil pencarian Google.</div>
                                </div>
                                <div class="mb-0">
                                    <label for="meta_keywords" class="form-label">Kata Kunci (Meta Keywords)</label>
                                    <input type="text" class="form-control @error('meta_keywords') is-invalid @enderror" id="meta_keywords" wire:model="meta_keywords" placeholder="event, kompetisi, lomba, pendaftaran, manajemen event">
                                    @error('meta_keywords') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="form-text">Pisahkan dengan koma. Maksimal 10-15 kata kunci relevan.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Logo Settings -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title fw-semibold mb-4">Tema & Tampilan Landing Page</h5>
                                <p class="text-muted fs-3 mb-3">Sesuaikan warna dan font untuk halaman utama BARIS APP.</p>

                                {{-- Warna --}}
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Warna Utama (Primary)</label>
                                        <div class="input-group">
                                            <input type="color" wire:model="site_primary_color" class="form-control form-control-color p-1" style="width: 44px; height: 38px; cursor: pointer;">
                                            <input type="text" wire:model="site_primary_color" class="form-control" style="font-family: monospace; font-size: 13px;">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Warna Aksen (Accent)</label>
                                        <div class="input-group">
                                            <input type="color" wire:model="site_accent_color" class="form-control form-control-color p-1" style="width: 44px; height: 38px; cursor: pointer;">
                                            <input type="text" wire:model="site_accent_color" class="form-control" style="font-family: monospace; font-size: 13px;">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="rounded-3 overflow-hidden border d-flex align-items-center" style="height: 38px;">
                                            <div style="width: 60%; height: 100%; display: inline-block; background: {{ $site_primary_color }};"></div>
                                            <div style="width: 40%; height: 100%; display: inline-block; background: {{ $site_accent_color }};"></div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Font --}}
                                @php $fonts = $this->getAvailableFonts(); @endphp
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Font Teks Isi (Body)</label>
                                        <select wire:model="site_font_sans" class="form-select">
                                            @foreach($fonts['sans'] as $f)
                                                <option value="{{ $f['id'] }}">{{ $f['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Font Judul (Display)</label>
                                        <select wire:model="site_font_display" class="form-select">
                                            @foreach($fonts['display'] as $f)
                                                <option value="{{ $f['id'] }}">{{ $f['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- Preview --}}
                                <div class="rounded-3 border p-4" style="background: #fafafa;">
                                    <span class="text-muted small d-block mb-2">Pratinjau:</span>
                                    <p class="mb-2" style="font-family: '{{ $site_font_sans }}', sans-serif; font-size: 16px; font-weight: 600;">
                                        Body Text — The quick brown fox jumps over the lazy dog.
                                    </p>
                                    <p class="mb-2" style="font-family: '{{ $site_font_sans }}', sans-serif; font-size: 14px; font-weight: 400;">
                                        Teks isi paragraf akan tampil seperti ini pada landing page utama.
                                    </p>
                                    <p class="mb-0" style="font-family: '{{ $site_font_display }}', sans-serif; font-size: 24px; font-weight: 700; color: {{ $site_primary_color }};">
                                        Judul Besar Landing Page
                                    </p>
                                    <p class="mb-0" style="font-family: '{{ $site_font_display }}', sans-serif; font-size: 18px; font-weight: 600;">
                                        Subjudul & Heading
                                    </p>
                                </div>

                                {{-- Preview font loader --}}
                                @php
                                    $previewSans = collect($fonts['sans'])->firstWhere('id', $site_font_sans);
                                    $previewDisplay = collect($fonts['display'])->firstWhere('id', $site_font_display);
                                    $previewSansW = $previewSans['weights'] ?? 'wght@400;500;600;700';
                                    $previewDisplayW = $previewDisplay['weights'] ?? 'wght@500;600;700;800';
                                @endphp
                                <link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $site_font_sans) }}:{{ $previewSansW }}&family={{ str_replace(' ', '+', $site_font_display) }}:{{ $previewDisplayW }}&display=swap" rel="stylesheet">
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
                        {{-- Biaya Pendaftaran --}}
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title fw-semibold mb-4">Biaya Pendaftaran Eventner</h5>
                                <div class="mb-3">
                                    <label for="eventner_registration_fee" class="form-label">Biaya Paket Berbayar (Rp)</label>
                                    <input type="number" class="form-control @error('eventner_registration_fee') is-invalid @enderror"
                                        id="eventner_registration_fee" wire:model="eventner_registration_fee" min="0" step="1000">
                                    @error('eventner_registration_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="form-text">Biaya pendaftaran untuk paket berbayar. Biaya 0 = gratis.</div>
                                </div>
                            </div>
                        </div>

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
