<div>
    {{-- Banner --}}
    <section class="bg-primary-subtle pt-7 pb-5">
        <div class="custom-container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    @if($eventner->logo_event)
                        <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="rounded-circle border border-3 border-white mb-3" width="80" height="80" style="object-fit:cover;" alt="">
                    @endif
                    <span class="badge bg-primary text-white mb-3 px-3 py-2">Kontingen Peserta</span>
                    <h1 class="text-dark fw-semibold fs-10 mb-2">Daftar Peserta Terdaftar</h1>
                    <p class="fs-5 text-muted mb-4">
                        {{ $eventner->nama_event }} — <em>{{ $eventner->diselenggarakan_oleh }}</em>
                    </p>
                    <a href="{{ route('event.detail', $eventner->slug) }}" class="btn btn-outline-primary px-4">
                        <i class="ti ti-arrow-left me-1"></i> Kembali ke Info Event
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Stats --}}
    @php
        $totalKontingen = $eventner->competitionCategories->sum(fn($c) => $c->registrations->count());
        $totalAnggota = $eventner->competitionCategories->sum(fn($c) => $c->registrations->sum(fn($r) => $r->participants->count()));
        $totalBooking = $eventner->competitionCategories->sum(fn($c) => $c->registrations->where('status_berkas', 'booking')->count());
        $totalVerified = $eventner->competitionCategories->sum(fn($c) => $c->registrations->where('status_berkas', 'Terverifikasi')->count());
    @endphp
    <section class="pb-0 pt-4">
        <div class="custom-container">
            <div class="row justify-content-center g-3 mb-4">
                <div class="col-md-2 col-6">
                    <div class="card text-center h-100 mb-0">
                        <div class="card-body py-3">
                            <p class="text-muted small mb-1 fw-semibold">Kategori</p>
                            <h3 class="fw-semibold text-dark mb-0">{{ $eventner->competitionCategories->count() }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card text-center h-100 mb-0">
                        <div class="card-body py-3">
                            <p class="text-muted small mb-1 fw-semibold">Total Kontingen</p>
                            <h3 class="fw-semibold text-success mb-0">{{ $totalKontingen }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card text-center h-100 mb-0">
                        <div class="card-body py-3">
                            <p class="text-muted small mb-1 fw-semibold">Booking</p>
                            <h3 class="fw-semibold text-secondary mb-0">{{ $totalBooking }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card text-center h-100 mb-0">
                        <div class="card-body py-3">
                            <p class="text-muted small mb-1 fw-semibold">Terverifikasi</p>
                            <h3 class="fw-semibold text-primary mb-0">{{ $totalVerified }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card text-center h-100 mb-0">
                        <div class="card-body py-3">
                            <p class="text-muted small mb-1 fw-semibold">Total Peserta</p>
                            <h3 class="fw-semibold text-warning mb-0">{{ $totalAnggota }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Participants List — Compact Table --}}
    <section class="pb-5 pt-2">
        <div class="custom-container">
            @foreach($eventner->competitionCategories as $cat)
                <div class="card w-100 mb-3 overflow-hidden">
                    <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-white fw-semibold">
                            <i class="ti ti-medal me-1"></i> {{ $cat->name }}
                        </h6>
                        <div class="d-flex align-items-center gap-2">
                            @if($cat->tanggal_pelaksanaan)
                                <span class="badge bg-white text-primary px-2 py-1"><i class="ti ti-calendar me-1"></i> {{ \Carbon\Carbon::parse($cat->tanggal_pelaksanaan)->translatedFormat('d M Y') }}</span>
                            @endif
                            <span class="badge bg-white text-dark px-2 py-1">{{ $cat->registrations->count() }} kontingen</span>
                        </div>
                    </div>

                    @if($cat->registrations->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle text-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 border-0 fw-semibold text-dark" style="width:40px;">No</th>
                                        <th class="border-0 fw-semibold text-dark">Sekolah / Kontingen</th>
                                        <th class="border-0 fw-semibold text-dark">Pelatih</th>
                                        <th class="border-0 fw-semibold text-dark text-center">Pasukan</th>
                                        <th class="border-0 fw-semibold text-dark text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cat->registrations as $idx => $reg)
                                    <tr>
                                        <td class="ps-3 fw-semibold text-muted">{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($reg->logo_sekolah)
                                                    <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" class="rounded-circle border" width="32" height="32" style="object-fit:cover;" alt="">
                                                @else
                                                    <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                                                        <i class="ti ti-school fs-5"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <span class="fw-semibold">{{ $reg->nama_sekolah }}</span>
                                                    <span class="text-muted small ms-1">({{ $reg->npsn }})</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($reg->nama_pelatih)
                                                <span class="fw-medium">{{ $reg->nama_pelatih }}</span>
                                            @else
                                                <span class="text-muted fst-italic small">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info-subtle text-info px-2 py-1">
                                                {{ $reg->participants->count() }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($reg->status_berkas === 'Terverifikasi')
                                                <span class="badge bg-success-subtle text-success px-2 py-1"><i class="ti ti-check me-1"></i>Verifikasi</span>
                                            @elseif($reg->status_berkas === 'Ditolak')
                                                <span class="badge bg-danger-subtle text-danger px-2 py-1">Ditolak</span>
                                            @elseif($reg->status_berkas === 'booking')
                                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1">Booking</span>
                                            @elseif($reg->status_berkas === 'dibatalkan')
                                                <span class="badge bg-dark-subtle text-dark px-2 py-1">Batal</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning px-2 py-1">Menunggu</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="card-body text-center py-3">
                            <p class="text-muted mb-0 small">Belum ada kontingen di kategori ini.</p>
                        </div>
                    @endif
                </div>
            @endforeach

            @if($eventner->competitionCategories->count() === 0)
                <div class="text-center py-5">
                    <i class="ti ti-mood-empty fs-10 text-muted"></i>
                    <p class="text-muted mt-3">Belum ada kategori lomba yang tersedia.</p>
                </div>
            @endif
        </div>
    </section>
</div>
