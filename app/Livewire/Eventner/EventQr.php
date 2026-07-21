<?php

namespace App\Livewire\Eventner;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('QR Link Event - BARIS APP')]
class EventQr extends Component
{
    public $eventner;
    public $qrDataUri;

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;
        if (!$this->eventner) abort(403);
        $this->generateQr();
    }

    public function generateQr()
    {
        $url = $this->eventner->publicUrl('detail');

        // Cari logo BARIS APP — fallback ke logo event
        $favicon = public_path('templates/assets/images/logos/favicon.png');
        if (!file_exists($favicon)) {
            $favicon = $this->eventner->logo_event
                ? Storage::disk('public')->path($this->eventner->logo_event)
                : null;
            if ($favicon && !file_exists($favicon)) $favicon = null;
        }

        $options = new QROptions;
        $options->outputInterface = QRGdImagePNG::class;
        $options->outputBase64 = false;
        $options->scale = 12;
        $options->eccLevel = 'H';
        $options->addLogoSpace = true;
        $options->logoSpaceWidth = 11;
        $options->logoSpaceHeight = 11;

        $qrCode = new QRCode($options);
        $qrPng = $qrCode->render($url);

        // Composite logo di tengah QR
        $qrImg = imagecreatefromstring($qrPng);
        if ($qrImg === false) {
            $this->qrDataUri = null;
            return;
        }

        $qrW = imagesx($qrImg);
        $logoMax = (int)($qrW * 0.22);

        $logoResource = $favicon ? $this->loadLogo($favicon, $logoMax) : null;
        if ($logoResource) {
            $lw = imagesx($logoResource);
            $lh = imagesy($logoResource);
            $dstX = (int)(($qrW - $lw) / 2);
            $dstY = (int)(($qrW - $lh) / 2);
            imagecopy($qrImg, $logoResource, $dstX, $dstY, 0, 0, $lw, $lh);
            imagedestroy($logoResource);
        }

        ob_start();
        imagepng($qrImg);
        $pngData = ob_get_clean();
        imagedestroy($qrImg);

        $this->qrDataUri = 'data:image/png;base64,' . base64_encode($pngData);
    }

    private function loadLogo($path, $maxSize)
    {
        if (!file_exists($path)) return null;

        $info = @getimagesize($path);
        if (!$info) return null;

        $src = null;
        if ($info[2] === IMAGETYPE_PNG) {
            $src = @imagecreatefrompng($path);
        } elseif ($info[2] === IMAGETYPE_JPEG) {
            $src = @imagecreatefromjpeg($path);
        } elseif ($info[2] === IMAGETYPE_WEBP) {
            $src = @imagecreatefromwebp($path);
        }

        if (!$src) return null;

        $ow = imagesx($src);
        $oh = imagesy($src);
        $scale = min($maxSize / $ow, $maxSize / $oh);
        $nw = (int)($ow * $scale);
        $nh = (int)($oh * $scale);

        $resized = imagescale($src, $nw, $nh);
        imagedestroy($src);

        return $resized;
    }

    public function render()
    {
        return view('livewire.eventner.event-qr')
            ->title('QR Link Event - BARIS APP');
    }
}
