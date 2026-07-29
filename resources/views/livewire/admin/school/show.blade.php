<div>
    {{-- Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Detail Sekolah</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('admin.schools.index') }}">Data Sekolah</a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">{{ $schoolInfo['nama_sekolah'] }}</li>
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

    {{-- School Info Card --}}
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    @if($schoolInfo['logo_sekolah'])
                        <img src="{{ Storage::url($schoolInfo['logo_sekolah']) }}" alt="" class="rounded-circle border" width="72" height="72" style="object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center" style="width: 72px; height: 72px;">
                            <i class="ti ti-school fs-2 text-primary"></i>
                        </div>
                    @endif
                </div>
                <div class="col">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h4 class="fw-semibold mb-0">{{ $schoolInfo['nama_sekolah'] }}</h4>
                        <span class="badge bg-dark rounded-1">NPSN {{ $schoolInfo['npsn'] }}</span>
                        <a href="{{ route('admin.schools.edit', $schoolInfo['npsn']) }}" class="btn btn-sm btn-warning ms-auto">
                            <i class="ti ti-edit me-1"></i> Edit
                        </a>
                    </div>
                    <div class="d-flex flex-wrap gap-3 text-muted" style="font-size: 0.85rem;">
                        @if($schoolInfo['no_hp'])
                            <span><i class="ti ti-phone me-1"></i> {{ $schoolInfo['no_hp'] }}</span>
                        @endif
                        @if($schoolInfo['school_email'])
                            <span><i class="ti ti-mail me-1"></i> {{ $schoolInfo['school_email'] }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row mb-4 g-3">
        <div class="col-md-4">
            <div class="card bg-primary-subtle shadow-none border-0 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ti ti-file-text fs-5"></i>
                        </div>
                        <div class="ms-3">
                            <p class="mb-0 text-muted" style="font-size: 0.8rem;">Total Pendaftaran</p>
                            <h3 class="mb-0 fw-bold">{{ $schoolInfo['total_registrations'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success-subtle shadow-none border-0 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-success text-white rounded-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ti ti-users fs-5"></i>
                        </div>
                        <div class="ms-3">
                            <p class="mb-0 text-muted" style="font-size: 0.8rem;">Total Peserta</p>
                            <h3 class="mb-0 fw-bold">{{ $schoolInfo['total_participants'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning-subtle shadow-none border-0 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning text-white rounded-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="ti ti-trophy fs-5"></i>
                        </div>
                        <div class="ms-3">
                            <p class="mb-0 text-muted" style="font-size: 0.8rem;">Event Diikuti</p>
                            <h3 class="mb-0 fw-bold">{{ $schoolInfo['events']->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Event yang diikuti chips --}}
    @if($schoolInfo['events']->isNotEmpty())
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body py-3">
                <p class="fw-semibold mb-2" style="font-size: 0.85rem;"><i class="ti ti-calendar-event me-1"></i> Event yang Diikuti</p>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($schoolInfo['events'] as $event)
                        <span class="badge bg-light text-dark border rounded-1 px-3 py-2">{{ $event }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Registration History --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h5 class="fw-semibold mb-4 d-flex align-items-center gap-2">
                <i class="ti ti-history"></i> Riwayat Pendaftaran
            </h5>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="fw-semibold">Event</th>
                            <th class="fw-semibold">Kategori</th>
                            <th class="fw-semibold">Pasukan</th>
                            <th class="fw-semibold">Peserta</th>
                            <th class="fw-semibold">Status</th>
                            <th class="fw-semibold">Final</th>
                            <th class="fw-semibold">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($registrations as $reg)
                            <tr>
                                <td>
                                    <div class="fw-semibold" style="font-size: 0.9rem;">{{ $reg->eventner->nama_event ?? '-' }}</div>
                                    <small class="text-muted">{{ $reg->eventner->diselenggarakan_oleh ?? '' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary rounded-1 px-2 py-1">
                                        {{ $reg->competitionCategory->nama ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    @if($reg->label_pasukan)
                                        <span class="badge bg-dark rounded-1 px-2 py-1">Pasukan {{ $reg->label_pasukan }}</span>
                                    @else
                                        <span class="text-muted" style="font-size: 0.8rem;">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse($reg->participants as $participant)
                                            <span class="badge bg-light text-dark border rounded-1 px-2 py-1" style="font-size: 0.75rem;">
                                                {{ $participant->nama }}
                                            </span>
                                        @empty
                                            <span class="text-muted" style="font-size: 0.8rem;">—</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $statusClass = match($reg->status_berkas) {
                                            'Terverifikasi' => 'success',
                                            'Ditolak' => 'danger',
                                            'confirmed' => 'info',
                                            'booking' => 'warning',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }} rounded-1 px-2 py-1">
                                        {{ $reg->status_berkas }}
                                    </span>
                                </td>
                                <td>
                                    @if($reg->is_finalized)
                                        <span class="badge bg-success-subtle text-success rounded-1 px-2 py-1">Ya</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary rounded-1 px-2 py-1">Belum</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $reg->created_at->translatedFormat('d M Y') }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="ti ti-inbox text-muted fs-2 d-block mb-2"></i>
                                    <span class="text-muted">Tidak ada data pendaftaran.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
