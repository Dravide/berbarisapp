{{-- Lembar tambahan: arsip panitia.

     Dicetak sebagai halaman kedua di PDF yang sama, bukan berkas terpisah,
     karena yang memegang lembar nilai adalah pelatih — kalau lembar arsip ini
     jadi unduhan sendiri, ia baru tercetak saat panitia ingat, dan bukti
     pengecekan justru hilang di momen yang paling butuh. Satu berkas, dua
     halaman, satu kali tanda tangan.

     Yang ditandatangani pelatih di sini bukan nilai barunya, melainkan
     pernyataan bahwa nilai di halaman pertama sudah ia periksa dan setujui.
     Isinya sengaja diringkas ke total per kategori — halaman pertama sudah
     memuat rincian per juri, dan mengulangnya di sini cuma memperbesar
     peluang dua halaman itu tidak cocok saat salah satu direvisi. --}}
<div style="page-break-before: always;"></div>

<div class="kop">
    <table>
        <tr>
            <td style="width: 70px;">
                @if($eventner->logo_event)
                    <img src="{{ public_path('storage/' . $eventner->logo_event) }}" class="kop-logo">
                @endif
            </td>
            <td style="padding-left: 12px;">
                <div class="kop-title">{{ $eventner->nama_event }}</div>
                <div class="kop-sub">{{ $eventner->diselenggarakan_oleh }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="judul">Lembar Verifikasi Nilai</div>
<div style="text-align:center; font-size:8px; color:#666; letter-spacing:1px; text-transform:uppercase; margin-top:-8px; margin-bottom:14px;">
    Arsip Panitia &mdash; bukti nilai telah dicek pelatih
</div>

<table class="info">
    <tr>
        <td class="lbl">Nama Kontingen</td>
        <td class="val">{{ $registration->display_name }}</td>
        <td class="lbl">NPSN</td>
        <td>{{ $registration->npsn }}</td>
    </tr>
    <tr>
        <td class="lbl">Pelatih</td>
        <td class="val">{{ $registration->nama_pelatih }}</td>
        <td class="lbl">No. Undian</td>
        {{-- Nomor undian diulang di sini dengan sengaja: lembar arsip sering
             dipisah dari lembar nilainya, dan tanpa nomor ini panitia tidak
             punya cara mencocokkan kembali kedua halaman itu ke peserta yang
             sama saat namanya serupa. --}}
        <td class="val">{{ $registration->urutan_tampil ?? '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Kategori Lomba</td>
        <td colspan="3">{{ $registration->competitionCategory->full_name ?? '-' }}</td>
    </tr>
    {{-- Seri sejajar dengan grup: dua seri boleh memakai nama kategori lomba
         yang sama persis, jadi lembar arsip Seri A dan Seri B tak bisa
         dipisahkan setelah dicetak tanpa penanda ini. --}}
    <tr>
        <td class="lbl">Seri</td>
        <td class="val">{{ $registration->competitionSeries->name ?? 'Tanpa Seri' }}</td>
        <td class="lbl">Grup</td>
        <td>{{ $registration->competitionGroup->name ?? '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Babak</td>
        <td class="{{ ($roundIsFinal ?? false) ? 'val babak-final' : 'val' }}">{{ $roundName ?? '—' }}</td>
        <td class="lbl">Dicetak</td>
        <td>{{ now()->translatedFormat('d F Y H:i') }} WIB</td>
    </tr>
</table>

<table class="cat-head">
    <tr>
        <td class="cat-name">Rekapitulasi Nilai</td>
        <td class="cat-score">{{ $finalScore }} poin</td>
    </tr>
</table>

<table class="krit">
    <thead>
        <tr>
            <th>Kategori Penilaian</th>
            <th style="width:90px; text-align:center;">Nilai</th>
        </tr>
    </thead>
    <tbody>
        @foreach($assessmentCategories as $cat)
            <tr>
                <td class="cn">{{ $cat->name }}</td>
                <td class="sv">{{ $categoryTotals[$cat->id] ?? 0 }}</td>
            </tr>
            @if(($categoryDeductions[$cat->id] ?? 0) < 0)
                <tr>
                    <td class="cn" style="color:#c0392b; padding-left:16px;">&rsaquo; Pengurangan</td>
                    <td class="sv" style="color:#c0392b; background:#fdf2f2;">{{ $categoryDeductions[$cat->id] }}</td>
                </tr>
            @endif
        @endforeach
        <tr>
            <td class="cn">Total Nilai Juri</td>
            <td class="sv">{{ $grandTotal }}</td>
        </tr>
        @if($totalDeduction < 0)
            <tr>
                <td class="cn" style="color:#c0392b;">Pengurangan</td>
                <td class="sv" style="color:#c0392b; background:#fdf2f2;">{{ $totalDeduction }}</td>
            </tr>
        @endif
        <tr>
            <td class="cn" style="font-size:11px;">Nilai Akhir</td>
            <td class="sv" style="font-size:12px; background:#1a1a2e; color:#f1c40f;">{{ $finalScore }}</td>
        </tr>
    </tbody>
</table>

<div style="margin-top:14px; border:1px solid #ccc; border-radius:4px; padding:12px 16px; background:#fafafa;">
    <div style="font-weight:bold; font-size:9px; text-transform:uppercase; color:#1a1a2e; margin-bottom:6px; letter-spacing:0.5px;">Pernyataan Pelatih</div>
    <div style="font-size:8px; color:#444; line-height:1.7;">
        Saya, pelatih kontingen di atas, menyatakan bahwa:
        <ol style="margin:4px 0 0 0; padding-left:16px;">
            <li>Nilai pada lembar penilaian halaman pertama telah saya periksa seluruhnya, termasuk rincian nilai tiap juri.</li>
            <li>Jumlah pada rekapitulasi di atas cocok dengan penampilan kontingen saya.</li>
            <li>Saya tidak mengajukan keberatan atas nilai tersebut.</li>
        </ol>
    </div>

    {{-- Ruang catatan sengaja dikosongkan: keberatan yang ditulis pelatih di
         sini jauh lebih berguna bagi panitia daripada formulir keberatan
         terpisah yang baru diisi berhari-hari kemudian. --}}
    <div style="font-size:8px; color:#666; margin-top:8px;">Catatan / keberatan (kosongkan bila tidak ada):</div>
    <div style="border:1px solid #ddd; background:#fff; height:44px; margin-top:3px;"></div>
</div>

<div class="ttd" style="margin-top:24px;">
    <table style="width:100%;">
        <tr>
            <td style="text-align:center; width:50%; vertical-align:top; padding-top:10px;">
                <div style="font-size:9px; color:#666;">{{ $eventner->venue ?: '' }}, {{ now()->translatedFormat('d F Y') }}</div>
                <div class="role" style="margin-bottom:8px; margin-top:6px;">Pelatih</div>
                <br><br><br>
                <span class="line"></span><br>
                <small>{{ $registration->nama_pelatih }}</small>
            </td>
            <td style="text-align:center; width:50%; vertical-align:top; padding-top:10px;">
                <div style="font-size:9px; color:#666;">&nbsp;</div>
                <div class="role" style="margin-bottom:8px; margin-top:6px;">Panitia Penerima</div>
                <br><br><br>
                <span class="line"></span><br>
                <small>{{ $eventner->diselenggarakan_oleh }}</small>
            </td>
        </tr>
    </table>
</div>

<div class="foot">
    {{ $eventner->nama_event }} &mdash; Lembar arsip panitia &mdash; {{ $registration->display_name }} (No. Undian {{ $registration->urutan_tampil ?? '—' }})
</div>
