<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\Judge;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class JudgeCardController extends Controller
{
    /**
     * Kartu akses juri — 2 lembar per juri:
     *   lembar 1: identitas juri + instruksi ("tutup QR dengan lembar ini")
     *   lembar 2: QR akses tablet
     *
     * Tanpa judge_id: kartu SEMUA juri eventner ini, satu juri dipisah
     * page-break supaya panitia bisa mencetak sekali lalu membagikan.
     */
    public function download($judgeId = null)
    {
        $eventner = Auth::user()->eventner;
        if (! $eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $judges = Judge::where('eventner_id', $eventner->id)
            ->with('assessmentCategories.competitionCategory.parent')
            ->when($judgeId, fn ($q) => $q->where('id', $judgeId))
            ->orderBy('name')
            ->get();

        if ($judges->isEmpty()) {
            abort(404, 'Juri tidak ditemukan.');
        }

        // QR dirender diskalakan 340px di lembar 2 — beberapa juri sekaligus
        // berarti lusinan gambar di satu request.
        ini_set('memory_limit', '512M');

        $filename = $judgeId
            ? 'Kartu_Akses_Juri_' . str_replace(['/', '\\', ' '], '_', $judges->first()->name) . '.pdf'
            : 'Kartu_Akses_Semua_Juri.pdf';

        return Pdf::loadView('eventner.judge.pdf_kartu_akses', [
            'eventner' => $eventner,
            'judges' => $judges,
        ])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }
}
