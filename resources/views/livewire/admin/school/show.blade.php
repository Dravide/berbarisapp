<div>
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
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    @if($schoolInfo['logo_sekolah'])
                        <img src="{{ Storage::url($schoolInfo['logo_sekolah']) }}" alt="" class="rounded-circle" width="64" height="64" style="object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                            <i class="ti ti-school fs-2 text-primary"></i>
                        </div>
                    @endif
                </div>
                <div class="col">
                    <h4 class="fw-semibold mb-1">{{ $schoolInfo['nama_sekolah'] }}</h4>
                    <span class="badge bg-dark mb-2">NPSN: {{ $schoolInfo['npsn'] }}</span>
                    <div class="text-muted fs-3">
                        @if($schoolInfo['nama_pelatih'])
                            <span class="me-3"><i class="ti ti-user me-1"></i>{{ $schoolInfo['nama_pelatih'] }}</span>
                        @endif
                        @if($schoolInfo['no_hp'])
                            <span><i class="ti ti-phone me-1"></i>{{ $schoolInfo['no_hp'] }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-info-subtle shadow-none border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-info text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="ti ti-file-text fs-5"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0 text-muted">Total Pendaftaran</h6>
                            <h3 class="mb-0 fw-bold">{{ $schoolInfo['total_registrations'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-primary-subtle shadow-none border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="ti ti-users fs-5"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0 text-muted">Total Peserta</h6>
                            <h3 class="mb-0 fw-bold">{{ $schoolInfo['total_participants'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success-subtle shadow-none border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-success text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="ti ti-trophy fs-5"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0 text-muted">Event Diikuti</h6>
                            <h3 class="mb-0 fw-bold">{{ $schoolInfo['events']->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Events List --}}
    <div class="card">
        <div class="card-body">
            <h5 class="card-title fw-semibold mb-4">Riwayat Pendaftaran</h5>

            <div class="table-responsive">
                <table class="table align-middle text-nowrap mb-0">
                    <thead>
                        <tr class="text-muted fw-semibold">
                            <th scope="col" class="ps-0">Event</th>
                            <th scope="col">Kategori Lomba</th>
                            <th scope="col">Peserta</th>
                            <th scope="col">Status</th>
                            <th scope="col">Finalized</th>
                            <th scope="col">Terdaftar</th>
                        </tr>
                    </thead>
                    <tbody class="border-top">
                        @forelse($registrations as $reg)
                            <tr>
                                <td class="ps-0">
                                    <h6 class="fw-semibold mb-0">{{ $reg->eventner->nama_event ?? '-' }}</h6>
                                    <span class="fs-2 text-muted">{{ $reg->eventner->diselenggarakan_oleh ?? '' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        {{ $reg->competitionCategory->nama ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        @foreach($reg->participants as $participant)
                                            <span class="badge bg-light text-dark me-1 mb-1">{{ $participant->nama }}</span>
                                        @endforeach
                                        @if($reg->participants->isEmpty())
                                            <span class="text-muted fs-3">Belum ada peserta</span>
                                        @endif
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
                                    <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }}">
                                        {{ $reg->status_berkas }}
                                    </span>
                                </td>
                                <td>
                                    @if($reg->is_finalized)
                                        <span class="badge bg-success-subtle text-success">Ya</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Belum</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fs-3 text-muted">{{ $reg->created_at->translatedFormat('d M Y') }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">Tidak ada data pendaftaran.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
