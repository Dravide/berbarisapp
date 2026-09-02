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

    /**
     * PDF detail voter per kontingen — data lengkap & transparan:
     * semua transaksi PAID (nama, email, vote, nominal, ID transaksi, waktu),
     * ringkasan, konteks event + kategori.
     */
    public function downloadDetailPdf(Request $request, Registration $registration)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        abort_unless(
            $registration->eventner_id === $eventner->id,
            403,
            'Kontingen ini bukan milik event Anda.'
        );

        $registration->load(['competitionCategory.parent', 'eventner']);

        $pricePerVote = $eventner->vote_price ?: 1000;

        // Semua transaksi PAID — transparan, tanpa paging, urut waktu bayar
        $voters = VoteTransaction::where('registration_id', $registration->id)
            ->where('status', 'PAID')
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get();

        $summary = VoteTransaction::where('registration_id', $registration->id)
            ->where('status', 'PAID')
            ->selectRaw('COUNT(*) as trx_count, COALESCE(SUM(votes_earned), 0) as total_votes, COALESCE(SUM(amount), 0) as total_amount, COALESCE(MIN(paid_at), ?) as first_paid_at, COALESCE(MAX(paid_at), ?) as last_paid_at', [now(), now()])
            ->first();

        // MIN/MAX via selectRaw kembali string — cast ke Carbon supaya translatedFormat jalan di view
        if ($summary) {
            $summary->first_paid_at = $summary->first_paid_at ? \Carbon\Carbon::parse($summary->first_paid_at) : null;
            $summary->last_paid_at = $summary->last_paid_at ? \Carbon\Carbon::parse($summary->last_paid_at) : null;
        }

        // Transaksi non-PAID utk transparansi lengkap (EXPIRED/FAILED — tidak hitung vote)
        $invalid = VoteTransaction::where('registration_id', $registration->id)
            ->whereIn('status', ['EXPIRED', 'FAILED'])
            ->selectRaw('status, COUNT(*) as trx_count')
            ->groupBy('status')
            ->pluck('trx_count', 'status');

        $data = [
            'eventner' => $eventner,
            'registration' => $registration,
            'pricePerVote' => $pricePerVote,
            'voters' => $voters,
            'summary' => $summary,
            'invalid' => $invalid,
        ];

        $pdf = Pdf::loadView('eventner.vote-results.pdf_detail', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', '10mm')
            ->setOption('margin-bottom', '10mm')
            ->setOption('margin-left', '5mm')
            ->setOption('margin-right', '5mm');

        $filename = 'Detail_Voting_' . str_replace(['/', '\\'], '-', $registration->nama_sekolah) . '_' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($filename);
    }
}
