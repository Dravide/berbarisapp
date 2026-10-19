<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AssessmentCategory;
use App\Models\EventDocument;
use App\Models\Eventner;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PublicJuknisController extends Controller
{
    public function downloadJuknis($slug = null)
    {
        $resolved = app()->bound('current_eventner') ? app('current_eventner') : null;
        if ($resolved) {
            $eventner = $resolved;
        } else {
            $eventner = Eventner::approved()->where('slug', $slug)->firstOrFail();
        }

        $categories = AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $eventner->id)
            ->get();

        if ($categories->isEmpty()) {
            abort(404, 'Juknis belum tersedia untuk event ini.');
        }

        $data = [
            'eventner' => $eventner,
            'categories' => $categories,
        ];

        $pdf = Pdf::loadView('livewire.public.partials.pdf.juknis', $data)
            ->setPaper('A4', 'portrait')
            ->setOption('margin-top', '15mm')
            ->setOption('margin-bottom', '15mm')
            ->setOption('margin-left', '12mm')
            ->setOption('margin-right', '12mm');

        $filename = 'Juknis_'.str_replace(['/', '\\', ':', ' '], '-', $eventner->nama_event).'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Unduh / buka dokumen yang diunggah panitia sendiri.
     *
     * Lewat disk, bukan asset('storage/...'): berkas .doc dan .xlsx harus
     * disajikan sebagai lampiran supaya browser mengunduhnya, bukan mencoba
     * merendernya. PDF dan gambar justru sebaliknya — panitia mengunggahnya
     * untuk dibaca, jadi keduanya disajikan inline dan terbuka di tab baru
     * tanpa memaksa unduh. Nama unduhannya memakai nama asli berkas (dibersihkan
     * di model), jadi panitia melihat persis berkas yang ia unggah.
     *
     * Lewat disk, bukan storage_path(), supaya disk palsu di tes (Storage::fake)
     * tetap berlaku — path mentah akan membuat berkas uji tak pernah ketemu.
     */
    public function downloadDocument($slug = null, $document = null)
    {
        return $this->sajikanDokumen($document);
    }

    /**
     * Varian subdomain (/dokumen/{document}). Event sudah dibinding middleware,
     * dan route-nya tidak punya segmen slug.
     *
     * $subdomain wajib ada di posisi pertama: Laravel menyisipkan parameter
     * domain SEBELUM parameter path, jadi tanpa itu $document menerima nama
     * subdomain — pola yang sama dengan TicketPdfController::downloadFromSubdomain.
     */
    public function downloadDocumentFromSubdomain($subdomain = null, $document = null)
    {
        return $this->sajikanDokumen($document);
    }

    private function sajikanDokumen($document)
    {
        $doc = EventDocument::whereKey((int) $document)->firstOrFail();

        // Sama seperti konten publik lain di file ini: hanya event yang sudah
        // disetujui yang berkasnya boleh disajikan.
        abort_unless(
            Eventner::approved()->whereKey($doc->eventner_id)->exists(),
            404
        );

        if (! Storage::disk('public')->exists($doc->file)) {
            abort(404, 'Berkas dokumen tidak ditemukan.');
        }

        if ($doc->bisa_dipratinjau) {
            // Penghitung sengaja tidak dinaikkan: angka itu dibaca panitia
            // sebagai "berapa kali berkas ini diunduh", dan pratinjau yang
            // sekadar dilihat akan menggelembungkannya.
            //
            // Content-Disposition sengaja TIDAK diisi sendiri: response()
            // menyusunnya lewat makeDisposition() yang meng-encode nama
            // non-ASCII (RFC 5987) — string manual akan jadi header rusak
            // begitu nama berkasnya mengandung huruf beraksen.
            return Storage::disk('public')->response($doc->file, $doc->nama_unduhan, [
                'Content-Type' => $doc->mime_pratinjau,
            ]);
        }

        $doc->increment('download_count');

        return Storage::disk('public')->download($doc->file, $doc->nama_unduhan);
    }
}
