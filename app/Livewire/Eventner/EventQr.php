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
class EventQr extends Component
{
    public $eventner;

    /** @var array<string, array{label: string, url: string, dataUri: ?string, catatan: ?string, nada: ?string}> */
    public $qrs = [];

    public array $icons = [
        'detail' => 'ti ti-home',
        'register' => 'ti ti-clipboard-list',
        'ticket' => 'ti ti-ticket',
        'vote' => 'ti ti-heart',
    ];

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;
        if (!$this->eventner) abort(403);
        $this->generateQr();
    }

    public function generateQr()
    {
        $favicon = $this->resolveFavicon();

        $targets = [
            'detail' => [
                'label' => 'Halaman Event',
                'url' => $this->eventner->publicUrl('detail'),
            ],
            'register' => [
                'label' => 'Formulir Pendaftaran',
                'url' => $this->eventner->publicUrl('register'),
            ],
            'ticket' => [
                'label' => 'Laman Tiket',
                'url' => $this->eventner->publicUrl('ticket'),
            ],
            'vote' => [
                'label' => 'Laman Vote',
                'url' => $this->eventner->publicUrl('vote'),
            ],
        ];

        foreach ($targets as $key => $target) {
            $this->qrs[$key] = [
                'label' => $target['label'],
                'url' => $target['url'],
                'dataUri' => $this->renderQr($target['url'], $favicon),
            ] + $this->catatanStatus($key);
        }
    }

    /**
     * Rambu di bawah QR pendaftaran.
     *
     * QR dicetak lalu ditempel di spanduk — ia hidup lebih lama daripada
     * pendaftarannya. Tombol "Daftar Sekarang" di halaman event memang
     * hilang sendiri saat status 'closed', tetapi QR ini tetap bisa dipindai
     * dan mendaratkan sekolah di formulir yang sudah ditutup. Jadi statusnya
     * diterangkan di sini, bukan disimpulkan sendiri oleh pemindai.
     *
     * Ambang batasnya dibaca dari accessor registration_status — sumber yang
     * sama dengan halaman publik — supaya badge panitia dan QR tak berbeda.
     */
    private function catatanStatus(string $key): array
    {
        if ($key !== 'register') {
            return [];
        }

        // tanggal_pendaftaran tidak di-cast ke datetime, jadi ia string mentah.
        $batas = $this->eventner->tanggal_pendaftaran
            ? \Carbon\Carbon::parse($this->eventner->tanggal_pendaftaran)->translatedFormat('d F Y, H:i')
            : null;

        return match ($this->eventner->registration_status) {
            'open' => [
                'catatan' => $batas
                    ? 'Pendaftaran dibuka sampai ' . $batas . '.'
                    : 'Pendaftaran sedang dibuka.',
                'nada' => 'success',
            ],
            'booking' => [
                'catatan' => 'Mode booking: slot dipesan dulu, pengisian data pasukan dibuka panitia setelah technical meeting.',
                'nada' => 'info',
            ],
            default => [
                'catatan' => $batas
                    ? 'Pendaftaran sudah ditutup pada ' . $batas . '. QR ini hanya menampilkan riwayat.'
                    : 'Pendaftaran publik ditutup: deadline pendaftaran belum ditetapkan panitia.',
                'nada' => 'warning',
            ],
        };
    }

    /**
     * Cari logo favicon dari admin settings — fallback ke logo event, lalu favicon default.
     */
    private function resolveFavicon(): ?string
    {
        $favicon = null;
        $faviconPath = \App\Models\Setting::get('favicon');
        if ($faviconPath) {
            $favicon = Storage::disk('public')->path($faviconPath);
            if (!file_exists($favicon)) $favicon = null;
        }
        if (!$favicon) {
            $favicon = $this->eventner->logo_event
                ? Storage::disk('public')->path($this->eventner->logo_event)
                : null;
            if ($favicon && !file_exists($favicon)) $favicon = null;
        }
        if (!$favicon) {
            $favicon = public_path('templates/assets/images/logos/favicon.png');
            if (!file_exists($favicon)) $favicon = null;
        }

        return $favicon;
    }

    private function renderQr(string $url, ?string $favicon): ?string
    {
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
            return null;
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

        return 'data:image/png;base64,' . base64_encode($pngData);
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
            ->title('QR Link Event - ' . app_name());
    }
}
