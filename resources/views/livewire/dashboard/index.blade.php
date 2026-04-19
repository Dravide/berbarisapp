<div class="row">
    <div class="col-12">
        <div class="card w-100">
            <div class="card-body">
                <h4 class="card-title fw-semibold">Dashboard</h4>
                <p class="mb-0">Welcome, {{ auth()->user()->name }}! You are logged in as <strong>{{ auth()->user()->role }}</strong>.</p>
                
                @if(auth()->user()->role === 'Admin')
                    <div class="alert alert-primary mt-3">This section is only visible to the Admin.</div>
                @elseif(auth()->user()->role === 'Eventner')
                    <div class="card bg-success-subtle shadow-none border-0 mt-4 rounded-4">
                        <div class="card-body px-4 py-4">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div>
                                    <h5 class="fw-semibold text-dark mb-1">Halaman Publik Kegiatan Anda Siap!</h5>
                                    <p class="fs-4 text-muted mb-0">Bagikan tautan ini kepada calon peserta untuk mempromosikan Event Anda.</p>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('event.detail', auth()->user()->eventner->slug) }}" target="_blank" class="btn btn-success d-flex align-items-center gap-2 px-4">
                                        <i class="ti ti-external-link fs-5"></i> Lihat Halaman
                                    </a>
                                    <button type="button" class="btn border-success text-success bg-white d-flex align-items-center gap-2 px-4 shadow-sm" onclick="navigator.clipboard.writeText('{{ route('event.detail', auth()->user()->eventner->slug) }}'); alert('Tautan publik berhasil disalin!');">
                                        <i class="ti ti-copy fs-5"></i> Salin Link
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif(auth()->user()->role === 'Peserta')
                    <div class="alert alert-info mt-3">This section is only visible to the Peserta.</div>
                @endif
            </div>
        </div>
    </div>
</div>
