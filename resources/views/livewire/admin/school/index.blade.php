<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Data Sekolah</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">Data Sekolah (NPSN)</li>
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

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title fw-semibold">Daftar Sekolah Pendaftar</h5>
                <span class="badge bg-primary fs-3">{{ $schools->count() }} Sekolah</span>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <input type="text" class="form-control" wire:model.live="search" placeholder="Cari NPSN atau nama sekolah...">
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="filterEvent">
                        <option value="">Semua Event</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}">{{ $event->nama_event }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle text-nowrap mb-0">
                    <thead>
                        <tr class="text-muted fw-semibold">
                            <th scope="col" class="ps-0">NPSN</th>
                            <th scope="col">Nama Sekolah</th>
                            <th scope="col">Total Pendaftaran</th>
                            <th scope="col">Total Peserta</th>
                            <th scope="col">Terverifikasi</th>
                            <th scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="border-top">
                        @forelse($schools as $school)
                            <tr>
                                <td class="ps-0">
                                    <span class="badge bg-dark">{{ $school->npsn }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($school->logo_sekolah)
                                            <img src="{{ Storage::url($school->logo_sekolah) }}" alt="" class="rounded-circle me-2" width="36" height="36" style="object-fit: cover;">
                                        @else
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px;">
                                                <i class="ti ti-school fs-5 text-muted"></i>
                                            </div>
                                        @endif
                                        <h6 class="fw-semibold mb-0">{{ $school->nama_sekolah }}</h6>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info">{{ $school->total_registrations }} kali</span>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $school->total_participants }}</span> peserta
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success">{{ $school->verified_count }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.schools.show', $school->npsn) }}" class="btn btn-sm btn-primary" title="Detail">
                                        <i class="ti ti-eye me-1"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">Belum ada data sekolah.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
