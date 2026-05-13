<div>
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body px-5 py-5">
                    <div class="mb-4">
                        <div class="bg-warning-subtle rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="ti ti-lock fs-1 text-warning"></i>
                        </div>
                    </div>
                    <h4 class="fw-semibold mb-2">Akun Dinonaktifkan</h4>
                    <p class="text-muted mb-4">Akun Anda sedang dinonaktifkan oleh administrator. Anda tidak dapat mengakses fitur apapun saat ini.</p>
                    <p class="fs-3 text-muted">Hubungi administrator untuk informasi lebih lanjut.</p>
                    <form action="{{ route('logout') }}" method="POST" class="mt-4">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="ti ti-logout me-1"></i> Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
