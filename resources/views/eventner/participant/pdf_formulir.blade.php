<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Formulir Pendaftaran - {{ $registration->nama_sekolah }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        /* KOP */
        .kop {
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .kop table {
            width: 100%;
            border: none;
        }
        .kop td {
            border: none;
            vertical-align: middle;
            padding: 0;
        }
        .kop-logo {
            width: 65px;
            height: 65px;
            border-radius: 4px;
        }
        .kop-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1a1a2e;
        }
        .kop-sub {
            font-size: 11px;
            color: #555;
            margin-top: 2px;
        }

        /* JUDUL */
        .title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #f2f2f2;
            padding: 6px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            letter-spacing: 1px;
        }

        /* SECTION TITLE */
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #1a1a2e;
            padding-bottom: 3px;
            margin-top: 15px;
            margin-bottom: 8px;
            color: #1a1a2e;
        }

        /* DETAIL TABLE */
        .table-detail {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .table-detail td {
            padding: 5px 8px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        .table-detail .lbl {
            background-color: #fafafa;
            font-weight: bold;
            width: 25%;
        }

        /* FOTO FRAME */
        .foto-container {
            width: 75px;
            height: 100px;
            border: 1px dashed #999;
            text-align: center;
            vertical-align: middle;
            display: inline-block;
            background-color: #fafafa;
        }
        .foto-img {
            width: 75px;
            height: 100px;
            object-fit: cover;
        }
        .foto-placeholder {
            font-size: 8px;
            color: #888;
            padding-top: 40px;
            font-weight: bold;
        }

        /* MEMBER TABLE */
        .table-member {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .table-member th {
            background-color: #1a1a2e;
            color: #fff;
            padding: 5px 8px;
            font-weight: bold;
            text-align: left;
            border: 1px solid #1a1a2e;
            font-size: 10px;
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
            margin-top: 30px;
            border: none;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            border: none;
            vertical-align: top;
        }
        .signature-space {
            height: 55px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>

    @php
        $safeLogoPath = null;
        if ($eventner->logo_event) {
            $fullPath = public_path('storage/' . $eventner->logo_event);
            if (file_exists($fullPath) && is_file($fullPath)) {
                $safeLogoPath = $fullPath;
            }
        }

        $safeFotoPelatih = null;
        if ($registration->foto_pelatih) {
            $fullPath = public_path('storage/' . $registration->foto_pelatih);
            if (file_exists($fullPath) && is_file($fullPath)) {
                $safeFotoPelatih = $fullPath;
            }
        }

        $safeFotoDanton = null;
        if ($registration->danton_foto) {
            $fullPath = public_path('storage/' . $registration->danton_foto);
            if (file_exists($fullPath) && is_file($fullPath)) {
                $safeFotoDanton = $fullPath;
            }
        }
    @endphp

    <!-- KOP -->
    <div class="kop">
        <table>
            <tr>
                @if($safeLogoPath)
                    <td style="width: 75px;">
                        <img src="{{ $safeLogoPath }}" class="kop-logo">
                    </td>
                @endif
                <td style="padding-left: 10px;">
                    <div class="kop-title">{{ $eventner->nama_event }}</div>
                    <div class="kop-sub">Diselenggarakan oleh: {{ $eventner->diselenggarakan_oleh }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- TITLE -->
    <div class="title">Formulir Pendaftaran Pasukan</div>

    <!-- DATA KONTINGEN -->
    <div class="section-title">I. Identitas Kontingen / Sekolah</div>
    <table class="table-detail">
        <tr>
            <td class="lbl">Nama Sekolah</td>
            <td>{{ $registration->nama_sekolah }}</td>
            <td class="lbl">NPSN</td>
            <td>{{ $registration->npsn }}</td>
        </tr>
        <tr>
            <td class="lbl">Kategori Lomba</td>
            <td>{{ $registration->competitionCategory->name ?? '-' }}</td>
            <td class="lbl">No. HP / WhatsApp</td>
            <td>{{ $registration->no_hp }}</td>
        </tr>
        <tr>
            <td class="lbl">Email Sekolah</td>
            <td>{{ $registration->school_email ?? '-' }}</td>
            <td class="lbl">Status Verifikasi</td>
            <td style="color: green; font-weight: bold;">TERVERIFIKASI</td>
        </tr>
    </table>

    <!-- STRUKTUR PASUKAN -->
    <div class="section-title">II. Struktur Official & Danton</div>
    <table class="table-detail">
        <tr>
            <td class="lbl" style="width: 20%;">Pelatih / Official</td>
            <td style="width: 30%;">
                <strong>{{ $registration->nama_pelatih ?? '-' }}</strong>
                <p style="margin: 5px 0 0 0; font-size: 9px; color: #666;">Pelatih Utama / Penanggung Jawab Pasukan</p>
            </td>
            <td class="lbl" style="width: 20%; text-align: center;">Foto Pelatih</td>
            <td style="width: 30%; text-align: center;">
                <div class="foto-container">
                    @if($safeFotoPelatih)
                        <img src="{{ $safeFotoPelatih }}" class="foto-img">
                    @else
                        <div class="foto-placeholder">FOTO 3X4</div>
                    @endif
                </div>
            </td>
        </tr>
        <tr>
            <td class="lbl">Komandan Ton (Danton)</td>
            <td>
                <strong>{{ $registration->danton_nama ?? '-' }}</strong>
                <p style="margin: 3px 0 0 0;">NISN: {{ $registration->danton_nisn ?? '-' }}</p>
            </td>
            <td class="lbl" style="text-align: center;">Foto Danton</td>
            <td style="text-align: center;">
                <div class="foto-container">
                    @if($safeFotoDanton)
                        <img src="{{ $safeFotoDanton }}" class="foto-img">
                    @else
                        <div class="foto-placeholder">FOTO 3X4</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- TANDA TANGAN KONTINGEN & PANITIA -->
    <table class="signature-table">
        <tr>
            <td>
                <p>Pelatih / Official,</p>
                <div class="signature-space"></div>
                <p class="signature-name">{{ $registration->nama_pelatih ?? '............................' }}</p>
            </td>
            <td>
                <p>Panitia Pelaksana,</p>
                <div class="signature-space"></div>
                <p class="signature-name">Verifikator BARIS APP</p>
                <p style="font-size: 9px; margin-top: 2px;">(Sistem Terverifikasi Otomatis)</p>
            </td>
        </tr>
    </table>

    <!-- ANGGOTA PASUKAN (PAGE BREAK) -->
    <div class="page-break"></div>

    <div class="kop">
        <table>
            <tr>
                @if($safeLogoPath)
                    <td style="width: 75px;">
                        <img src="{{ $safeLogoPath }}" class="kop-logo">
                    </td>
                @endif
                <td style="padding-left: 10px;">
                    <div class="kop-title">{{ $eventner->nama_event }}</div>
                    <div class="kop-sub">Daftar Anggota Pasukan - {{ $registration->nama_sekolah }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">III. Daftar Anggota Pasukan</div>
    <table class="table-member">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">No</th>
                <th style="width: 50%;">Nama Lengkap</th>
                <th style="width: 25%;">NISN</th>
                <th style="width: 20%; text-align: center;">Foto 3x4</th>
            </tr>
        </thead>
        <tbody>
            @forelse($participantsData as $index => $participant)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td><strong>{{ $participant['nama'] }}</strong></td>
                    <td>{{ $participant['nisn'] }}</td>
                    <td class="center" style="padding: 5px 0;">
                        <div class="foto-container" style="width: 45px; height: 60px;">
                            @if($participant['foto_path'])
                                <img src="{{ $participant['foto_path'] }}" class="foto-img" style="width: 45px; height: 60px;">
                            @else
                                <div class="foto-placeholder" style="padding-top: 22px; font-size: 7px;">FOTO</div>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="center" style="padding: 20px; color: #888;">Belum ada anggota pasukan yang didaftarkan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
