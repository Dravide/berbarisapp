<div>
    <div class="row">
        <div class="col-12">
            <!-- Header & Breadcrumb -->
            <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
                <div class="card-body px-4 py-3">
                    <div class="row align-items-center">
                        <div class="col-9">
                            <h4 class="fw-semibold mb-8">Detail Event: {{ $eventner->nama_event }}</h4>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a class="text-muted text-decoration-none" href="{{ route('admin.eventner.index') }}">Eventner</a>
                                    </li>
                                    <li class="breadcrumb-item" aria-current="page">Detail</li>
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

            <!-- Dashboard Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary-subtle shadow-none">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="ti ti-users fs-6"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 text-muted">Total Pendaftar</h6>
                                    <h4 class="mb-0 fw-bold">{{ $totalRegistrations }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success-subtle shadow-none">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-success text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="ti ti-wallet fs-6"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 text-muted">Total Pendapatan</h6>
                                    <h4 class="mb-0 fw-bold">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning-subtle shadow-none">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="ti ti-category fs-6"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 text-muted">Total Kategori</h6>
                                    <h4 class="mb-0 fw-bold">{{ $totalCategories }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-secondary-subtle shadow-none">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-secondary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="ti ti-user-check fs-6"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 text-muted">Total Juri</h6>
                                    <h4 class="mb-0 fw-bold">{{ $totalJudges }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-8">
                    <!-- Event Info -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="card-title fw-semibold mb-0">Informasi Event</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center mb-4 mb-md-0">
                                    @if($eventner->logo_event)
                                        <img src="{{ Storage::url($eventner->logo_event) }}" class="img-fluid rounded border p-2" style="max-height: 150px;">
                                    @else
                                        <div class="bg-light rounded p-5 text-center">
                                            <i class="ti ti-photo fs-9 text-muted"></i>
                                            <p class="mb-0 text-muted mt-2">No Logo</p>
                                        </div>
                                    @endif
                                    <div class="mt-3">
                                        <a href="{{ route('event.detail', $eventner->slug) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="ti ti-external-link"></i> Lihat Laman Publik
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span class="text-muted">Penyelenggara</span>
                                            <span class="fw-bold">{{ $eventner->diselenggarakan_oleh }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span class="text-muted">Tingkat Perlombaan</span>
                                            <span class="badge bg-primary rounded-pill">{{ $eventner->tingkat_perlombaan }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span class="text-muted">Tanggal Pelaksanaan</span>
                                            <span class="fw-bold">{{ \Carbon\Carbon::parse($eventner->tanggal)->format('d F Y') }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span class="text-muted">Batas Pendaftaran</span>
                                            <span class="fw-bold text-danger">{{ $eventner->tanggal_pendaftaran }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span class="text-muted">Venue / Lokasi</span>
                                            <span class="fw-bold text-end">{{ $eventner->venue }}, {{ $eventner->lokasi }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-bottom-0">
                                            <span class="text-muted">Technical Meeting</span>
                                            <span class="fw-bold">{{ $eventner->technical_meeting }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <hr>
                            <div class="mt-2">
                                <h6 class="fw-semibold">Deskripsi Event:</h6>
                                <p class="text-muted mb-0">{!! nl2br(e($eventner->deskripsi)) !!}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Categories Table -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="card-title fw-semibold mb-0">Kategori Lomba</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Nama Kategori</th>
                                            <th>Pelaksanaan</th>
                                            <th class="text-center">Kuota (Terisi)</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($eventner->competitionCategories as $category)
                                            @php
                                                $regCount = $category->registrations()->count();
                                                $kuota = $category->kuota ?: 0;
                                                $percent = $kuota > 0 ? ($regCount / $kuota) * 100 : 0;
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold text-dark">{{ $category->name }}</span>
                                                </td>
                                                <td>{{ $category->tanggal_pelaksanaan ?: '-' }}</td>
                                                <td>
                                                    <div class="text-center mb-1">
                                                        <small>{{ $regCount }} / {{ $kuota }}</small>
                                                    </div>
                                                    <div class="progress" style="height: 4px;">
                                                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $percent }}%"></div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    @if($kuota > 0 && $regCount >= $kuota)
                                                        <span class="badge bg-danger">Full</span>
                                                    @else
                                                        <span class="badge bg-success">Open</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Registrations -->
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title fw-semibold mb-0">Pendaftar Terbaru</h5>
                            <span class="badge bg-info-subtle text-info">Menampilkan 10 terakhir</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Sekolah</th>
                                            <th>Kategori</th>
                                            <th>Tanggal Daftar</th>
                                            <th class="text-center">Status Berkas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentRegistrations as $registration)
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center">
                                                        @if($registration->logo_sekolah)
                                                            <img src="{{ Storage::url($registration->logo_sekolah) }}" class="rounded-circle" width="35" height="35">
                                                        @else
                                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                                                <i class="ti ti-school text-muted"></i>
                                                            </div>
                                                        @endif
                                                        <div class="ms-3">
                                                            <h6 class="mb-0 fw-semibold">{{ $registration->nama_sekolah }}</h6>
                                                            <span class="text-muted fs-2">{{ $registration->npsn }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $registration->competitionCategory->name }}</td>
                                                <td class="fs-2 text-muted">{{ $registration->created_at->format('d/m/Y H:i') }}</td>
                                                <td class="text-center">
                                                    @if($registration->status_berkas == 'Diverifikasi')
                                                        <span class="badge bg-success-subtle text-success">Verified</span>
                                                    @elseif($registration->status_berkas == 'Ditolak')
                                                        <span class="badge bg-danger-subtle text-danger">Rejected</span>
                                                    @else
                                                        <span class="badge bg-warning-subtle text-warning">Pending</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center p-4">Belum ada pendaftaran</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-4">
                    <!-- Panitia Info -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="card-title fw-semibold mb-0">Akun Pengelola (Panitia)</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                    <i class="ti ti-shield-check fs-6"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 fw-bold">{{ $eventner->user->name }}</h6>
                                    <span class="text-muted fs-2">Role: Panitia Event</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted fs-2 d-block mb-1">Username:</label>
                                <div class="bg-light p-2 rounded fw-bold text-dark">{{ $eventner->user->username }}</div>
                            </div>
                            <div>
                                <label class="text-muted fs-2 d-block mb-1">Email:</label>
                                <div class="bg-light p-2 rounded fw-bold text-dark">{{ $eventner->user->email }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Social Links -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="card-title fw-semibold mb-0">Media Sosial & Tautan</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                @if($eventner->link_instagram)
                                    <a href="{{ str_contains($eventner->link_instagram, 'http') ? $eventner->link_instagram : 'https://instagram.com/'.$eventner->link_instagram }}" target="_blank" class="btn btn-outline-danger d-flex align-items-center justify-content-between">
                                        <span><i class="ti ti-brand-instagram me-2"></i> Instagram</span>
                                        <i class="ti ti-chevron-right"></i>
                                    </a>
                                @endif
                                @if($eventner->link_tiktok)
                                    <a href="{{ str_contains($eventner->link_tiktok, 'http') ? $eventner->link_tiktok : 'https://tiktok.com/@'.$eventner->link_tiktok }}" target="_blank" class="btn btn-outline-dark d-flex align-items-center justify-content-between">
                                        <span><i class="ti ti-brand-tiktok me-2"></i> TikTok</span>
                                        <i class="ti ti-chevron-right"></i>
                                    </a>
                                @endif
                                @if($eventner->link_whatsapp)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $eventner->link_whatsapp) }}" target="_blank" class="btn btn-outline-success d-flex align-items-center justify-content-between">
                                        <span><i class="ti ti-brand-whatsapp me-2"></i> WhatsApp CS</span>
                                        <i class="ti ti-chevron-right"></i>
                                    </a>
                                @endif
                                @if($eventner->link_livestreaming)
                                    <a href="{{ $eventner->link_livestreaming }}" target="_blank" class="btn btn-outline-info d-flex align-items-center justify-content-between">
                                        <span><i class="ti ti-video me-2"></i> Live Streaming</span>
                                        <i class="ti ti-chevron-right"></i>
                                    </a>
                                @endif
                                @if(!$eventner->link_instagram && !$eventner->link_tiktok && !$eventner->link_whatsapp && !$eventner->link_livestreaming)
                                    <p class="text-muted text-center py-3 mb-0">Tidak ada tautan tersedia</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Map Section -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="card-title fw-semibold mb-0">Lokasi Venue</h5>
                        </div>
                        <div class="card-body">
                            @if($eventner->latitude && $eventner->longitude)
                                <div class="rounded overflow-hidden mb-3 border">
                                    <iframe 
                                        width="100%" 
                                        height="250" 
                                        frameborder="0" 
                                        scrolling="no" 
                                        marginheight="0" 
                                        marginwidth="0" 
                                        src="https://maps.google.com/maps?q={{ $eventner->latitude }},{{ $eventner->longitude }}&hl=id&z=15&output=embed">
                                    </iframe>
                                </div>
                                <a href="https://www.google.com/maps/search/?api=1&query={{ $eventner->latitude }},{{ $eventner->longitude }}" target="_blank" class="btn btn-primary w-100">
                                    <i class="ti ti-navigation me-2"></i> Navigasi Maps
                                </a>
                            @else
                                <div class="bg-light rounded p-4 text-center border">
                                    <i class="ti ti-map-off fs-8 text-muted"></i>
                                    <p class="mb-0 text-muted mt-2">Koordinat lokasi belum disetel.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
