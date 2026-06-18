<div class="premium-event-page">
    @push('styles')
        <link href="https://unpkg.com/filepond/dist/filepond.css" rel="stylesheet">
        <link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css" rel="stylesheet">
        <style>
            .filepond--root { font-family: 'Inter', sans-serif; margin-bottom: 0; }
            .filepond--panel-root { border-radius: 8px; background-color: #fff; border: 1px solid var(--df-border, #e2e8f0); }
            .filepond--drop-label { color: var(--df-secondary, #64748b); }
        </style>
    @endpush

    {{-- Hero Banner --}}
    <div class="pm-hero" style="background: var(--event-primary, #2665fd); text-align: center;">
        <div class="pm-hero-content">
            <span class="pm-event-badge"><i class="fa fa-link"></i> Portal Pendaftaran Mandiri</span>
            <h1 class="pm-event-title" style="font-size: clamp(24px, 5vw, 36px);">Kelola Pendaftaran</h1>
            <p class="pm-event-org">
                Event: <strong>{{ $registration->eventner->nama_event }}</strong>
            </p>
            <div class="mt-2">
                <span style="color: rgba(255,255,255,0.7); font-size: 14px;">Sekolah:</span>
                <strong style="color: #fff;">{{ $registration->nama_sekolah }}</strong>
                <span class="mx-2" style="color: rgba(255,255,255,0.5);">|</span>
                <span style="color: rgba(255,255,255,0.7); font-size: 14px;">NPSN:</span>
                <strong style="color: #fff;">{{ $registration->npsn }}</strong>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="section zubuz-section-padding3" style="padding-top: 40px;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    {{-- Flash Messages --}}
                    @if(session()->has('success'))
                        <div class="wow fadeInUp" style="background: rgba(16,185,129,0.1); color: #10b981; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <i class="fa fa-check-circle" style="margin-right: 8px;"></i>
                                <span>{{ session('success') }}</span>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 12px;"></button>
                        </div>
                    @endif

                    @if(session()->has('error'))
                        <div class="wow fadeInUp" style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <i class="fa fa-exclamation-circle" style="margin-right: 8px;"></i>
                                <span>{{ session('error') }}</span>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 12px;"></button>
                        </div>
                    @endif

                    @if(($registration->eventner->registration_status ?? 'open') == 'booking')
                        <div class="wow fadeInUp" style="background: rgba(59,130,246,0.1); color: #3b82f6; padding: 16px 20px; border-radius: 8px; margin-bottom: 24px; border: 1px solid #3b82f6;">
                            <h6 style="color: #3b82f6; margin-bottom: 4px; font-weight: 700;"><i class="fa fa-info-circle me-1"></i> Mode Booking Aktif</h6>
                            <p style="margin: 0; font-size: 14px;">Saat ini pendaftaran hanya diperbolehkan untuk memesan slot (Booking). Pengisian data pasukan akan dibuka setelah masa pendaftaran dibuka secara resmi oleh panitia.</p>
                        </div>
                    @endif

                    {{-- Status Badge --}}
                    @php
                        $statusConfig = [
                            'booking' => ['rgba(245,158,11,0.1)', '#f59e0b', 'fa-clock', 'Booking'],
                            'confirmed' => ['rgba(59,130,246,0.1)', '#3b82f6', 'fa-paper-plane', 'Menunggu Verifikasi'],
                            'Terverifikasi' => ['rgba(16,185,129,0.1)', '#10b981', 'fa-check-circle', 'Terverifikasi'],
                            'Ditolak' => ['rgba(239,68,68,0.1)', '#ef4444', 'fa-exclamation-triangle', 'Ditolak — Perlu Revisi'],
                        ];
                        $sc = $statusConfig[$registration->status_berkas] ?? $statusConfig['booking'];
                    @endphp
                    <div class="wow fadeInUp" style="background: #fff; border: 1px solid {{ $sc[1] }}; border-radius: 8px; padding: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px;">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: {{ $sc[0] }}; display: flex; align-items: center; justify-content: center;">
                            <i class="fa {{ $sc[2] }}" style="font-size: 20px; color: {{ $sc[1] }};"></i>
                        </div>
                        <div>
                            <h6 style="margin: 0; font-size: 16px; font-weight: 600; color: var(--df-on-surface, #0f172a);">{{ $sc[3] }}</h6>
                            <p style="margin: 4px 0 0; font-size: 14px; color: var(--df-secondary, #64748b);">
                                @if($registration->status_berkas === 'booking')
                                    Slot Anda sudah dipesan. Siapkan data dan berkas pasukan sebelum pengisian dibuka oleh panitia.
                                @elseif($registration->status_berkas === 'confirmed')
                                    Data telah dikirim ke panitia. Menunggu verifikasi.
                                @elseif($registration->status_berkas === 'Terverifikasi')
                                    Pendaftaran Anda telah disetujui panitia. Sampai jumpa di hari perlombaan!
                                @elseif($registration->status_berkas === 'Ditolak')
                                    Ada berkas/data yang perlu diperbaiki. Silakan revisi dan kirim ulang.
                                @endif
                            </p>
                        </div>
                    </div>
                    {{-- Verified Only: Score & Vote Recap --}}
                    @if($registration->status_berkas === 'Terverifikasi')
                        <div class="row g-3 mb-4">
                            {{-- Score Recap Card --}}
                            @if($this->is_scoring_finalized)
                            <div class="col-md-{{ $registration->eventner->vote_active ? '6' : '12' }} wow fadeInUp">
                                <div style="background: #fff; border: 1px solid var(--df-border, #e2e8f0); border-radius: 8px; padding: 20px; height: 100%;">
                                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                        <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(0,114,255,0.1); display: flex; align-items: center; justify-content: center;">
                                            <i class="fa fa-trophy" style="color: var(--event-primary, #0072FF);"></i>
                                        </div>
                                        <h6 style="margin: 0; font-size: 15px; font-weight: 700;">Rekap Hasil Nilai</h6>
                                    </div>
                                    <p style="font-size: 13px; color: var(--df-secondary, #64748b); margin-bottom: 16px;">Penilaian juri telah selesai dan difinalisasi.</p>
                                    @php
                                        $finalScores = \App\Models\AssessmentScore::where('registration_id', $registration->id)
                                            ->where('is_finalized', true)
                                            ->with(['judge', 'assessmentCriteria.subCategory.category'])
                                            ->get();

                                        $categories = \App\Models\AssessmentCategory::where('eventner_id', $registration->eventner_id)->get();
                                        $judges = \App\Models\Judge::whereIn('id', $finalScores->pluck('judge_id')->unique())->get();

                                        $scoreTable = [];
                                        foreach($finalScores as $score) {
                                            $jid = $score->judge_id;
                                            $cid = $score->assessmentCriteria->subCategory->assessment_category_id;
                                            $scoreTable[$jid][$cid] = ($scoreTable[$jid][$cid] ?? 0) + $score->score;
                                        }

                                        $totalAllJudges = 0;
                                    @endphp
                                    <div style="background: var(--df-surface-variant, #f8fafc); border-radius: 8px; padding: 12px; overflow-x: auto;">
                                        @foreach($judges as $judge)
                                            <div style="margin-bottom: 12px; border-bottom: 1px dashed #e5e7eb; padding-bottom: 8px;">
                                                <div style="font-weight: 700; font-size: 13px; color: var(--df-on-surface, #0f172a); margin-bottom: 4px;">
                                                    <i class="fa fa-user-tie" style="margin-right: 6px; color: var(--df-secondary, #64748b);"></i> {{ $judge->name }}
                                                </div>
                                                @php $jTotal = 0; @endphp
                                                @foreach($categories as $cat)
                                                    @php
                                                        $val = $scoreTable[$judge->id][$cat->id] ?? 0;
                                                        $jTotal += $val;
                                                    @endphp
                                                    <div style="display: flex; justify-content: space-between; font-size: 12px; margin-left: 20px; margin-bottom: 2px;">
                                                        <span style="color: var(--df-secondary, #64748b);">{{ $cat->name }}</span>
                                                        <span style="font-weight: 600; color: var(--df-on-surface, #0f172a);">{{ number_format($val, 0, ',', '.') }}</span>
                                                    </div>
                                                @endforeach
                                                <div style="display: flex; justify-content: space-between; font-size: 13px; margin-left: 20px; margin-top: 4px; border-top: 1px solid #f1f5f9; padding-top: 2px;">
                                                    <span style="font-weight: 700; color: var(--df-on-surface, #0f172a);">Subtotal Juri</span>
                                                    <span style="font-weight: 700; color: var(--event-primary, #0072FF);">{{ number_format($jTotal, 0, ',', '.') }}</span>
                                                </div>
                                                @php $totalAllJudges += $jTotal; @endphp
                                            </div>
                                        @endforeach
                                        <div style="margin-top: 8px; padding: 8px 12px; background: var(--event-primary, #0072FF); color: #fff; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                                            <span style="font-weight: 700; font-size: 13px;">TOTAL KESELURUHAN</span>
                                            <span style="font-weight: 800; font-size: 16px;">{{ number_format($totalAllJudges, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            {{-- Vote Recap Card --}}
                            @if($registration->eventner->vote_active)
                            <div class="col-md-{{ $this->is_scoring_finalized ? '6' : '12' }} wow fadeInUp">
                                <div style="background: #fff; border: 1px solid var(--df-border, #e2e8f0); border-radius: 8px; padding: 20px; height: 100%;">
                                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                        <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(245,158,11,0.1); display: flex; align-items: center; justify-content: center;">
                                            <i class="fa fa-heart" style="color: #f59e0b;"></i>
                                        </div>
                                        <h6 style="margin: 0; font-size: 15px; font-weight: 700;">Rekap Jumlah Vote</h6>
                                    </div>
                                    <p style="font-size: 13px; color: var(--df-secondary, #64748b); margin-bottom: 16px;">Total dukungan (vote online) yang berhasil dikumpulkan.</p>
                                    <div style="background: rgba(245,158,11,0.05); border: 1px dashed rgba(245,158,11,0.3); border-radius: 8px; padding: 20px; text-align: center;">
                                        <h2 style="margin: 0; font-weight: 800; color: #f59e0b; font-size: 32px;">{{ number_format($this->vote_total, 0, ',', '.') }}</h2>
                                        <p style="margin: 4px 0 0; font-size: 13px; font-weight: 600; color: #f59e0b; text-uppercase; letter-spacing: 1px;">Total Vote</p>
                                    </div>
                                    <div style="margin-top: 16px; text-align: center;">
                                        <a href="{{ route('event.vote', $registration->eventner->slug) }}" style="font-size: 13px; color: var(--event-primary, #0072FF); font-weight: 600; text-decoration: none;">
                                            Lihat Klasemen Vote <i class="fa fa-arrow-right" style="font-size: 10px; margin-left: 4px;"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    @endif

                    {{-- Team Tabs (if multiple registrations) --}}
                    @if($siblingRegistrations->count() > 1)
                        <div class="wow fadeInUp" style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 8px; margin-bottom: 24px;">
                            @foreach($siblingRegistrations as $sib)
                                <button wire:click="switchRegistration({{ $sib->id }})"
                                    style="white-space: nowrap; padding: 10px 16px; border-radius: 8px; font-size: 14px; font-weight: 600; transition: all 0.2s; border: none; outline: none; {{ $activeRegId == $sib->id ? 'background: var(--event-primary, #0072FF); color: #fff;' : 'background: var(--df-surface-variant, #f8fafc); color: var(--df-secondary, #64748b);' }}">
                                    <i class="fa fa-users" style="margin-right: 6px;"></i> {{ $sib->competitionCategory->name }}
                                    @if($sib->participants->count() > 0)
                                        <span style="display: inline-block; padding: 2px 6px; border-radius: 8px; font-size: 11px; margin-left: 6px; {{ $activeRegId == $sib->id ? 'background: #fff; color: var(--event-primary, #0072FF);' : 'background: #e5e7eb; color: var(--df-secondary, #64748b);' }}">{{ $sib->participants->count() }}</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif

                    {{-- Active Category Header --}}
                    <div class="wow fadeInUp" style="background: #fff; border: 1px solid var(--df-border, #e2e8f0); border-radius: 8px; margin-bottom: 24px; overflow: hidden;">
                        <div style="background: rgba(0,114,255,0.05); padding: 16px 20px; border-bottom: 1px solid var(--df-border, #e2e8f0);">
                            <h5 style="margin: 0; font-size: 16px; font-weight: 600; color: var(--event-primary, #0072FF);">
                                <i class="fa fa-medal" style="margin-right: 8px;"></i> {{ $registration->competitionCategory->name }}
                            </h5>
                        </div>
                        <div style="padding: 16px 20px;">
                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                <span style="background: var(--df-surface-variant, #f8fafc); color: var(--df-on-surface, #0f172a); padding: 4px 10px; border-radius: 6px; font-size: 13px;">
                                    <i class="fa fa-school" style="margin-right: 6px; color: var(--df-secondary, #64748b);"></i>{{ $registration->nama_sekolah }}
                                </span>
                                @if($registration->nama_pelatih)
                                    <span style="background: var(--df-surface-variant, #f8fafc); color: var(--df-on-surface, #0f172a); padding: 4px 10px; border-radius: 6px; font-size: 13px;">
                                        <i class="fa fa-user" style="margin-right: 6px; color: var(--df-secondary, #64748b);"></i>Pelatih: {{ $registration->nama_pelatih }}
                                    </span>
                                @endif
                                @if($registration->participants->count() > 0)
                                    <span style="background: var(--df-surface-variant, #f8fafc); color: var(--df-on-surface, #0f172a); padding: 4px 10px; border-radius: 6px; font-size: 13px;">
                                        <i class="fa fa-users" style="margin-right: 6px; color: var(--df-secondary, #64748b);"></i>{{ $registration->participants->count() }} anggota
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- BOOKING STATE: Confirm button --}}
                    @if($registration->status_berkas === 'booking')
                        <div class="wow fadeInUp" style="background: #fff; border: 1px solid var(--event-primary, #0072FF); border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                            <h5 style="margin: 0 0 16px; font-size: 16px; font-weight: 600; color: var(--df-on-surface, #0f172a);"><i class="fa fa-info-circle" style="color: var(--event-primary, #0072FF); margin-right: 8px;"></i>Lengkapi Data & Konfirmasi</h5>

                            @if($registration->eventner->technical_meeting && now()->lt($registration->eventner->technical_meeting))
                                <div style="background: rgba(245,158,11,0.1); border-radius: 8px; padding: 16px;">
                                    <p style="margin: 0; font-size: 14px; color: #b45309;">
                                        <i class="fa fa-clock" style="margin-right: 6px;"></i>
                                        Konfirmasi baru bisa dilakukan setelah Technical Meeting:
                                        <strong>{{ \Carbon\Carbon::parse($registration->eventner->technical_meeting)->translatedFormat('d F Y, H:i') }}</strong>.
                                        Anda sudah bisa mengisi draft data di bawah ini.
                                    </p>
                                </div>
                            @else
                                <div style="background: rgba(16,185,129,0.1); border-radius: 8px; padding: 16px;">
                                    <p style="margin: 0; font-size: 14px; color: #047857;">
                                        <i class="fa fa-check-circle" style="margin-right: 6px;"></i>
                                        Technical Meeting sudah dilaksanakan. Silakan lengkapi data dan tekan <strong>"Konfirmasi"</strong>.
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Form --}}
                    @if($registration->status_berkas === 'Terverifikasi')
                        {{-- Verified Data Table View --}}
                        <div class="wow fadeInUp" style="background: #fff; border: 1px solid var(--df-border, #e2e8f0); border-radius: 8px; overflow: hidden; margin-bottom: 40px;">
                            <div style="background: var(--df-surface-variant, #f8fafc); padding: 16px 20px; border-bottom: 1px solid var(--df-border, #e2e8f0);">
                                <h5 style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;"><i class="fa fa-clipboard-check" style="margin-right: 8px; color: #10b981;"></i>Data Pendaftaran Terverifikasi</h5>
                            </div>
                            <div style="padding: 0;">
                                <table class="table table-bordered mb-0" style="font-size: 14px;">
                                    <tbody>
                                        <tr>
                                            <th style="width: 200px; background: #f9fafb; color: #64748b; font-weight: 600;">Kategori Lomba</th>
                                            <td style="font-weight: 700; color: #1e293b;">{{ $registration->competitionCategory->name }}</td>
                                        </tr>
                                        <tr>
                                            <th style="background: #f9fafb; color: #64748b; font-weight: 600;">Nama Sekolah</th>
                                            <td style="font-weight: 700; color: #1e293b;">{{ $registration->nama_sekolah }}</td>
                                        </tr>
                                        <tr>
                                            <th style="background: #f9fafb; color: #64748b; font-weight: 600;">Data Pelatih</th>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 12px;">
                                                    @if($registration->foto_pelatih)
                                                        <img src="{{ asset('storage/' . $registration->foto_pelatih) }}" style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover;">
                                                    @endif
                                                    <div>
                                                        <div style="font-weight: 700;">{{ $registration->nama_pelatih }}</div>
                                                        <div style="font-size: 12px; color: #64748b;">HP: {{ $registration->no_hp }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th style="background: #f9fafb; color: #64748b; font-weight: 600;">Berkas Persyaratan</th>
                                            <td>
                                                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                                    @if($registration->logo_sekolah)
                                                        <a href="{{ asset('storage/' . $registration->logo_sekolah) }}" target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size: 11px;"><i class="fa fa-image me-1"></i> Logo</a>
                                                    @endif
                                                    @if($registration->surat_tugas)
                                                        <a href="{{ asset('storage/' . $registration->surat_tugas) }}" target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size: 11px;"><i class="fa fa-file-pdf me-1"></i> Surat Tugas</a>
                                                    @endif
                                                    @if($registration->bukti_pendaftaran)
                                                        <a href="{{ asset('storage/' . $registration->bukti_pendaftaran) }}" target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size: 11px;"><i class="fa fa-receipt me-1"></i> Kwitansi</a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th style="background: #f9fafb; color: #64748b; font-weight: 600;">Data Danton</th>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 12px;">
                                                    @if($registration->danton_foto)
                                                        <img src="{{ asset('storage/' . $registration->danton_foto) }}" style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover;">
                                                    @endif
                                                    <div>
                                                        <div style="font-weight: 700;">{{ $registration->danton_nama }}</div>
                                                        <div style="font-size: 12px; color: #64748b;">NISN: {{ $registration->danton_nisn ?: '-' }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div style="background: var(--df-surface-variant, #f8fafc); padding: 12px 20px; border-top: 1px solid #e5e7eb; border-bottom: 1px solid var(--df-border, #e2e8f0);">
                                <h6 style="margin: 0; font-size: 14px; font-weight: 700; color: #475569;"><i class="fa fa-users" style="margin-right: 8px;"></i>Daftar Pasukan ({{ $registration->participants->count() }} Anggota)</h6>
                            </div>
                            <div style="padding: 0;">
                                <table class="table table-striped table-hover mb-0" style="font-size: 13px;">
                                    <thead style="background: #f1f5f9;">
                                        <tr>
                                            <th style="width: 50px; text-align: center;">No</th>
                                            <th style="width: 60px;">Foto</th>
                                            <th>Nama Lengkap</th>
                                            <th>NISN</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($registration->participants as $index => $p)
                                            <tr>
                                                <td style="text-align: center; color: #94a3b8;">{{ $index + 1 }}</td>
                                                <td>
                                                    @if($p->foto)
                                                        <img src="{{ asset('storage/' . $p->foto) }}" style="width: 32px; height: 32px; border-radius: 4px; object-fit: cover;">
                                                    @else
                                                        <div style="width: 32px; height: 32px; border-radius: 4px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #94a3b8;"><i class="fa fa-user" style="font-size: 12px;"></i></div>
                                                    @endif
                                                </td>
                                                <td style="font-weight: 600; color: #334155;">{{ $p->nama }}</td>
                                                <td style="color: #64748b;">{{ $p->nisn ?: '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="text-center wow fadeInUp" style="margin-bottom: 60px;">
                            <span style="display: inline-block; background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid #10b981; border-radius: 8px; padding: 12px 32px; font-weight: 800; font-size: 16px;">
                                <i class="fa fa-check-circle" style="margin-right: 8px;"></i> TERVERIFIKASI PANITIA
                            </span>
                            <p style="margin-top: 12px; color: #64748b; font-size: 14px;">Data di atas adalah data resmi yang akan digunakan pada saat perlombaan.</p>
                        </div>
                    @else

                    {{-- Form (for non-verified) --}}
                    @php
                        $isLocked = ($registration->is_finalized && $registration->status_berkas !== 'Ditolak');
                    @endphp

                    @if($registration->status_berkas === 'booking')
                        {{-- Booking: Show preparation info instead of form --}}
                        <div class="wow fadeInUp" style="background: #fff; border: 1px solid var(--df-border, #e2e8f0); border-radius: 8px; margin-bottom: 24px; overflow: hidden;">
                            <div style="padding: 16px 20px; border-bottom: 1px solid var(--df-border, #e2e8f0); background: var(--df-surface-variant, #f8fafc);">
                                <h5 style="margin: 0; font-size: 16px; font-weight: 600; color: var(--df-on-surface, #0f172a);"><i class="fa fa-clipboard-list" style="color: var(--event-primary, #0072FF); margin-right: 8px;"></i>Persiapan Data & Berkas</h5>
                            </div>
                            <div style="padding: 20px;">
                                <p style="color: var(--df-secondary, #64748b); font-size: 14px; margin-bottom: 20px;">Pengisian data pasukan belum dibuka. Silakan persiapkan data dan berkas berikut agar proses pengisian nanti lebih cepat:</p>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div style="background: rgba(0,114,255,0.04); border: 1px solid rgba(0,114,255,0.15); border-radius: 8px; padding: 16px; height: 100%;">
                                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                                                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(0,114,255,0.1); display: flex; align-items: center; justify-content: center;">
                                                    <i class="fa fa-user" style="color: #0072FF; font-size: 14px;"></i>
                                                </div>
                                                <h6 style="margin: 0; font-size: 14px; font-weight: 600; color: var(--df-on-surface, #0f172a);">Data Pelatih</h6>
                                            </div>
                                            <ul style="margin: 0; padding-left: 18px; color: var(--df-secondary, #64748b); font-size: 13px; line-height: 1.8;">
                                                <li>Nama lengkap pelatih</li>
                                                <li>Pas foto pelatih (format JPG/PNG)</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div style="background: rgba(0,114,255,0.04); border: 1px solid rgba(0,114,255,0.15); border-radius: 8px; padding: 16px; height: 100%;">
                                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                                                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(0,114,255,0.1); display: flex; align-items: center; justify-content: center;">
                                                    <i class="fa fa-file-alt" style="color: #0072FF; font-size: 14px;"></i>
                                                </div>
                                                <h6 style="margin: 0; font-size: 14px; font-weight: 600; color: var(--df-on-surface, #0f172a);">Berkas Persyaratan</h6>
                                            </div>
                                            <ul style="margin: 0; padding-left: 18px; color: var(--df-secondary, #64748b); font-size: 13px; line-height: 1.8;">
                                                <li>Logo sekolah (format JPG/PNG)</li>
                                                @if($registration->eventner->surat_tugas_required)
                                                    <li>Surat tugas (format PDF/JPG)</li>
                                                @endif
                                                @if($registration->eventner->kwitansi_required)
                                                    <li>Kwitansi/bukti pendaftaran (format JPG/PNG)</li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div style="background: rgba(245,158,11,0.04); border: 1px solid rgba(245,158,11,0.15); border-radius: 8px; padding: 16px; height: 100%;">
                                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                                                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(245,158,11,0.1); display: flex; align-items: center; justify-content: center;">
                                                    <i class="fa fa-star" style="color: #f59e0b; font-size: 14px;"></i>
                                                </div>
                                                <h6 style="margin: 0; font-size: 14px; font-weight: 600; color: var(--df-on-surface, #0f172a);">Komandan Pleton (Danton)</h6>
                                            </div>
                                            <ul style="margin: 0; padding-left: 18px; color: var(--df-secondary, #64748b); font-size: 13px; line-height: 1.8;">
                                                <li>Nama lengkap danton</li>
                                                <li>NISN</li>
                                                <li>Pas foto danton (format JPG/PNG)</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div style="background: rgba(16,185,129,0.04); border: 1px solid rgba(16,185,129,0.15); border-radius: 8px; padding: 16px; height: 100%;">
                                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                                                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(16,185,129,0.1); display: flex; align-items: center; justify-content: center;">
                                                    <i class="fa fa-users" style="color: #10b981; font-size: 14px;"></i>
                                                </div>
                                                <h6 style="margin: 0; font-size: 14px; font-weight: 600; color: var(--df-on-surface, #0f172a);">Anggota Pasukan</h6>
                                            </div>
                                            <ul style="margin: 0; padding-left: 18px; color: var(--df-secondary, #64748b); font-size: 13px; line-height: 1.8;">
                                                <li>Nama lengkap setiap anggota</li>
                                                <li>NISN setiap anggota</li>
                                                <li>Pas foto setiap anggota (format JPG/PNG)</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div style="margin-top: 20px; padding: 14px 18px; background: rgba(59,130,246,0.06); border-radius: 8px; border: 1px dashed rgba(59,130,246,0.3);">
                                    <p style="margin: 0; font-size: 13px; color: #3b82f6;">
                                        <i class="fa fa-info-circle" style="margin-right: 6px;"></i>
                                        Simpan link halaman ini. Pengisian data akan dibuka setelah Technical Meeting atau pengumuman resmi panitia. Anda akan dihubungi melalui email/WhatsApp.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <fieldset {{ $isLocked ? 'disabled' : '' }}>

                        {{-- Nama Pelatih --}}
                        <div class="wow fadeInUp" style="background: #fff; border: 1px solid var(--df-border, #e2e8f0); border-radius: 8px; margin-bottom: 24px; overflow: hidden;">
                            <div style="padding: 16px 20px; border-bottom: 1px solid var(--df-border, #e2e8f0);">
                                <h5 style="margin: 0; font-size: 16px; font-weight: 600; color: var(--df-on-surface, #0f172a);"><i class="fa fa-user" style="color: var(--df-secondary, #64748b); margin-right: 8px;"></i>Data Pelatih</h5>
                            </div>
                            <div style="padding: 20px;">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--df-on-surface, #0f172a); margin-bottom: 6px;">Nama Pelatih <span style="color: #ef4444;">*</span></label>
                                        <input type="text" wire:model="namaPelatih" style="width: 100%; border: 1px solid var(--df-border, #e2e8f0); border-radius: 8px; padding: 10px 14px; font-size: 14px; outline: none; transition: all 0.2s;" placeholder="Nama lengkap pelatih">
                                        @error('namaPelatih') <span style="display: block; font-size: 12px; color: #ef4444; margin-top: 4px;">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--df-on-surface, #0f172a); margin-bottom: 6px;">No HP</label>
                                        <input type="text" value="{{ $registration->no_hp }}" disabled style="width: 100%; border: 1px solid var(--df-border, #e2e8f0); border-radius: 8px; padding: 10px 14px; font-size: 14px; background: #f9fafb; color: var(--df-secondary, #64748b);">
                                        <small style="display: block; font-size: 12px; color: var(--df-secondary, #64748b); margin-top: 4px;">Tidak bisa diubah</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label style="display: block; font-size: 13px; font-weight: 600; color: var(--df-on-surface, #0f172a); margin-bottom: 6px;">Foto Pelatih</label>
                                        <div wire:ignore x-data="{ pond: null }" x-init="
                                            pond = FilePond.create($refs.input, {
                                                credits: false,
                                                labelIdle: 'Tarik & Letakkan gambar atau <span class=\'filepond--label-action\'>Pilih File</span>',
                                                server: {
                                                    process: (fieldName, file, metadata, load, error, progress, abort, transfer, options) => {
                                                        @this.upload('fotoPelatih', file, load, error, progress)
                                                    },
                                                    revert: (filename, load) => {
                                                        @this.removeUpload('fotoPelatih', filename, load)
                                                    },
                                                },
                                            });
                                        ">
                                            <input type="file" x-ref="input" accept="image/*">
                                        </div>
                                        @error('fotoPelatih') <span style="display: block; font-size: 12px; color: #ef4444; margin-top: 4px;">{{ $message }}</span> @enderror
                                        @if($registration->foto_pelatih)
                                            <span style="display: block; font-size: 12px; color: #10b981; margin-top: 6px;"><i class="fa fa-check" style="margin-right: 4px;"></i>Sudah diunggah</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form wire:submit.prevent="submit(false)">
                            {{-- Documents --}}
                            <div class="wow fadeInUp" style="background: #fff; border: 1px solid var(--df-border, #e2e8f0); border-radius: 8px; margin-bottom: 24px; overflow: hidden;">
                                <div style="padding: 16px 20px; border-bottom: 1px solid var(--df-border, #e2e8f0);">
                                    <h5 style="margin: 0; font-size: 16px; font-weight: 600; color: var(--df-on-surface, #0f172a);"><i class="fa fa-file-alt" style="color: var(--df-secondary, #64748b); margin-right: 8px;"></i>Berkas Persyaratan</h5>
                                </div>
                                <div style="padding: 20px;">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label style="display: block; font-size: 13px; font-weight: 600; color: var(--df-on-surface, #0f172a); margin-bottom: 6px;">Logo Sekolah</label>
                                            <div wire:ignore x-data="{ pond: null }" x-init="
                                                pond = FilePond.create($refs.input, {
                                                    credits: false,
                                                    labelIdle: 'Tarik & Letakkan gambar atau <span class=\'filepond--label-action\'>Pilih File</span>',
                                                    server: {
                                                        process: (fieldName, file, metadata, load, error, progress, abort, transfer, options) => {
                                                            @this.upload('logoSekolah', file, load, error, progress)
                                                        },
                                                        revert: (filename, load) => {
                                                            @this.removeUpload('logoSekolah', filename, load)
                                                        },
                                                    },
                                                });
                                            ">
                                                <input type="file" x-ref="input" accept="image/*">
                                            </div>
                                            @error('logoSekolah') <span style="display: block; font-size: 12px; color: #ef4444; margin-top: 4px;">{{ $message }}</span> @enderror
                                            @if($registration->logo_sekolah)
                                                <span style="display: block; font-size: 12px; color: #10b981; margin-top: 6px;"><i class="fa fa-check" style="margin-right: 4px;"></i>Sudah diunggah</span>
                                            @endif
                                        </div>
                                        @if($registration->eventner->surat_tugas_required)
                                        <div class="col-md-6">
                                            <label style="display: block; font-size: 13px; font-weight: 600; color: var(--df-on-surface, #0f172a); margin-bottom: 6px;">Surat Tugas (.pdf/.jpg)</label>
                                            <div wire:ignore x-data="{ pond: null }" x-init="
                                                pond = FilePond.create($refs.input, {
                                                    credits: false,
                                                    labelIdle: 'Tarik & Letakkan file atau <span class=\'filepond--label-action\'>Pilih File</span>',
                                                    server: {
                                                        process: (fieldName, file, metadata, load, error, progress, abort, transfer, options) => {
                                                            @this.upload('suratTugas', file, load, error, progress)
                                                        },
                                                        revert: (filename, load) => {
                                                            @this.removeUpload('suratTugas', filename, load)
                                                        },
                                                    },
                                                });
                                            ">
                                                <input type="file" x-ref="input">
                                            </div>
                                            @error('suratTugas') <span style="display: block; font-size: 12px; color: #ef4444; margin-top: 4px;">{{ $message }}</span> @enderror
                                            @if($registration->surat_tugas)
                                                <span style="display: block; font-size: 12px; color: #10b981; margin-top: 6px;"><i class="fa fa-check" style="margin-right: 4px;"></i>Sudah diunggah</span>
                                            @endif
                                        </div>
                                        @endif
                                        @if($registration->eventner->kwitansi_required)
                                        <div class="col-md-6">
                                            <label style="display: block; font-size: 13px; font-weight: 600; color: var(--df-on-surface, #0f172a); margin-bottom: 6px;">Kwitansi Pendaftaran</label>
                                            <div wire:ignore x-data="{ pond: null }" x-init="
                                                pond = FilePond.create($refs.input, {
                                                    credits: false,
                                                    labelIdle: 'Tarik & Letakkan gambar atau <span class=\'filepond--label-action\'>Pilih File</span>',
                                                    server: {
                                                        process: (fieldName, file, metadata, load, error, progress, abort, transfer, options) => {
                                                            @this.upload('buktiPendaftaran', file, load, error, progress)
                                                        },
                                                        revert: (filename, load) => {
                                                            @this.removeUpload('buktiPendaftaran', filename, load)
                                                        },
                                                    },
                                                });
                                            ">
                                                <input type="file" x-ref="input" accept="image/*">
                                            </div>
                                            @error('buktiPendaftaran') <span style="display: block; font-size: 12px; color: #ef4444; margin-top: 4px;">{{ $message }}</span> @enderror
                                            @if($registration->bukti_pendaftaran)
                                                <span style="display: block; font-size: 12px; color: #10b981; margin-top: 6px;"><i class="fa fa-check" style="margin-right: 4px;"></i>Sudah diunggah</span>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Danton --}}
                            <div class="wow fadeInUp" style="background: #fff; border: 1px solid var(--df-border, #e2e8f0); border-radius: 8px; margin-bottom: 24px; overflow: hidden;">
                                <div style="padding: 16px 20px; border-bottom: 1px solid var(--df-border, #e2e8f0);">
                                    <h5 style="margin: 0; font-size: 16px; font-weight: 600; color: var(--df-on-surface, #0f172a);"><i class="fa fa-star" style="color: #f59e0b; margin-right: 8px;"></i>Komandan Pleton (Danton)</h5>
                                </div>
                                <div style="padding: 20px;">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label style="display: block; font-size: 13px; font-weight: 600; color: var(--df-on-surface, #0f172a); margin-bottom: 6px;">Nama Danton <span style="color: #ef4444;">*</span></label>
                                            <input type="text" wire:model="dantonNama" style="width: 100%; border: 1px solid var(--df-border, #e2e8f0); border-radius: 8px; padding: 10px 14px; font-size: 14px; outline: none; transition: all 0.2s;" placeholder="Nama lengkap...">
                                            @error('dantonNama') <span style="display: block; font-size: 12px; color: #ef4444; margin-top: 4px;">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <label style="display: block; font-size: 13px; font-weight: 600; color: var(--df-on-surface, #0f172a); margin-bottom: 6px;">NISN</label>
                                            <input type="text" wire:model="dantonNisn" style="width: 100%; border: 1px solid var(--df-border, #e2e8f0); border-radius: 8px; padding: 10px 14px; font-size: 14px; outline: none; transition: all 0.2s;" placeholder="NISN...">
                                            @error('dantonNisn') <span style="display: block; font-size: 12px; color: #ef4444; margin-top: 4px;">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-md-5">
                                            <label style="display: block; font-size: 13px; font-weight: 600; color: var(--df-on-surface, #0f172a); margin-bottom: 6px;">Pas Foto</label>
                                            <div wire:ignore x-data="{ pond: null }" x-init="
                                                pond = FilePond.create($refs.input, {
                                                    credits: false,
                                                    labelIdle: 'Tarik & Letakkan gambar atau <span class=\'filepond--label-action\'>Pilih File</span>',
                                                    server: {
                                                        process: (fieldName, file, metadata, load, error, progress, abort, transfer, options) => {
                                                            @this.upload('dantonFoto', file, load, error, progress)
                                                        },
                                                        revert: (filename, load) => {
                                                            @this.removeUpload('dantonFoto', filename, load)
                                                        },
                                                    },
                                                });
                                            ">
                                                <input type="file" x-ref="input" accept="image/*">
                                            </div>
                                            @error('dantonFoto') <span style="display: block; font-size: 12px; color: #ef4444; margin-top: 4px;">{{ $message }}</span> @enderror
                                            @if($registration->danton_foto)
                                                <span style="display: block; font-size: 12px; color: #10b981; margin-top: 6px;"><i class="fa fa-check" style="margin-right: 4px;"></i>Sudah diunggah</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Participants --}}
                            <div class="wow fadeInUp" style="background: #fff; border: 1px solid var(--df-border, #e2e8f0); border-radius: 8px; margin-bottom: 24px; overflow: hidden;">
                                <div style="padding: 16px 20px; border-bottom: 1px solid var(--df-border, #e2e8f0); display: flex; justify-content: space-between; align-items: center;">
                                    <h5 style="margin: 0; font-size: 16px; font-weight: 600; color: var(--df-on-surface, #0f172a);"><i class="fa fa-users" style="color: var(--df-secondary, #64748b); margin-right: 8px;"></i>Anggota Pasukan</h5>
                                    @if(!$isLocked)
                                        <button type="button" wire:click="addParticipant" style="background: transparent; border: 1px solid var(--event-primary, #0072FF); color: var(--event-primary, #0072FF); border-radius: 8px; padding: 6px 12px; font-size: 13px; font-weight: 600; transition: all 0.2s;">
                                            <i class="fa fa-plus" style="margin-right: 4px;"></i> Tambah
                                        </button>
                                    @endif
                                </div>
                                <div style="padding: 20px;">
                                    @foreach($participants as $index => $participant)
                                        <div wire:key="participant-{{ $index }}" style="display: flex; gap: 16px; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--df-border, #e2e8f0); align-items: flex-start; flex-wrap: wrap;">
                                            <div style="width: 32px; height: 32px; border-radius: 50%; background: rgba(0,114,255,0.1); color: var(--event-primary, #0072FF); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; flex-shrink: 0;">
                                                {{ $index + 1 }}
                                            </div>
                                            <div style="flex: 1; min-width: 200px;">
                                                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--df-secondary, #64748b); margin-bottom: 4px;">Nama <span style="color: #ef4444;">*</span></label>
                                                <input type="text" wire:model="participants.{{ $index }}.nama" style="width: 100%; border: 1px solid var(--df-border, #e2e8f0); border-radius: 6px; padding: 6px 10px; font-size: 13px; outline: none;" placeholder="Nama lengkap...">
                                                @error('participants.'.$index.'.nama') <span style="display: block; font-size: 11px; color: #ef4444; margin-top: 4px;">{{ $message }}</span> @enderror
                                            </div>
                                            <div style="flex: 1; min-width: 120px;">
                                                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--df-secondary, #64748b); margin-bottom: 4px;">NISN</label>
                                                <input type="text" wire:model="participants.{{ $index }}.nisn" style="width: 100%; border: 1px solid var(--df-border, #e2e8f0); border-radius: 6px; padding: 6px 10px; font-size: 13px; outline: none;" placeholder="NISN...">
                                            </div>
                                            <div style="flex: 1; min-width: 160px;">
                                                <label style="display: block; font-size: 12px; font-weight: 600; color: var(--df-secondary, #64748b); margin-bottom: 4px;">Pas Foto</label>
                                                <div wire:ignore x-data="{ pond: null }" x-init="
                                                    pond = FilePond.create($refs.input, {
                                                        credits: false,
                                                        labelIdle: 'Tarik & Letakkan gambar atau <span class=\'filepond--label-action\'>Pilih File</span>',
                                                        server: {
                                                            process: (fieldName, file, metadata, load, error, progress, abort, transfer, options) => {
                                                                @this.upload('participants.{{ $index }}.foto', file, load, error, progress)
                                                            },
                                                            revert: (filename, load) => {
                                                                @this.removeUpload('participants.{{ $index }}.foto', filename, load)
                                                            },
                                                        },
                                                    });
                                                ">
                                                    <input type="file" x-ref="input" accept="image/*">
                                                </div>
                                                @if(isset($participant['existing_foto']) && $participant['existing_foto'])
                                                    <span style="display: block; font-size: 11px; color: #10b981; margin-top: 4px;"><i class="fa fa-check" style="margin-right: 2px;"></i>Sudah diunggah</span>
                                                @endif
                                            </div>
                                            @if(count($participants) > 1 && !$isLocked)
                                                <div style="padding-top: 24px;">
                                                    <button type="button" wire:click="removeParticipant({{ $index }})" style="background: transparent; border: 1px solid #ef4444; color: #ef4444; border-radius: 6px; padding: 6px 10px; cursor: pointer;">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Action Buttons --}}
                            @if(!$isLocked)
                                <div class="wow fadeInUp" style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-bottom: 40px;">
                                    <button type="submit" style="background: transparent; border: 1px solid var(--event-primary, #0072FF); color: var(--event-primary, #0072FF); border-radius: 8px; padding: 12px 24px; font-weight: 600; font-size: 15px; cursor: pointer;">
                                        <i class="fa fa-save" style="margin-right: 6px;"></i> Simpan Draft
                                    </button>

                                    @if($registration->status_berkas === 'booking' && (!$registration->eventner->technical_meeting || now()->gte($registration->eventner->technical_meeting)))
                                        <button type="button" onclick="confirmAction()" style="background: #10b981; border: none; color: #fff; border-radius: 8px; padding: 12px 24px; font-weight: 600; font-size: 15px; cursor: pointer;">
                                            <i class="fa fa-check" style="margin-right: 6px;"></i> Konfirmasi & Kirim
                                        </button>
                                    @elseif($registration->status_berkas !== 'booking')
                                        <button type="button" onclick="confirmFinalization()" class="zubuz-default-btn" style="border: none;">
                                            <span><i class="fa fa-paper-plane" style="margin-right: 6px;"></i> Finalisasi Data</span>
                                        </button>
                                    @endif
                                </div>
                            @else
                                <div class="text-center wow fadeInUp" style="margin-bottom: 40px;">
                                    <span style="display: inline-block; background: rgba(59,130,246,0.1); color: #3b82f6; border: 1px solid #3b82f6; border-radius: 8px; padding: 12px 24px; font-weight: 700; font-size: 16px;">
                                        <i class="fa fa-clock" style="margin-right: 6px;"></i> MENUNGGU VERIFIKASI
                                    </span>
                                </div>
                            @endif
                        </form>
                    </fieldset>
                    @endif

                </div>
            </div>
        </div>
    </div>

    {{-- Bottom Navigation --}}
    <nav class="pm-bottom-nav">
        <a href="{{ route('event.detail', $registration->eventner->slug) }}" class="pm-nav-item">
            <i class="fa fa-home"></i>
            <span>Home</span>
        </a>
        <a href="{{ route('event.participant', $registration->eventner->slug) }}" class="pm-nav-item">
            <i class="fa fa-users"></i>
            <span>Peserta</span>
        </a>
        @if($registration->eventner->vote_active)
        <a href="{{ route('event.vote', $registration->eventner->slug) }}" class="pm-nav-item">
            <i class="fa fa-heart"></i>
            <span>Vote</span>
        </a>
        @endif
        @if($registration->eventner->ticket_active && $registration->eventner->ticket_price)
        <a href="{{ route('event.ticket', $registration->eventner->slug) }}" class="pm-nav-item">
            <i class="fa fa-ticket-alt"></i>
            <span>Tiket</span>
        </a>
        @endif
        <a href="{{ url('/reg/' . $registration->magic_token) }}" class="pm-nav-item active" style="color: var(--pm-primary);">
            <i class="fa fa-link"></i>
            <span>Portal</span>
        </a>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmFinalization() {
            Swal.fire({
                title: 'Finalisasi Data?',
                text: "Data akan dikunci dan tidak bisa diubah lagi.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0072FF',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Kirim!',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('submit', true);
                }
            })
        }

        function confirmAction() {
            Swal.fire({
                title: 'Konfirmasi Pendaftaran?',
                text: "Pastikan semua data pasukan sudah benar. Data akan dikirim ke panitia untuk diverifikasi.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Konfirmasi!',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('confirm');
                }
            })
        }
    </script>
    @push('scripts')
        <script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js"></script>
        <script src="https://unpkg.com/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.js"></script>
        <script src="https://unpkg.com/filepond/dist/filepond.js"></script>
        <script>
            FilePond.registerPlugin(FilePondPluginImagePreview, FilePondPluginFileValidateType);
        </script>
    @endpush
</div>
