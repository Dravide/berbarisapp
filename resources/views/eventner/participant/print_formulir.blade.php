<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Formulir - {{ $registration->nama_sekolah }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.3;
            padding: 15px;
            margin: 0;
        }

        /* CONTAINER */
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
        }

        /* KOP */
        .kop {
            border-bottom: 3px double #333;
            padding-bottom: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .kop-logo {
            width: 70px;
            height: 70px;
            border-radius: 6px;
            margin-right: 15px;
            object-fit: cover;
        }
        .kop-text {
            flex-grow: 1;
        }
        .kop-title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1a1a2e;
            margin: 0;
        }
        .kop-sub {
            font-size: 12px;
            color: #555;
            margin: 4px 0 0 0;
        }

        /* JUDUL */
        .title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #f8f9fa;
            padding: 8px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            letter-spacing: 1px;
        }

        /* SECTION */
        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 2px solid #1a1a2e;
            padding-bottom: 3px;
            margin-top: 14px;
            margin-bottom: 8px;
            color: #1a1a2e;
        }

        /* TABLE DETAIL */
        .table-detail {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .table-detail td {
            padding: 5px 10px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }
        .table-detail .lbl {
            background-color: #fcfcfc;
            font-weight: bold;
            width: 25%;
            color: #555;
        }

        /* FOTO FRAME */
        .foto-container {
            width: 48px;
            height: 64px;
            border: 1px dashed #bbb;
            text-align: center;
            display: inline-block;
            background-color: #fbfbfb;
            position: relative;
        }
        .foto-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .foto-placeholder {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 9px;
            color: #888;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* MEMBER TABLE */
        .table-member {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table-member th {
            background-color: #1a1a2e;
            color: #fff;
            padding: 6px 8px;
            font-weight: bold;
            text-align: left;
            border: 1px solid #1a1a2e;
            font-size: 11px;
            text-transform: uppercase;
        }
        .table-member td {
            padding: 4px 8px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }
        .table-member .center {
            text-align: center;
        }

        /* SIGNATURE */
        .signature-table {
            width: 100%;
            margin-top: 20px;
            border: none;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            border: none;
            vertical-align: top;
            padding: 10px;
        }
        .signature-space {
            height: 40px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
            margin: 0;
        }

        /* PRINT CONFIG */
        @media print {
            body {
                padding: 0;
                margin: 0;
                font-size: 12px;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-before: always;
            }
            .container {
                width: 100%;
                max-width: none;
                margin: 0;
            }
            .table-member th {
                background-color: #1a1a2e !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .title {
                background-color: #f2f2f2 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* TOP ACTION BAR */
        .action-bar {
            background-color: #f8f9fa;
            border: 1px solid #e3e6f0;
            padding: 12px 20px;
            margin-bottom: 25px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-print {
            background-color: #2e59d9;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-print:hover {
            background-color: #224abe;
        }
        .btn-back {
            color: #5a5c69;
            text-decoration: none;
            font-size: 13px;
        }
    </style>
</head>
<body>

    <!-- ACTION BAR (NO PRINT) -->
    <div class="action-bar no-print">
        <a href="javascript:window.close();" class="btn-back">&larr; Tutup Halaman</a>
        <div>
            <button onclick="window.print();" class="btn-print">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Cetak / Simpan PDF
            </button>
        </div>
    </div>

    <div class="container">
        <!-- KOP -->
        <div class="kop">
            @if($eventner->logo_event)
                <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="kop-logo">
            @endif
            <div class="kop-text">
                <h1 class="kop-title">{{ $eventner->nama_event }}</h1>
                <p class="kop-sub">Diselenggarakan oleh: {{ $eventner->diselenggarakan_oleh }}</p>
            </div>
        </div>

        <!-- TITLE -->
        <div class="title">Formulir Pendaftaran Pasukan</div>

        <!-- DATA KONTINGEN -->
        <div class="section-title">I. Identitas Kontingen / Sekolah</div>
        <table class="table-detail">
            <tr>
                <td class="lbl" style="width:18%;">Nama Sekolah</td>
                <td style="width:32%;">{{ $registration->nama_sekolah }}</td>
                <td class="lbl" style="width:18%;">Logo</td>
                <td style="width:32%; text-align:center;">
                    @if($registration->logo_sekolah)
                        <img src="{{ asset('storage/' . $registration->logo_sekolah) }}" style="max-height:50px; max-width:100px;">
                    @else
                        <span style="color:#888; font-size:11px;">Tidak tersedia</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="lbl">NPSN</td>
                <td>{{ $registration->npsn }}</td>
                <td class="lbl">Kategori Lomba</td>
                <td><strong>{{ $registration->competitionCategory->full_name ?? '-' }}</strong></td>
            </tr>
            <tr>
                <td class="lbl">No. HP / WA</td>
                <td>{{ $registration->no_hp }}</td>
                <td class="lbl">Email Sekolah</td>
                <td>{{ $registration->school_email ?? '-' }}</td>
            </tr>
            @if($registration->urutan_tampil)
            <tr>
                <td class="lbl">Urutan Tampil</td>
                <td><strong>#{{ str_pad($registration->urutan_tampil, 2, '0', STR_PAD_LEFT) }}</strong></td>
                <td class="lbl">Status Verifikasi</td>
                <td style="color: green; font-weight: bold;">{{ $registration->status_berkas }}</td>
            </tr>
            @else
            <tr>
                <td class="lbl">Status Verifikasi</td>
                <td colspan="3" style="color: green; font-weight: bold;">{{ $registration->status_berkas }}</td>
            </tr>
            @endif
        </table>

        <!-- STRUKTUR PASUKAN -->
        <div class="section-title">II. Struktur Official &amp; Danton</div>
        <table class="table-detail">
            <tr>
                <td class="lbl" style="width:18%;">Pelatih / Official</td>
                <td style="width:32%;">
                    <strong>{{ $registration->nama_pelatih ?? '-' }}</strong>
                </td>
                <td class="lbl" style="width:18%; text-align: center;">Foto Pelatih</td>
                <td style="width:32%; text-align: center;">
                    <div class="foto-container">
                        @if($registration->foto_pelatih)
                            <img src="{{ asset('storage/' . $registration->foto_pelatih) }}" class="foto-img">
                        @else
                            <div class="foto-placeholder">Foto 3x4</div>
                        @endif
                    </div>
                </td>
            </tr>
            <tr>
                <td class="lbl">Komandan Ton (Danton)</td>
                <td>
                    <strong>{{ $registration->danton_nama ?? '-' }}</strong>
                    <p style="margin: 3px 0 0 0; font-size:11px;">NISN: {{ $registration->danton_nisn ?? '-' }}</p>
                </td>
                <td class="lbl" style="text-align: center;">Foto Danton</td>
                <td style="text-align: center;">
                    <div class="foto-container">
                        @if($registration->danton_foto)
                            <img src="{{ asset('storage/' . $registration->danton_foto) }}" class="foto-img">
                        @else
                            <div class="foto-placeholder">Foto 3x4</div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        {{-- Berkas Lampiran --}}
        @if($eventner->surat_tugas_required || $eventner->kwitansi_required)
        <div class="section-title">III. Berkas Persyaratan</div>
        <table class="table-detail">
            <tr>
                @if($eventner->surat_tugas_required)
                <td class="lbl" style="width:18%;">Surat Tugas</td>
                <td style="width:32%;">
                    @if($registration->surat_tugas)
                        <span style="color:green; font-weight:bold;">✓ Terlampir</span>
                    @else
                        <span style="color:red;">✗ Belum diunggah</span>
                    @endif
                </td>
                @endif
                @if($eventner->kwitansi_required)
                <td class="lbl" style="width:18%;">Kwitansi</td>
                <td style="width:32%;">
                    @if($registration->bukti_pendaftaran || $registration->payment_proof)
                        <span style="color:green; font-weight:bold;">✓ Terlampir</span>
                    @else
                        <span style="color:red;">✗ Belum diunggah</span>
                    @endif
                </td>
                @endif
            </tr>
        </table>
        @endif

        <!-- PAGE BREAK UNTUK ANGGOTA (1 halaman → lanjut saja tanpa pecah) -->

        <div class="section-title">IV. Daftar Anggota Pasukan</div>
        <table class="table-member">
            <thead>
                <tr>
                    <th style="width: 6%; text-align: center;">No</th>
                    <th style="width: 17%; text-align: center;">Foto</th>
                    <th style="width: 42%;">Nama Lengkap</th>
                    <th style="width: 35%;">NISN</th>
                </tr>
            </thead>
            <tbody>
                @forelse($participants as $index => $participant)
                    <tr>
                        <td class="center">{{ $index + 1 }}.</td>
                        <td class="center" style="padding: 4px 0;">
                            <div class="foto-container" style="width: 45px; height: 60px;">
                                @if($participant->foto)
                                    <img src="{{ asset('storage/' . $participant->foto) }}" class="foto-img" style="width: 45px; height: 60px;">
                                @else
                                    <div class="foto-placeholder" style="font-size:7px;">FOTO</div>
                                @endif
                            </div>
                        </td>
                        <td><strong>{{ $participant->nama }}</strong></td>
                        <td>{{ $participant->nisn ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="center" style="padding: 30px; color: #888;">Belum ada anggota pasukan yang didaftarkan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- TANDA TANGAN -->
        @php
            use chillerlan\QRCode\QRCode;
            $qrData = route('magic.link', $registration->magic_token);
            $qrImage = (new QRCode)->render($qrData);
        @endphp
        <div class="section-title" style="margin-top: 18px;">V. Pengesahan</div>
        <table class="signature-table">
            <tr>
                <td>
                    <p><strong>Pelatih / Official</strong></p>
                    <div class="signature-space"></div>
                    <p class="signature-name">{{ $registration->nama_pelatih ?? '............................' }}</p>
                </td>
                <td>
                    <p><strong>Ketua Panitia</strong></p>
                    <div style="margin: 0 auto; text-align: center;">
                        <img src="{{ $qrImage }}" style="width:80px; height:80px;" alt="QR">
                    </div>
                    <p style="font-size:10px; margin:4px 0 0 0;">Scan untuk verifikasi data</p>
                    <p class="signature-name" style="margin-top: 8px;">{{ $eventner->diselenggarakan_oleh }}</p>
                </td>
            </tr>
        </table>
    </div>

    <script>
        // Pemicu cetak otomatis setelah halaman termuat penuh
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
