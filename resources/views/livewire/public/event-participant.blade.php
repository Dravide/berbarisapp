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
                    <h1 class="text-dark fw-semibold fs-10 mb-2">Daftar Sekolah Terdaftar</h1>
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

    {{-- Stats Summary --}}
    @php
        $totalKontingen = $eventner->competitionCategories->sum(fn($c) => $c->registrations->count());
        $totalAnggota = $eventner->competitionCategories->sum(fn($c) => $c->registrations->sum(fn($r) => $r->participants->count()));
    @endphp
    <section class="pb-0 pt-5">
        <div class="custom-container">
            <div class="row justify-content-center g-3 mb-5">
                <div class="col-md-3 col-6">
                    <div class="card text-center h-100 mb-0">
                        <div class="card-body py-4">
                            <div class="bg-primary-subtle rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width:48px;height:48px;">
                                <i class="ti ti-category fs-6 text-primary"></i>
                            </div>
                            <h3 class="fw-semibold text-dark mb-0">{{ $eventner->competitionCategories->count() }}</h3>
                            <span class="text-muted fs-3">Kategori Lomba</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card text-center h-100 mb-0">
                        <div class="card-body py-4">
                            <div class="bg-success-subtle rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width:48px;height:48px;">
                                <i class="ti ti-school fs-6 text-success"></i>
                            </div>
                            <h3 class="fw-semibold text-dark mb-0">{{ $totalKontingen }}</h3>
                            <span class="text-muted fs-3">Total Kontingen</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card text-center h-100 mb-0">
                        <div class="card-body py-4">
                            <div class="bg-warning-subtle rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width:48px;height:48px;">
                                <i class="ti ti-users fs-6 text-warning"></i>
                            </div>
                            <h3 class="fw-semibold text-dark mb-0">{{ $totalAnggota }}</h3>
                            <span class="text-muted fs-3">Total Peserta</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Participants List --}}
    <section class="pb-5">
        <div class="custom-container">
            @foreach($eventner->competitionCategories as $cat)
                <div class="card w-100 mb-4 overflow-hidden">
                    <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white fw-semibold">
                            <i class="ti ti-medal me-2"></i> {{ $cat->name }}
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            @if($cat->tanggal_pelaksanaan)
                                <span class="badge bg-white text-primary px-3 py-2"><i class="ti ti-calendar me-1"></i> {{ \Carbon\Carbon::parse($cat->tanggal_pelaksanaan)->translatedFormat('d F Y') }}</span>
                            @endif
                            <span class="badge bg-white text-dark px-3 py-2">{{ $cat->registrations->count() }} Kontingen</span>
                        </div>
                    </div>

                    @if($cat->registrations->count() > 0)
                    <div class="card-body p-0">
                        {{-- Desktop Table --}}
                        <div class="d-none d-md-block">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr class="table-light">
                                            <th class="ps-4 border-0 fw-semibold text-dark" style="width:50px;">No</th>
                                            <th class="border-0 fw-semibold text-dark">Sekolah / Kontingen</th>
                                            <th class="border-0 fw-semibold text-dark">Pelatih</th>
                                            <th class="border-0 fw-semibold text-dark">Danton</th>
                                            <th class="border-0 fw-semibold text-dark text-center">Pasukan</th>
                                            <th class="border-0 fw-semibold text-dark text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($cat->registrations as $idx => $reg)
                                        <tr>
                                            <td class="ps-4 fw-semibold text-muted">{{ $idx + 1 }}</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    @if($reg->logo_sekolah)
                                                        <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" class="rounded-circle border" width="44" height="44" style="object-fit:cover;" alt="">
                                                    @else
                                                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                                                            <i class="ti ti-school fs-5"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <h6 class="fw-semibold mb-0 fs-4">{{ $reg->nama_sekolah }}</h6>
                                                        <span class="text-muted fs-2">NPSN: {{ $reg->npsn }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="fw-medium">{{ $reg->nama_pelatih }}</span>
                                            </td>
                                            <td>
                                                @if($reg->danton_nama)
                                                    <span class="fw-medium">{{ $reg->danton_nama }}</span>
                                                @else
                                                    <span class="text-muted fs-2 fst-italic">Belum diisi</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info-subtle text-info fs-3 px-3 py-2">
                                                    <i class="ti ti-users me-1"></i> {{ $reg->participants->count() }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                @if($reg->status_berkas === 'Terverifikasi')
                                                    <span class="badge bg-success-subtle text-success fs-3 px-3 py-2"><i class="ti ti-circle-check me-1"></i> Terverifikasi</span>
                                                @elseif($reg->status_berkas === 'Ditolak')
                                                    <span class="badge bg-danger-subtle text-danger fs-3 px-3 py-2"><i class="ti ti-circle-x me-1"></i> Ditolak</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning fs-3 px-3 py-2"><i class="ti ti-clock me-1"></i> Menunggu</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Mobile Cards --}}
                        <div class="d-md-none p-3">
                            @foreach($cat->registrations as $idx => $reg)
                            <div class="border p-3 mb-2 bg-white">
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    @if($reg->logo_sekolah)
                                        <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" class="rounded-circle border" width="40" height="40" style="object-fit:cover;" alt="">
                                    @else
                                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                            <i class="ti ti-school fs-5"></i>
                                        </div>
                                    @endif
                                    <div class="flex-grow-1">
                                        <h6 class="fw-semibold mb-0">{{ $reg->nama_sekolah }}</h6>
                                        <span class="text-muted fs-2">NPSN: {{ $reg->npsn }}</span>
                                    </div>
                                    @if($reg->status_berkas === 'Terverifikasi')
                                        <span class="badge bg-success">✓</span>
                                    @elseif($reg->status_berkas === 'Ditolak')
                                        <span class="badge bg-danger">✗</span>
                                    @else
                                        <span class="badge bg-warning">⏳</span>
                                    @endif
                                </div>
                                <div class="d-flex justify-content-between fs-2 text-muted">
                                    <span>Pelatih: <strong class="text-dark">{{ $reg->nama_pelatih }}</strong></span>
                                    <span><i class="ti ti-users"></i> {{ $reg->participants->count() }} orang</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @else
                        <div class="card-body text-center py-4">
                            <i class="ti ti-mood-empty fs-7 text-muted d-block mb-2"></i>
                            <p class="text-muted mb-0 fs-3">Belum ada kontingen yang mendaftar di kategori ini.</p>
                        </div>
                    @endif
                </div>
            @endforeach

            @if($eventner->competitionCategories->count() === 0)
                <div class="text-center py-7">
                    <i class="ti ti-mood-empty fs-12 text-muted"></i>
                    <p class="text-muted mt-3">Belum ada kategori lomba yang tersedia.</p>
                </div>
            @endif
        </div>
    </section>
</div>
