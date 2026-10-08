<div>
    <div class="row">
        <div class="col-12">
            <!-- Header & Breadcrumb -->
            <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
                <div class="card-body px-4 py-3">
                    <div class="row align-items-center">
                        <div class="col-9">
                            <h4 class="fw-semibold mb-8">Modul Event: {{ $eventner->nama_event }}</h4>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a class="text-muted text-decoration-none" href="{{ route('admin.eventner.index') }}">Eventner</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a class="text-muted text-decoration-none" href="{{ route('admin.eventner.show', $eventner->id) }}">{{ \Illuminate\Support\Str::limit($eventner->nama_event, 24) }}</a>
                                    </li>
                                    <li class="breadcrumb-item" aria-current="page">Modul</li>
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

    @php
        $max = \App\Livewire\Admin\Eventner\Modul::BATAS_BARIS;
        $potongan = fn ($koleksi) => $koleksi->count() > $max;
    @endphp

    <!-- Tiket -->
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-semibold mb-0"><i class="ti ti-ticket text-primary me-2"></i>Tiket Online</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $eventner->tickets->count() }} tiket</span>
        </div>
        <div class="card-body p-0">
            @if($eventner->ticket_active)
                <div class="px-4 pt-3">
                    <span class="badge bg-success">Tiket aktif</span>
                    @if($eventner->hasTicketPrice())
                        <span class="text-muted fs-2 ms-2">Harga mulai Rp {{ number_format((int) $eventner->startingTicketPrice(), 0, ',', '.') }}</span>
                    @endif
                </div>
            @endif
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Kode</th>
                            <th>Pembeli</th>
                            <th>Venue</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-end">Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $ticket->order_code }}</td>
                                <td>
                                    {{ $ticket->buyer_name }}
                                    <span class="text-muted fs-2 d-block">{{ $ticket->buyer_phone }}</span>
                                </td>
                                <td>{{ $ticket->venue?->name ?? 'Semua gerbang' }}</td>
                                <td class="text-center">{{ $ticket->quantity }}</td>
                                <td class="text-end">Rp {{ number_format((int) $ticket->total_amount, 0, ',', '.') }}</td>
                                <td><span class="badge bg-secondary-subtle text-secondary">{{ $ticket->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center p-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($potongan($tickets))
                <div class="px-4 py-2"><span class="badge bg-info-subtle text-info">Menampilkan {{ $max }} terakhir</span></div>
            @endif
        </div>
    </div>

    <!-- Voting -->
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-semibold mb-0"><i class="ti ti-heart text-danger me-2"></i>Voting</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $eventner->voteTransactions->count() }} transaksi</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Pemberi</th>
                            <th class="text-end">Nominal</th>
                            <th class="text-center">Vote</th>
                            <th>Status</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($eventner->voteTransactions as $trx)
                            <tr>
                                <td class="ps-4">{{ $trx->voter_name }}</td>
                                <td class="text-end">Rp {{ number_format((int) $trx->amount, 0, ',', '.') }}</td>
                                <td class="text-center">{{ $trx->votes_earned }}</td>
                                <td><span class="badge bg-secondary-subtle text-secondary">{{ $trx->status }}</span></td>
                                <td class="fs-2 text-muted">{{ $trx->paid_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center p-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Kategori Juara -->
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-semibold mb-0"><i class="ti ti-trophy text-warning me-2"></i>Kategori Juara</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $eventner->championCategories->count() }} kategori</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Nama</th>
                            <th class="text-center">Jumlah Juara</th>
                            <th class="text-center">Publik</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($eventner->championCategories as $champ)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $champ->name }}</td>
                                <td class="text-center">{{ $champ->quantity }}</td>
                                <td class="text-center">
                                    @if($champ->is_public)
                                        <span class="badge bg-success-subtle text-success">Publik</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Privat</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center p-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Drawing -->
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-semibold mb-0"><i class="ti ti-arrows-shuffle text-info me-2"></i>Drawing / Undian</h5>
            @if($eventner->drawing_code)
                <span class="badge bg-info-subtle text-info">Kode: {{ $eventner->drawing_code }}</span>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Sekolah</th>
                            <th>Kategori</th>
                            <th>QR Token</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($drawingRegistrations as $reg)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $reg->display_name }}</td>
                                <td>{{ $reg->competitionCategory?->full_name ?? '-' }}</td>
                                <td class="text-muted">{{ $reg->qr_token }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center p-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($potongan($drawingRegistrations))
                <div class="px-4 py-2"><span class="badge bg-info-subtle text-info">Menampilkan {{ $max }} terakhir</span></div>
            @endif
        </div>
    </div>

    <!-- Rundown -->
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-semibold mb-0"><i class="ti ti-list-details text-secondary me-2"></i>Rundown Acara</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $eventner->eventRundowns->count() }} agenda</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Waktu</th>
                            <th>Judul</th>
                            <th>Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($eventner->eventRundowns as $rundown)
                            <tr>
                                <td class="ps-4 text-muted">{{ $rundown->start_time?->format('H:i') ?? '-' }}@if($rundown->end_time) - {{ $rundown->end_time->format('H:i') }} @endif</td>
                                <td class="fw-semibold">{{ $rundown->title }}</td>
                                <td class="text-muted">{{ \Illuminate\Support\Str::limit($rundown->description, 80) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center p-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Livestream Overlay -->
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-semibold mb-0"><i class="ti ti-video text-info me-2"></i>Livestream Overlay</h5>
            @if($eventner->link_livestreaming)
                <a href="{{ $eventner->link_livestreaming }}" target="_blank" rel="noopener" class="fs-2">Stream terhubung <i class="ti ti-external-link"></i></a>
            @endif
        </div>
        <div class="card-body">
            @if($eventner->overlaySetting)
                @php $overlay = $eventner->overlaySetting; @endphp
                <div class="d-flex flex-wrap gap-3">
                    <span class="badge {{ $overlay->show_header ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">Header: {{ $overlay->show_header ? 'tampil' : 'sembunyi' }}</span>
                    <span class="badge {{ $overlay->show_vote_leaderboard ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">Leaderboard Vote: {{ $overlay->show_vote_leaderboard ? 'tampil' : 'sembunyi' }}</span>
                    <span class="badge {{ $overlay->show_kegiatan ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">Kegiatan: {{ $overlay->show_kegiatan ? 'tampil' : 'sembunyi' }}</span>
                    <span class="badge {{ $overlay->show_footer ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">Footer: {{ $overlay->show_footer ? 'tampil' : 'sembunyi' }}</span>
                </div>
                @if($overlay->marquee_text)
                    <p class="text-muted fs-2 mt-2 mb-0">Marquee: {{ $overlay->marquee_text }}</p>
                @endif
            @else
                <p class="text-muted mb-0">Belum ada data.</p>
            @endif
        </div>
    </div>

    <!-- Sponsor -->
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-semibold mb-0"><i class="ti ti-affiliate text-primary me-2"></i>Sponsor & Partner</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $eventner->sponsors->count() }} sponsor</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Nama</th>
                            <th>Tipe</th>
                            <th>Tautan</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($eventner->sponsors as $sponsor)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $sponsor->name }}</td>
                                <td>{{ $sponsor->type }}</td>
                                <td>
                                    @if($sponsor->link)
                                        <a href="{{ $sponsor->link }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($sponsor->link, 40) }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($sponsor->is_active)
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center p-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tenant -->
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-semibold mb-0"><i class="ti ti-building-store text-success me-2"></i>Tenant / Stand</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $eventner->tenants->count() }} tenant</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Nama</th>
                            <th>Tipe</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($eventner->tenants as $tenant)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $tenant->name }}</td>
                                <td>{{ $tenant->type }}</td>
                                <td class="text-center">
                                    @if($tenant->is_active)
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center p-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sertifikat -->
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-semibold mb-0"><i class="ti ti-certificate text-warning me-2"></i>Sertifikat</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $eventner->certificateTemplates->count() }} template</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Nama</th>
                            <th>Ukuran</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($eventner->certificateTemplates as $template)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $template->name }}</td>
                                <td>{{ $template->width }} x {{ $template->height }}</td>
                                <td class="text-center">
                                    @if($template->is_active)
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center p-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- FAQ -->
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-semibold mb-0"><i class="ti ti-info-circle text-info me-2"></i>FAQ</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $eventner->faqs->count() }} pertanyaan</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Pertanyaan</th>
                            <th>Jawaban</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($eventner->faqs as $faq)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $faq->question }}</td>
                                <td class="text-muted">{{ \Illuminate\Support\Str::limit($faq->answer, 100) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center p-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Galeri -->
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-semibold mb-0"><i class="ti ti-photo text-primary me-2"></i>Galeri</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $eventner->galleries->count() }} foto</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Gambar</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($eventner->galleries as $gallery)
                            <tr>
                                <td class="ps-4">
                                    @if($gallery->image)
                                        <img src="{{ Storage::url($gallery->image) }}" class="rounded border" width="60" height="40" style="object-fit: cover;">
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $gallery->caption ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center p-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TTD & Stempel -->
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-semibold mb-0"><i class="ti ti-signature text-danger me-2"></i>TTD & Stempel</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $eventner->signatures->count() }} tanda tangan</span>
        </div>
        <div class="card-body p-0">
            @if($eventner->signatures->isEmpty())
                <div class="p-4 text-center text-muted">Belum ada data.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Nama</th>
                                <th>Gambar</th>
                                <th class="text-center">Aktif</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($eventner->signatures as $signature)
                                <tr>
                                    <td class="ps-4 fw-semibold">{{ $signature->name }}</td>
                                    <td>
                                        @if($signature->image)
                                            <img src="{{ Storage::url($signature->image) }}" class="rounded border" height="30">
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($eventner->active_signature_id === $signature->id)
                                            <span class="badge bg-success-subtle text-success">Dipakai</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-2">
                    <span class="text-muted fs-2">Mode: {{ ucfirst($eventner->signature_mode ?? '-') }}</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Rekening Bank -->
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-semibold mb-0"><i class="ti ti-building-bank text-secondary me-2"></i>Rekening Bank</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $eventner->bankAccounts->count() }} rekening</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Bank</th>
                            <th>Nomor</th>
                            <th>Atas Nama</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($eventner->bankAccounts as $account)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $account->bank_name }}</td>
                                <td>{{ $account->account_number }}</td>
                                <td>{{ $account->account_name }}</td>
                                <td class="text-center">
                                    @if($account->is_active)
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center p-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Field Pendaftaran -->
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-semibold mb-0"><i class="ti ti-forms text-primary me-2"></i>Field Pendaftaran</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $eventner->registrationFields->count() }} field</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Label</th>
                            <th>Key</th>
                            <th>Tipe</th>
                            <th>Seksi</th>
                            <th class="text-center">Wajib</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($eventner->registrationFields as $field)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $field->label }}</td>
                                <td class="text-muted">{{ $field->field_key }}</td>
                                <td>{{ $field->type }}</td>
                                <td>{{ $field->section }}</td>
                                <td class="text-center">
                                    @if($field->is_required)
                                        <span class="badge bg-danger-subtle text-danger">Wajib</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($field->is_active)
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center p-4">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
