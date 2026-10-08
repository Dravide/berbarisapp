<div>
    <div class="row">
        <div class="col-12">
            <!-- Header & Breadcrumb -->
            <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
                <div class="card-body px-4 py-3">
                    <div class="row align-items-center">
                        <div class="col-9">
                            <h4 class="fw-semibold mb-8">Struktur & Juri: {{ $eventner->nama_event }}</h4>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a class="text-muted text-decoration-none" href="{{ route('admin.eventner.index') }}">Eventner</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a class="text-muted text-decoration-none" href="{{ route('admin.eventner.show', $eventner->id) }}">{{ \Illuminate\Support\Str::limit($eventner->nama_event, 24) }}</a>
                                    </li>
                                    <li class="breadcrumb-item" aria-current="page">Struktur & Juri</li>
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
        </div>
    </div>

    <!-- Pohon Tingkat Lomba -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="card-title fw-semibold mb-0">Pohon Tingkat Lomba</h5>
        </div>
        <div class="card-body">
            @forelse($this->levels as $level)
                <div class="mb-3">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <i class="ti ti-category text-primary fs-5"></i>
                        <span class="fw-bold fs-5 text-dark">{{ $level->name }}</span>
                        <span class="badge bg-primary-subtle text-primary">{{ $level->registrations_count }} pendaftar</span>
                        @if($level->venue)
                            <span class="badge bg-info-subtle text-info"><i class="ti ti-map-pin me-1"></i>{{ $level->venue->name }}</span>
                        @endif
                    </div>
                    <div class="text-muted fs-2 mt-1">
                        @if($level->tanggal_pelaksanaan) Pelaksanaan: {{ $level->tanggal_pelaksanaan }} &bull; @endif
                        @if($level->registration_fee !== null) Biaya: Rp {{ number_format($level->registration_fee, 0, ',', '.') }} &bull; @endif
                        Kuota: {{ $level->kuota ?: 'tanpa batas' }}
                    </div>

                    <div class="ms-4 mt-2">
                        @if($level->groups->isEmpty() && $level->rounds->isEmpty() && $level->series->isEmpty() && $level->children->isEmpty())
                            <span class="text-muted fs-2">Belum ada grup, babak, seri, atau sub-tingkat.</span>
                        @endif

                        @if($level->groups->isNotEmpty())
                            <div class="mb-2">
                                <span class="fw-semibold fs-2 text-uppercase text-muted">Grup</span>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    @foreach($level->groups as $group)
                                        <span class="badge bg-secondary-subtle text-secondary">{{ $group->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($level->rounds->isNotEmpty())
                            <div class="mb-2">
                                <span class="fw-semibold fs-2 text-uppercase text-muted">Babak</span>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    @foreach($level->rounds as $round)
                                        <span class="badge bg-warning-subtle text-warning">
                                            {{ $round->name }}
                                            @if($round->type === \App\Models\CompetitionRound::TYPE_FINAL)
                                                &mdash; Final
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($level->series->isNotEmpty())
                            <div class="mb-2">
                                <span class="fw-semibold fs-2 text-uppercase text-muted">Seri</span>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    @foreach($level->series as $series)
                                        <span class="badge bg-success-subtle text-success">{{ $series->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @foreach($level->children as $child)
                            <div class="d-flex align-items-center gap-2 flex-wrap mt-2 ms-3 ps-3 border-start">
                                <i class="ti ti-subtask text-muted"></i>
                                <span class="fw-semibold">{{ $child->name }}</span>
                                <span class="badge bg-primary-subtle text-primary">{{ $child->registrations_count }} pendaftar</span>
                                @if($child->venue)
                                    <span class="badge bg-info-subtle text-info"><i class="ti ti-map-pin me-1"></i>{{ $child->venue->name }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">Belum ada tingkat lomba.</p>
            @endforelse
        </div>
    </div>

    <!-- Juri & Penugasan -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="card-title fw-semibold mb-0">Juri & Penugasan</h5>
        </div>
        <div class="card-body">
            @php $penugasan = $this->penugasanPerJuri(); @endphp
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Nama Juri</th>
                            <th>Kontak</th>
                            <th>Penugasan (dari competition_group_judge)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->judges as $judge)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $judge->name }}</td>
                                <td class="text-muted">{{ $judge->phone_number ?: '-' }}</td>
                                <td>
                                    @php $tugas = $penugasan->get($judge->id, collect()); @endphp
                                    @if($tugas->isEmpty())
                                        <span class="text-muted fs-2">Belum ada penugasan grup.</span>
                                    @else
                                        @foreach($tugas as $perTingkat)
                                            <div class="mb-1">
                                                <span class="fw-semibold fs-2">{{ $perTingkat['name'] }}:</span>
                                                @foreach($perTingkat['items'] as $item)
                                                    <span class="badge bg-primary-subtle text-primary ms-1">{{ $item }}</span>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center p-4">Belum ada juri.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
