<div>
    <div class="row">
        <div class="col-12">
            <!-- Header & Breadcrumb -->
            <div class="card bg-primary-subtle shadow-none position-relative overflow-hidden mb-4">
                <div class="card-body px-4 py-3">
                    <div class="row align-items-center">
                        <div class="col-9">
                            <h4 class="fw-semibold mb-8">Admin Dashboard Overview</h4>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                                    </li>
                                    <li class="breadcrumb-item" aria-current="page">Admin Stats</li>
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

            <!-- Global Stats -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-info-subtle shadow-none border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-info text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="ti ti-users fs-7"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 text-muted">Total Eventner</h6>
                                    <h3 class="mb-0 fw-bold">{{ $totalEventners }}</h3>
                                    <small class="text-muted">Penyelenggara Terdaftar</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-primary-subtle shadow-none border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="ti ti-id-badge fs-7"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 text-muted">Total Pendaftar</h6>
                                    <h3 class="mb-0 fw-bold">{{ $totalRegistrations }}</h3>
                                    <small class="text-muted">Seluruh Event</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success-subtle shadow-none border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-success text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="ti ti-currency-dollar fs-7"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 text-muted">Global Revenue</h6>
                                    <h3 class="mb-0 fw-bold">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h3>
                                    <small class="text-muted">Voting Terbayar</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title fw-semibold">Quick Actions</h5>
                            <div class="row mt-4">
                                <div class="col-md-3">
                                    <a href="{{ route('admin.eventner.index') }}" class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center">
                                        <i class="ti ti-users fs-8 mb-2"></i>
                                        <span>Kelola Eventner</span>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-info w-100 py-3 d-flex flex-column align-items-center">
                                        <i class="ti ti-user-cog fs-8 mb-2"></i>
                                        <span>Manajemen User</span>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('admin.schools.index') }}" class="btn btn-outline-success w-100 py-3 d-flex flex-column align-items-center">
                                        <i class="ti ti-school fs-8 mb-2"></i>
                                        <span>Data Sekolah</span>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary w-100 py-3 d-flex flex-column align-items-center">
                                        <i class="ti ti-settings fs-8 mb-2"></i>
                                        <span>Pengaturan Situs</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
