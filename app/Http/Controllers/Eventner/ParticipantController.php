<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class ParticipantController extends Controller
{
    public function downloadPdf(Registration $registration)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        // Verifikasi kepemilikan
        if ($registration->eventner_id !== $eventner->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk data ini.');
        }

        // Verifikasi status berkas
        if ($registration->status_berkas !== 'Terverifikasi') {
            abort(403, 'Formulir hanya dapat diunduh jika status pendaftaran telah Terverifikasi.');
        }

        // Eager load data
        $registration->load(['participants', 'competitionCategory', 'eventner']);

        // Generate safe image paths and optimized thumbnails
        $logoPath = $eventner->logo_event ? public_path('storage/' . $eventner->logo_event) : null;
        $safeLogoPath = ($logoPath && file_exists($logoPath) && is_file($logoPath)) ? $logoPath : null;

        $fotoPelatihPath = $registration->foto_pelatih ? public_path('storage/' . $registration->foto_pelatih) : null;
        $safeFotoPelatih = $this->getThumbnailPath($fotoPelatihPath, 150);

        $fotoDantonPath = $registration->danton_foto ? public_path('storage/' . $registration->danton_foto) : null;
        $safeFotoDanton = $this->getThumbnailPath($fotoDantonPath, 150);

        $participantsData = [];
        foreach ($registration->participants as $participant) {
            $fotoAnggotaPath = $participant->foto ? public_path('storage/' . $participant->foto) : null;
            $safeFotoAnggota = $this->getThumbnailPath($fotoAnggotaPath, 100);

            $participantsData[] = [
                'nama' => $participant->nama,
                'nisn' => $participant->nisn,
                'foto_path' => $safeFotoAnggota,
            ];
        }

        $data = [
            'eventner' => $eventner,
            'registration' => $registration,
            'safeLogoPath' => $safeLogoPath,
            'safeFotoPelatih' => $safeFotoPelatih,
            'safeFotoDanton' => $safeFotoDanton,
            'participantsData' => $participantsData,
        ];

        $pdf = Pdf::loadView('eventner.participant.pdf_formulir', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', '15mm')
            ->setOption('margin-bottom', '15mm')
            ->setOption('margin-left', '10mm')
            ->setOption('margin-right', '10mm');

        $filename = 'Formulir_' . str_replace(['/', '\\', ' '], '_', $registration->nama_sekolah) . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Generate an optimized thumbnail for high-res images to speed up DomPDF rendering
     */
    private function getThumbnailPath($originalPath, $width = 120)
    {
        if (!$originalPath || !file_exists($originalPath) || !is_file($originalPath)) {
            return null;
        }

        $filename = basename($originalPath);
        $cacheDir = storage_path('app/public/img_cache');
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Unique cache name based on path and mod time to detect replacements
        $cacheKey = md5($originalPath . filemtime($originalPath)) . '_' . $width . '_' . $filename;
        $cachePath = $cacheDir . '/' . $cacheKey;

        // Return if thumbnail already generated
        if (file_exists($cachePath)) {
            return $cachePath;
        }

        // Resize image on-the-fly using GD
        try {
            list($w, $h) = getimagesize($originalPath);
            if (!$w || !$h) return $originalPath;

            $ratio = $w / $h;
            $newHeight = $width / $ratio;

            $imgInfo = getimagesize($originalPath);
            $mime = $imgInfo['mime'];

            switch ($mime) {
                case 'image/jpeg':
                    $src = imagecreatefromjpeg($originalPath);
                    break;
                case 'image/png':
                    $src = imagecreatefrompng($originalPath);
                    break;
                case 'image/gif':
                    $src = imagecreatefromgif($originalPath);
                    break;
                case 'image/webp':
                    if (function_exists('imagecreatefromwebp')) {
                        $src = imagecreatefromwebp($originalPath);
                    } else {
                        return $originalPath;
                    }
                    break;
                default:
                    return $originalPath;
            }

            if (!$src) return $originalPath;

            $tmp = imagecreatetruecolor($width, $newHeight);

            // Handle PNG/GIF transparency
            if ($mime == 'image/png' || $mime == 'image/gif') {
                imagealphablending($tmp, false);
                imagesavealpha($tmp, true);
            }

            imagecopyresampled($tmp, $src, 0, 0, 0, 0, $width, $newHeight, $w, $h);

            switch ($mime) {
                case 'image/jpeg':
                    imagejpeg($tmp, $cachePath, 75); // Compress to 75% quality
                    break;
                case 'image/png':
                    imagepng($tmp, $cachePath, 6);
                    break;
                case 'image/gif':
                    imagegif($tmp, $cachePath);
                    break;
                case 'image/webp':
                    imagewebp($tmp, $cachePath, 75);
                    break;
            }

            imagedestroy($src);
            imagedestroy($tmp);

            return $cachePath;
        } catch (\Exception $e) {
            return $originalPath; // Fallback to original image if GD fails
        }
    }
}
