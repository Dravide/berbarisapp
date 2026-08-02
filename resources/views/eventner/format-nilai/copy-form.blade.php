<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salin Rubrik ke Tingkat Lain - BARIS APP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.30.0/tabler-icons.min.css">
</head>
<body class="bg-light" style="min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem;">
    <div class="card shadow-sm w-100" style="max-width:560px; border:0;">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold">
                <i class="ti ti-arrow-right-circle me-1"></i> Salin Rubrik ke Tingkat Lain
            </h5>
            <a href="{{ route('eventner.format-nilai.builder') }}" class="btn btn-close btn-close-white" aria-label="Tutup"></a>
        </div>
        <div class="card-body">
            @if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif

            <div class="alert alert-success border-0 bg-success-subtle text-success">
                <i class="ti ti-check me-1"></i> Sumber: <strong>{{ $source->name }}</strong>
                @if($source->competitionCategory)
                    <span class="badge bg-success-subtle text-success ms-1">{{ $source->competitionCategory->full_name }}</span>
                @endif
            </div>

            @if($source->subCategories->isEmpty())
                <div class="alert alert-warning">Kategori ini belum punya sub-kategori / rubrik.</div>
            @else
                <p class="text-muted small mb-3">Akan disalin:
                    {{ $source->subCategories->count() }} sub-kategori,
                    {{ $source->subCategories->sum(fn($s) => $s->criterias->count()) }} kriteria.
                </p>
            @endif

            <form method="POST" action="{{ route('eventner.format-nilai.copy-execute', $source->id) }}" id="copyForm">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Salin ke Tingkat</label>
                    <select name="target_competition_category_id" id="targetSelect" class="form-select @error('target_competition_category_id') is-invalid @enderror" required>
                        <option value="">― Pilih Tingkat Tujuan ―</option>
                        @foreach($targets as $t)
                            <option value="{{ $t->id }}">{{ $t->full_name }}</option>
                        @endforeach
                    </select>
                    @error('target_competition_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="form-text text-muted">Struktur rubrik ini akan disalin sebagai kategori baru di tingkat tujuan.</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('eventner.format-nilai.builder') }}" class="btn btn-secondary">
                        <i class="ti ti-x me-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-success" id="btnSalin">
                        <i class="ti ti-copy me-1"></i> Salin Rubrik
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Konfirmasi sebelum salin
    document.getElementById('copyForm').addEventListener('submit', function (e) {
        var target = document.getElementById('targetSelect');
        if (!target.value) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Pilih Tingkat Tujuan', text: 'Pilih tingkat tujuan terlebih dahulu.', confirmButtonColor: '#198754' });
            return;
        }
        e.preventDefault();
        var targetName = target.options[target.selectedIndex].text;
        Swal.fire({
            title: 'Salin Rubrik?',
            html: 'Rubrik <strong>{{ $source->name }}</strong> akan disalin ke <strong>' + targetName + '</strong>.<br><small>Sub-kategori & kriteria (bobot, skor) ikut disalin.</small>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Salin',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
        }).then(function (result) {
            if (result.isConfirmed) {
                document.getElementById('copyForm').submit();
            }
        });
    });

    // Feedback success/error via session flash
    @if(session('success'))
    Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", confirmButtonColor: '#198754', timer: 2500, timerProgressBar: true });
    @endif
    @if(session('error'))
    Swal.fire({ icon: 'error', title: 'Gagal', text: "{{ session('error') }}", confirmButtonColor: '#dc3545' });
    @endif
    </script>
</body>
</html>
