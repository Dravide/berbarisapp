{{--
    Blok tanda tangan panitia untuk dokumen PDF.
    Variabel wajib: $eventner (App\Models\Eventner).
    Mode 'image' + activeSignature → tampil gambar TTD/stempel.
    Mode 'qr' / fallback            → tampil QR link event.
    Dipakai di dalam <td> (bukan pembungkus td sendiri).
--}}
@if($eventner->signature_mode === 'image' && $eventner->activeSignature && $eventner->activeSignature->image)
    @php
        $signaturePath = public_path('storage/' . $eventner->activeSignature->image);
        $signatureExists = file_exists($signaturePath);
    @endphp
    @if($signatureExists)
        <img src="{{ $signaturePath }}" style="height:80px; max-width:180px; object-fit:contain;">
    @else
        <div style="height:80px;"></div>
    @endif
@else
    @php
        $qrData = event_url($eventner, 'detail');
        $qrImage = (new \chillerlan\QRCode\QRCode)->render($qrData);
    @endphp
    <img src="{{ $qrImage }}" style="width:72px; height:72px;">
    <p style="font-size:7px; color:#888; margin:2px 0 0;">Scan untuk info &amp; verifikasi event</p>
@endif
