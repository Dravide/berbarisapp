<div>
    <div class="card bg-warning-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Hasil Voting Digital</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Voting</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="card-title fw-semibold mb-1">Klasemen Voting Peserta</h5>
                    <p class="text-muted mb-0">Berdasarkan total nominal yang berhasil terverifikasi (PAID)</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-primary rounded-pill px-3 py-2">1 Vote = Rp 1.000</span>
                </div>
            </div>

            <!-- Tabs Kategori -->
            <ul class="nav nav-tabs nav-fill mb-4" role="tablist">
                @forelse ($categories as $category)
                    <li class="nav-item" role="presentation">
                        <button 
                            class="nav-link {{ $activeTab == $category->id ? 'active bg-primary text-white' : '' }}" 
                            wire:click="switchTab('{{ $category->id }}')"
                            type="button" 
                            role="tab"
                        >
                            <i class="ti ti-medal me-2"></i> {{ $category->name }}
                        </button>
                    </li>
                @empty
                    <li class="nav-item">
                        <span class="nav-link text-muted">Belum ada Kategori Lomba.</span>
                    </li>
                @endforelse
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <div class="tab-pane active" role="tabpanel">
                    @if(count($results) > 0)
                        <div class="table-responsive">
                            <table class="table text-nowrap align-middle mb-0">
                                <thead class="text-dark fs-4 bg-light">
                                    <tr>
                                        <th class="border-bottom-0" style="width: 50px;"><h6 class="fw-semibold mb-0">Rank</h6></th>
                                        <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Sekolah / Kontingen</h6></th>
                                        <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Total Perolehan Vote</h6></th>
                                        <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Estimasi Pendapatan</h6></th>
                                        <th class="border-bottom-0" style="width: 100px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($results as $idx => $reg)
                                        <tr>
                                            <td>
                                                @if($idx == 0)
                                                    <span class="badge bg-warning rounded-circle p-2" title="Juara 1"><i class="ti ti-trophy fs-5"></i></span>
                                                @elseif($idx == 1)
                                                    <span class="badge bg-secondary rounded-circle p-2" title="Juara 2"><i class="ti ti-medal fs-5"></i></span>
                                                @elseif($idx == 2)
                                                    <span class="badge bg-bronze rounded-circle p-2 text-white" style="background-color: #cd7f32" title="Juara 3"><i class="ti ti-medal fs-5"></i></span>
                                                @else
                                                    <span class="fw-bold ms-2">{{ $idx + 1 }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    @if($reg->logo_sekolah)
                                                        <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" class="rounded-circle border" width="40" height="40" style="object-fit:cover;" alt="">
                                                    @else
                                                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                                            <i class="ti ti-school fs-5"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <h6 class="fw-semibold mb-1">{{ $reg->nama_sekolah }}</h6>
                                                        <span class="text-muted fs-2">Danton: {{ $reg->danton_nama ?: '-' }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <h5 class="fw-bolder text-primary mb-0">{{ number_format($reg->total_votes ?: 0, 0, ',', '.') }}</h5>
                                                <small class="text-muted">Suara</small>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-success">Rp {{ number_format(($reg->total_votes ?: 0) * 1000, 0, ',', '.') }}</span>
                                            </td>
                                            <td>
                                                {{-- Progress bar visual --}}
                                                @php
                                                    $maxVotes = $results->first()->total_votes ?: 1;
                                                    $percent = (($reg->total_votes ?: 0) / $maxVotes) * 100;
                                                @endphp
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $percent }}%"></div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="ti ti-chart-bar fs-10 text-muted"></i>
                            <p class="text-muted mt-3 mb-0">Belum ada data voting yang masuk untuk kategori ini.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
