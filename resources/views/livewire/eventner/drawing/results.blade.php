<div wire:poll.3s>
    <!-- Banner Start -->
    <section class="bg-primary-subtle pt-7 py-lg-9 py-7">
        <div class="custom-container">
            <div class="row align-items-center">
                <div class="col-lg-8 text-center text-lg-start mb-4 mb-lg-0">
                    <span class="badge bg-primary text-white mb-3 fs-3 px-3 py-2 rounded-pill">Hasil Undian Live</span>
                    <h1 class="text-dark fw-bolder fs-11 mb-3">Hasil Pengundian Urutan Tampil</h1>
                    <p class="fs-5 text-muted mb-0">Event: <strong>{{ $eventner->nama_event }}</strong></p>
                    <div class="mt-3">
                        <a href="{{ route('event.detail', $slug) }}" class="btn btn-outline-primary rounded-pill px-4 shadow-sm pb-2 pt-2">
                            <i class="ti ti-arrow-left me-1"></i> Kembali ke Event
                        </a>
                        <a href="{{ route('event.drawing.spin', $slug) }}" class="btn btn-primary rounded-pill px-4 shadow-sm pb-2 pt-2 ms-2">
                            <i class="ti ti-arrows-shuffle me-1"></i> Kembali ke Spin
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 text-center d-none d-lg-block">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid" style="max-height: 150px;" />
                </div>
            </div>
        </div>
    </section>
    <!-- Banner End -->

    <section class="py-5 py-md-8">
        <div class="custom-container">

    <!-- Tabs Kategori -->
    <ul class="nav nav-tabs nav-fill mb-4" role="tablist">
        @foreach ($categories as $category)
            <li class="nav-item" role="presentation">
                <button 
                    class="nav-link {{ $activeTab == $category['id'] ? 'active bg-primary text-white' : '' }}" 
                    wire:click="switchTab('{{ $category['id'] }}')"
                    type="button" role="tab"
                >
                    <i class="ti ti-medal me-2"></i> {{ $category['name'] }}
                </button>
            </li>
        @endforeach
    </ul>

    <!-- Live Badge -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <span class="badge bg-danger rounded-pill px-3 py-2 fs-3 fw-bold shadow-sm">
                <i class="ti ti-live-photo me-1"></i> LIVE
            </span>
            <span class="text-muted ms-2 fs-3">Update otomatis setiap 3 detik</span>
        </div>
        <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold">
            {{ $results->count() }} / {{ $totalSchools }} Ditentukan
        </span>
    </div>

    <!-- Tabel Hasil -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            @if($results->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 fs-4">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="ps-4 py-3 fw-bold" style="width: 100px;">URUTAN</th>
                                <th class="py-3 fw-bold" style="width: 80px;">LOGO</th>
                                <th class="py-3 fw-bold">NAMA SEKOLAH / KONTINGEN</th>
                                <th class="py-3 fw-bold">NPSN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $reg)
                                <tr class="{{ $loop->last ? 'bg-success-subtle' : '' }}" style="transition: background-color 0.5s;">
                                    <td class="ps-4 py-3">
                                        <span class="badge bg-primary rounded-circle d-inline-flex align-items-center justify-content-center fw-bolder shadow-sm" style="width: 46px; height: 46px; font-size: 18px;">
                                            {{ $reg->urutan_tampil }}
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        @if($reg->logo_sekolah)
                                            <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" class="rounded-3 border" style="width: 48px; height: 48px; object-fit: contain;" alt="Logo">
                                        @else
                                            <div class="bg-light rounded-3 d-flex align-items-center justify-content-center border" style="width: 48px; height: 48px;">
                                                <i class="ti ti-building-school text-muted fs-6"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        <h5 class="fw-bold mb-0">{{ $reg->nama_sekolah }}</h5>
                                    </td>
                                    <td class="py-3">
                                        <span class="text-muted fw-semibold">{{ $reg->npsn }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-7 px-3">
                    <div class="bg-primary-subtle rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                        <i class="ti ti-hourglass-empty fs-10 text-primary"></i>
                    </div>
                    <h4 class="fw-bold text-muted">Menunggu Pengundian...</h4>
                    <p class="text-muted fs-4">Hasil akan muncul otomatis saat pengundian dilakukan di laman Spin.</p>
                </div>
            @endif
        </div>
    </div>
        </div>
    </section>
</div>
