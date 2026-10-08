<div>
    <div class="row">
        <div class="col-12">
            {{-- Header --}}
            <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
                <div class="card-body px-4 py-3">
                    <div class="row align-items-center">
                        <div class="col-9">
                            <h4 class="fw-semibold mb-8">Pengaturan Landing Page</h4>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a class="text-muted text-decoration-none" href="{{ route('admin.settings.index') }}">Pengaturan</a>
                                    </li>
                                    <li class="breadcrumb-item" aria-current="page">Landing Page</li>
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

            {{-- Preview Link --}}
            <div class="alert alert-light border d-flex align-items-center mb-4">
                <i class="ti ti-external-link fs-6 me-2 text-primary"></i>
                <span class="me-2">Preview Landing Page:</span>
                <a href="{{ route('landing') }}" target="_blank" class="fw-bold text-primary">{{ route('landing') }}</a>
            </div>

            <form wire:submit.prevent="save">
                <div class="row">
                    {{-- Main Content --}}
                    <div class="col-lg-9">
                        <div class="card">
                            <div class="card-body p-0">
                                {{-- Tab Navigation --}}
                                <div class="border-bottom">
                                    <ul class="nav nav-tabs border-0 px-3 pt-3" id="landingTabs" role="tablist">
                                        @foreach([
                                            'hero' => 'Hero',
                                            'features' => 'Fitur',
                                            'about' => 'Visual & Poin',
                                            'pricing' => 'Harga',
                                            'perbandingan' => 'Perbandingan',
                                            'ticket' => 'Tiket',
                                            'faq' => 'FAQ',
                                            'contact' => 'Kontak',
                                            'cta' => 'CTA',
                                            'social' => 'Sosial Media',
                                        ] as $key => $label)
                                        <li class="nav-item">
                                            <button type="button"
                                                class="nav-link px-3 py-2 {{ $activeTab === $key ? 'active' : '' }} {{ !($sectionsActive[$key] ?? true) && $key !== 'social' ? 'text-decoration-line-through text-muted' : '' }}"
                                                wire:click="setActiveTab('{{ $key }}')">
                                                {{ $label }}
                                            </button>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>

                                {{-- Tab Content --}}
                                <div class="p-4">
                                    {{-- ==================== HERO TAB ==================== --}}
                                    @if($activeTab === 'hero')
                                    <div wire:key="tab-hero">
                                        <h5 class="fw-semibold mb-3"><i class="ti ti-layout-navbar me-2"></i>Hero Section</h5>
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Judul Utama</label>
                                                <input type="text" class="form-control" wire:model="hero_heading" placeholder="Judul utama landing page">
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Subjudul</label>
                                                <textarea class="form-control" wire:model="hero_subheading" rows="3" placeholder="Deskripsi singkat di bawah judul"></textarea>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Teks Tombol CTA</label>
                                                <input type="text" class="form-control" wire:model="hero_cta_text" placeholder="Mis: Mulai Sekarang">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">URL Tombol CTA</label>
                                                <input type="text" class="form-control" wire:model="hero_cta_url" placeholder="https://...">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">URL Video Demo (opsional)</label>
                                                <input type="text" class="form-control" wire:model="hero_video_url" placeholder="https://youtube.com/...">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Background Image (opsional)</label>
                                                @if($hero_bg_current)
                                                    <div class="mb-2">
                                                        <img src="{{ Storage::url($hero_bg_current) }}" class="img-fluid rounded border p-1" style="max-height: 80px;">
                                                        <small class="d-block text-muted mt-1">Gambar saat ini</small>
                                                    </div>
                                                @endif
                                                <input type="file" class="form-control" wire:model="hero_background_image" accept="image/*">
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    {{-- ==================== FEATURES TAB ==================== --}}
                                    @if($activeTab === 'features')
                                    <div wire:key="tab-features">
                                        <h5 class="fw-semibold mb-3"><i class="ti ti-grid-dots me-2"></i>Fitur Section</h5>
                                        <div class="mb-3">
                                            <label class="form-label">Judul Section</label>
                                            <input type="text" class="form-control" wire:model="features_title">
                                        </div>

                                        <div class="border rounded p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0">Item Fitur</h6>
                                                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addFeatureItem">
                                                    <i class="ti ti-plus me-1"></i> Tambah
                                                </button>
                                            </div>
                                            @foreach($features_items as $i => $item)
                                            <div class="border rounded p-3 mb-2" wire:key="feat-{{ $i }}">
                                                <div class="row">
                                                    <div class="col-md-4 mb-2">
                                                        <label class="form-label small">Icon Name</label>
                                                        <select class="form-select form-select-sm" wire:model="features_items.{{ $i }}.icon">
                                                            <option value="icon3.png">📊 Financial Planning</option>
                                                            <option value="icon4.png">🔒 Security</option>
                                                            <option value="icon5.png">📋 Tax</option>
                                                            <option value="icon6.png">📱 Offline</option>
                                                            <option value="icon7.png">💱 Currency</option>
                                                            <option value="icon8.png">🔗 Integration</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3 mb-2">
                                                        <label class="form-label small">Judul</label>
                                                        <input type="text" class="form-control form-control-sm" wire:model="features_items.{{ $i }}.title" placeholder="Nama fitur">
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <label class="form-label small">Deskripsi</label>
                                                        <input type="text" class="form-control form-control-sm" wire:model="features_items.{{ $i }}.description" placeholder="Deskripsi singkat">
                                                    </div>
                                                    <div class="col-md-1 d-flex align-items-end">
                                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeFeatureItem({{ $i }})">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    {{-- ==================== PRICING TAB ==================== --}}
                                    @if($activeTab === 'pricing')
                                    <div wire:key="tab-pricing">
                                        <h5 class="fw-semibold mb-3"><i class="ti ti-cash me-2"></i>Harga Section</h5>
                                        <p class="text-muted fs-3 mb-3">Harga & daftar fitur paket diatur di <a href="{{ route('admin.settings.index') }}">Pengaturan Situs</a>. Di sini cukup judul section.</p>
                                        <div class="mb-3">
                                            <label class="form-label">Judul Section</label>
                                            <input type="text" class="form-control" wire:model="pricing_title">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Subjudul</label>
                                            <textarea class="form-control" rows="2" wire:model="pricing_subtitle"></textarea>
                                        </div>
                                    </div>
                                    @endif

                                    {{-- ==================== PERBANDINGAN TAB ==================== --}}
                                    @if($activeTab === 'perbandingan')
                                    <div wire:key="tab-perbandingan">
                                        <h5 class="fw-semibold mb-3"><i class="ti ti-table me-2"></i>Section Perbandingan</h5>
                                        <div class="alert alert-light border d-flex align-items-center mb-3">
                                            <i class="ti ti-info-circle fs-6 me-2 text-primary"></i>
                                            <span class="fs-2">Tabel fitur per paket dihitung <strong>otomatis dari paket aktif</strong> (sama dengan section Harga). Di sini cukup judul &amp; subjudul.</span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Judul Section</label>
                                            <input type="text" class="form-control" wire:model="perbandingan_title" placeholder="Mis: Bandingkan Paket">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Subjudul</label>
                                            <textarea class="form-control" rows="2" wire:model="perbandingan_subtitle" placeholder="Kalimat pengantar di bawah judul"></textarea>
                                        </div>
                                    </div>
                                    @endif

                                    {{-- ==================== ABOUT TAB ==================== --}}
                                    @if($activeTab === 'about')
                                    <div wire:key="tab-about">
                                        {{-- Section "Tentang" sendiri sudah dibuang dari laman
                                             (isinya mengulang #features). Yang tersisa dari
                                             kuncinya dipakai di dua tempat lain: gambarnya jadi
                                             visual di bawah Hero, poin-poinnya jadi chip di bawah
                                             judul #features. --}}
                                        <h5 class="fw-semibold mb-3"><i class="ti ti-info-circle me-2"></i>Visual &amp; Poin</h5>
                                        <div class="alert alert-info py-2 small mb-3">
                                            Gambar &amp; poin di sini tampil di bagian lain laman, bukan sebagai section sendiri:
                                            gambarnya jadi visual di bawah Hero, poinnya jadi ringkasan di bawah judul Fitur.
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Gambar (tampil di bawah Hero)</label>
                                                @if($about_image_current)
                                                    <div class="mb-2">
                                                        <img src="{{ Storage::url($about_image_current) }}" class="img-fluid rounded border p-1" style="max-height: 120px;">
                                                    </div>
                                                @endif
                                                <input type="file" class="form-control" wire:model="about_image" accept="image/*">
                                                <div class="mt-2">
                                                    <label class="form-label small">Video URL (opsional)</label>
                                                    <input type="text" class="form-control form-control-sm" wire:model="about_video" placeholder="https://.../video.mp4">
                                                    <small class="text-muted">Hanya dipakai kalau gambar di atas kosong. Kosongkan untuk memakai gambar.</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="border rounded p-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <label class="form-label mb-0">Poin-Poin (tampil di bawah judul Fitur)</label>
                                                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addAboutPoint">
                                                            <i class="ti ti-plus"></i>
                                                        </button>
                                                    </div>
                                                    @foreach($about_points as $i => $point)
                                                    <div class="mb-2 border rounded p-2" wire:key="about-pt-{{ $i }}">
                                                        <input type="text" class="form-control form-control-sm mb-1" wire:model="about_points.{{ $i }}.title" placeholder="Judul poin">
                                                        <input type="text" class="form-control form-control-sm" wire:model="about_points.{{ $i }}.text" placeholder="Deskripsi poin">
                                                        <button type="button" class="btn btn-sm btn-outline-danger mt-1" wire:click="removeAboutPoint({{ $i }})">
                                                            <i class="ti ti-trash"></i> Hapus
                                                        </button>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    {{-- ==================== CTA TAB ==================== --}}
                                    @if($activeTab === 'cta')
                                    <div wire:key="tab-cta">
                                        <h5 class="fw-semibold mb-3"><i class="ti ti-speakerphone me-2"></i>CTA Section</h5>
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Judul</label>
                                                <input type="text" class="form-control" wire:model="cta_heading">
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Deskripsi</label>
                                                <textarea class="form-control" wire:model="cta_description" rows="3"></textarea>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Teks Tombol</label>
                                                <input type="text" class="form-control" wire:model="cta_button_text">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">URL Tombol</label>
                                                <input type="text" class="form-control" wire:model="cta_button_url">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Gambar (opsional)</label>
                                                @if($cta_image_current)
                                                    <div class="mb-2">
                                                        <img src="{{ Storage::url($cta_image_current) }}" class="img-fluid rounded border p-1" style="max-height: 120px;">
                                                    </div>
                                                @endif
                                                <input type="file" class="form-control" wire:model="cta_image" accept="image/*">
                                            </div>
                                        </div>
                                    </div>
                                    @endif



                                    {{-- ==================== FAQ TAB ==================== --}}
                                    @if($activeTab === 'faq')
                                    <div wire:key="tab-faq">
                                        <h5 class="fw-semibold mb-3"><i class="ti ti-help-circle me-2"></i>FAQ Section</h5>
                                        <div class="mb-3">
                                            <label class="form-label">Judul Section</label>
                                            <input type="text" class="form-control" wire:model="faq_title">
                                        </div>
                                        <div class="d-flex justify-content-end mb-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addFaqItem">
                                                <i class="ti ti-plus me-1"></i> Tambah FAQ
                                            </button>
                                        </div>
                                        @foreach($faq_items as $i => $item)
                                        <div class="border rounded p-3 mb-2" wire:key="faq-{{ $i }}">
                                            <div class="mb-2">
                                                <label class="form-label small">Pertanyaan</label>
                                                <input type="text" class="form-control form-control-sm" wire:model="faq_items.{{ $i }}.question">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small">Jawaban</label>
                                                <textarea class="form-control form-control-sm" wire:model="faq_items.{{ $i }}.answer" rows="2"></textarea>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeFaqItem({{ $i }})">
                                                <i class="ti ti-trash me-1"></i> Hapus
                                            </button>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif


                                    {{-- ==================== TICKET TAB ==================== --}}
                                    @if($activeTab === 'ticket')
                                    <div wire:key="tab-ticket">
                                        <h5 class="fw-semibold mb-3"><i class="ti ti-ticket me-2"></i>Section Tiket</h5>
                                        <div class="alert alert-light border d-flex align-items-center mb-3">
                                            <i class="ti ti-info-circle fs-6 me-2 text-primary"></i>
                                            <span class="fs-2">Menampilkan event yang mengaktifkan tiket secara <strong>otomatis (data live)</strong>. Atur hanya judul &amp; subjudul section.</span>
                                        </div>
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Judul Section</label>
                                                <input type="text" class="form-control" wire:model="ticket_title" placeholder="Mis: E-Tiket Digital">
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Subjudul</label>
                                                <textarea class="form-control" wire:model="ticket_subtitle" rows="2" placeholder="Deskripsi singkat section tiket"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    @endif


                                    {{-- ==================== CONTACT TAB ==================== --}}
                                    @if($activeTab === 'contact')
                                    <div wire:key="tab-contact">
                                        <h5 class="fw-semibold mb-3"><i class="ti ti-phone me-2"></i>Kontak</h5>
                                        <p class="text-muted mb-3">Kontak ini dipakai di halaman <strong>Bantuan & Support</strong> dan section Kontak landing page.</p>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label"><i class="ti ti-phone me-1 text-success"></i>Telepon / WhatsApp</label>
                                                <input type="text" class="form-control" wire:model="contact_phone" placeholder="+62 812-3456-7890">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label"><i class="ti ti-mail me-1 text-primary"></i>Email</label>
                                                <input type="email" class="form-control" wire:model="contact_email" placeholder="halo@berbaris.local">
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label class="form-label"><i class="ti ti-map-pin me-1 text-danger"></i>Alamat</label>
                                                <input type="text" class="form-control" wire:model="contact_address" placeholder="Jl. Teknologi No. 42, Jakarta Selatan">
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label class="form-label"><i class="ti ti-map me-1"></i>Google Maps Embed URL (opsional)</label>
                                                <input type="text" class="form-control" wire:model="contact_map_embed_url" placeholder="https://www.google.com/maps/embed?pb=...">
                                                <small class="text-muted">Link embed dari Google Maps (bagian "Bagikan" → "Tempelkan peta").</small>
                                            </div>
                                        </div>
                                    </div>
                                    @endif


                                    {{-- ==================== SOCIAL MEDIA TAB ==================== --}}
                                    @if($activeTab === 'social')
                                    <div wire:key="tab-social">
                                        <h5 class="fw-semibold mb-3"><i class="ti ti-share me-2"></i>Social Media Links</h5>
                                        <p class="text-muted mb-3">Tautan media sosial akan muncul di footer landing page.</p>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label"><i class="ti ti-brand-instagram me-1 text-danger"></i>Instagram URL</label>
                                                <input type="text" class="form-control" wire:model="social_instagram" placeholder="https://instagram.com/...">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label"><i class="ti ti-brand-tiktok me-1"></i>TikTok URL</label>
                                                <input type="text" class="form-control" wire:model="social_tiktok" placeholder="https://tiktok.com/@...">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label"><i class="ti ti-brand-youtube me-1 text-danger"></i>YouTube URL</label>
                                                <input type="text" class="form-control" wire:model="social_youtube" placeholder="https://youtube.com/...">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label"><i class="ti ti-brand-facebook me-1 text-primary"></i>Facebook URL</label>
                                                <input type="text" class="form-control" wire:model="social_facebook" placeholder="https://facebook.com/...">
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Sidebar --}}
                    <div class="col-lg-3">
                        {{-- Save Button --}}
                        <div class="card">
                            <div class="card-body">
                                <h5 class="fw-semibold mb-3">Simpan Perubahan</h5>
                                <p class="text-muted fs-2 mb-3">Klik simpan untuk menerapkan perubahan ke landing page.</p>
                                <button type="submit" class="btn btn-primary w-100 py-2" wire:loading.attr="disabled">
                                    <span wire:loading.remove><i class="ti ti-device-floppy me-1"></i> Simpan</span>
                                    <span wire:loading><i class="ti ti-loader me-1"></i> Menyimpan...</span>
                                </button>
                                <a href="{{ route('landing') }}" target="_blank" class="btn btn-outline-primary w-100 mt-2">
                                    <i class="ti ti-external-link me-1"></i> Preview
                                </a>
                            </div>
                        </div>

                        {{-- Section Ordering --}}
                        <div class="card">
                            <div class="card-body">
                                <h5 class="fw-semibold mb-3">Urutan & Visibilitas</h5>
                                <p class="text-muted fs-2 mb-3">Atur urutan dan aktifkan/nonaktifkan section.</p>
                                @foreach($sectionsOrder as $i => $sectionType)
                                <div class="d-flex align-items-center border rounded p-2 mb-2 {{ ($sectionsActive[$sectionType] ?? true) ? '' : 'opacity-50' }}" wire:key="order-{{ $i }}">
                                    <div class="d-flex flex-column me-2">
                                        <button type="button" class="btn btn-xs btn-ghost p-0 lh-1" wire:click="moveSectionUp({{ $i }})" {{ $i === 0 ? 'disabled' : '' }}>
                                            <i class="ti ti-chevron-up"></i>
                                        </button>
                                        <button type="button" class="btn btn-xs btn-ghost p-0 lh-1" wire:click="moveSectionDown({{ $i }})" {{ $i === count($sectionsOrder) - 1 ? 'disabled' : '' }}>
                                            <i class="ti ti-chevron-down"></i>
                                        </button>
                                    </div>
                                    <div class="form-check form-switch me-2 mb-0">
                                        <input type="checkbox" class="form-check-input" 
                                            wire:change="toggleSection('{{ $sectionType }}')"
                                            {{ ($sectionsActive[$sectionType] ?? true) ? 'checked' : '' }}>
                                    </div>
                                    <span class="fs-3 {{ ($sectionsActive[$sectionType] ?? true) ? '' : 'text-decoration-line-through text-muted' }}">{{ ucfirst($sectionType) }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
