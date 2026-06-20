<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\VoteTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class VoteResultsController extends Controller
{
    public function downloadPdf(Request $request)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $pricePerVote = $eventner->vote_price ?: 1000;

        // Hitung Summary secara global untuk eventner ini
        $summary = VoteTransaction::where('eventner_id', $eventner->id)
            ->where('status', 'PAID')
            ->selectRaw('COUNT(*) as trx_count, COALESCE(SUM(votes_earned), 0) as total_votes, COALESCE(SUM(amount), 0) as total_amount')
            ->first();

        // Ambil kategori kompetisi
        $categories = $eventner->competitionCategories()->orderBy('name')->get();

        // Hitung klasemen per kategori
        $rankings = [];
        foreach ($categories as $category) {
            $rankings[$category->id] = Registration::where('eventner_id', $eventner->id)
                ->where('competition_category_id', $category->id)
                ->withSum(['voteTransactions as total_votes' => function ($query) {
                    $query->where('status', 'PAID');
                }], 'votes_earned')
                ->orderByDesc('total_votes')
                ->get(['id', 'nama_sekolah', 'logo_sekolah', 'danton_nama']);
        }

        $data = [
            'eventner' => $eventner,
            'pricePerVote' => $pricePerVote,
            'summary' => $summary,
            'categories' => $categories,
            'rankings' => $rankings,
        ];

        $pdf = Pdf::loadView('eventner.vote-results.pdf_recap', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', '10mm')
            ->setOption('margin-bottom', '10mm')
            ->setOption('margin-left', '5mm')
            ->setOption('margin-right', '5mm');

        $filename = 'Rekap_Voting_' . str_replace(['/', '\\'], '-', $eventner->nama_event) . '.pdf';
        return $pdf->download($filename);
    }
}
