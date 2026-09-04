<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Formulir Pendaftaran - {{ $registration->display_name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }
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
            margin-bottom: 14px;
        }
        .kop table {
            width: 100%;
            border: none;
            border-collapse: collapse;
        }
        .kop td {
            border: none;
            vertical-align: middle;
            padding: 0;
        }
        .kop-logo {
            width: 62px;
            height: 62px;
            object-fit: contain;
        }
        .kop-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1a1a2e;
            margin: 0;
        }
        .kop-sub {
            font-size: 11px;
            color: #555;
            margin-top: 2px;
        }
        /* JUDUL */
        .title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #f2f2f2;
            padding: 6px;
            margin-bottom: 12px;
            border: 1px solid #ddd;
            letter-spacing: 1px;
        }
        /* SECTION */
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #1a1a2e;
            padding-bottom: 3px;
            margin-top: 12px;
            margin-bottom: 7px;
            color: #1a1a2e;
        }
        /* DETAIL TABLE */
        .table-detail {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
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
            width: 58px;
            height: 78px;
            border: 1px dashed #999;
            text-align: center;
            display: inline-block;
            background-color: #fafafa;
        }
        .foto-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .foto-placeholder {
            font-size: 8px;
            color: #888;
            padding-top: 30px;
            font-weight: bold;
        }
        /* MEMBER GRID (3 per baris pakai table) */
        .member-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .member-grid td {
            width: 33.33%;
            border: 1px solid #ddd;
            padding: 8px 4px;
            text-align: center;
            vertical-align: top;
        }
        .member-info {
            font-size: 11px;
            margin-top: 4px;
        }
        .member-info strong {
            display: block;
            font-size: 11px;
        }
        .member-info span {
            font-size: 9px;
            color: #666;
        }
        /* SIGNATURE */
        .pernyataan {
            text-align: justify;
            margin: 8px 0 18px 0;
            font-size: 11px;
            line-height: 1.6;
        }
        .signature-table {
            width: 100%;
            margin-top: 18px;
            border: none;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            border: none;
            vertical-align: top;
        }
        .signature-space {
            height: 58px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    @php
        $safeLogo = null;
        if ($eventner->logo_event) {
            $p = public_path('storage/' . $eventner->logo_event);
            if (file_exists($p) && is_file($p)) $safeLogo = $p;
        }
        $safeSekolah = null;
        if ($registration->logo_sekolah) {
            $p = public_path('storage/' . $registration->logo_sekolah);
            if (file_exists($p) && is_file($p)) $safeSekolah = $p;
        }
        $safeFotoPelatih = null;
        if ($registration->foto_pelatih) {
            $p = public_path('storage/' . $registration->foto_pelatih);
            if (file_exists($p) && is_file($p)) $safeFotoPelatih = $p;
        }
        $safeFotoDanton = null;
        if ($registration->danton_foto) {
            $p = public_path('storage/' . $registration->danton_foto);
            if (file_exists($p) && is_file($p)) $safeFotoDanton = $p;
        }
    @endphp

    <!-- KOP -->
    <div class="kop">
        <table>
            <tr>
                <td style="width: 65px;">
                    @if($safeLogo)
                        <img src="{{ $safeLogo }}" class="kop-logo">
                    @endif
                </td>
                <td style="padding-left: 10px;">
                    <div class="kop-title">{{ $eventner->nama_event }}</div>
                    <div class="kop-sub">Diselenggarakan oleh: {{ $eventner->diselenggarakan_oleh }}</div>
                </td>
                <td style="width: 65px; text-align: right;">
                    @if($safeSekolah)
                        <img src="{{ $safeSekolah }}" class="kop-logo">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- TITLE -->
    <div class="title">Formulir Pendaftaran Pasukan</div>

    <!-- I. IDENTITAS -->
    <div class="section-title">I. Identitas Kontingen / Sekolah</div>
    <table class="table-detail">
        <tr>
            <td class="lbl">Nama Sekolah</td>
            <td>{{ $registration->display_name }}</td>
            <td class="lbl">NPSN</td>
            <td>{{ $registration->npsn }}</td>
        </tr>
        <tr>
            <td class="lbl">Kategori Lomba</td>
            <td><strong>{{ $registration->competitionCategory->full_name ?? '-' }}</strong></td>
            <td class="lbl">Kontak (HP / WA)</td>
            <td>{{ $registration->no_hp }}</td>
        </tr>
    </table>

    <!-- II. STRUKTUR -->
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
                    @if($safeFotoPelatih)
                        <img src="{{ $safeFotoPelatih }}" class="foto-img">
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
                <p style="margin:2px 0 0 0; font-size:10px;">NISN: {{ $registration->danton_nisn ?? '-' }}</p>
            </td>
            <td class="lbl" style="text-align: center;">Foto Danton</td>
            <td style="text-align: center;">
                <div class="foto-container">
                    @if($safeFotoDanton)
                        <img src="{{ $safeFotoDanton }}" class="foto-img">
                    @else
                        <div class="foto-placeholder">Foto 3x4</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- III. DAFTAR ANGGOTA -->
    <div class="section-title">III. Daftar Anggota Pasukan</div>
    @php
        $rows = $participants->chunk(3);
        $photoOf = function ($participant) {
            if ($participant->foto) {
                $p = public_path('storage/' . $participant->foto);
                if (file_exists($p) && is_file($p)) return $p;
            }
            return null;
        };
    @endphp
    @if($participants->isNotEmpty())
        @foreach($rows as $chunk)
            <table class="member-grid">
                <tr>
                    @foreach($chunk as $participant)
                        @php $fp = $photoOf($participant); @endphp
                        <td>
                            <div class="foto-container">
                                @if($fp)
                                    <img src="{{ $fp }}" class="foto-img" style="width:56px; height:74px;">
                                @else
                                    <div class="foto-placeholder">Foto</div>
                                @endif
                            </div>
                            <div class="member-info">
                                <strong>{{ $participant->nama }}</strong>
                                <span>NISN: {{ $participant->nisn ?: '-' }}</span>
                            </div>
                        </td>
                    @endforeach
                </tr>
            </table>
        @endforeach
    @else
        <div style="text-align:center; padding:20px; color:#888; border:1px solid #ddd;">Belum ada anggota pasukan yang didaftarkan.</div>
    @endif

    <!-- IV. PENGESAHAN -->
    <div class="section-title" style="margin-top:16px;">IV. Pengesahan</div>
    <p class="pernyataan">
        Dengan ini menyatakan bahwa data pasukan pada formulir ini adalah benar dan
        sesuai dengan keadaan yang sebenarnya. Apabila di kemudian hari ditemukan
        ketidaksesuaian data, maka pihak sekolah bersedia menerima konsekuensinya.
    </p>

    @php
        use chillerlan\QRCode\QRCode;
        $qrImage = (new QRCode)->render(route('magic.link', $registration->magic_token));
    @endphp
    <table class="signature-table">
        <tr>
            <td>
                <p><strong>Pelatih / Official</strong></p>
                <div class="signature-space"></div>
                <p class="signature-name">{{ $registration->nama_pelatih ?? '............................' }}</p>
            </td>
            <td>
                <p><strong>Ketua Panitia</strong></p>
                <div style="text-align:center;">
                    <img src="{{ $qrImage }}" style="width:72px; height:72px;">
                </div>
                <p style="font-size:9px; margin:2px 0;">Scan untuk verifikasi data</p>
                <p class="signature-name">{{ $eventner->diselenggarakan_oleh }}</p>
            </td>
        </tr>
    </table>

</body>
</html>