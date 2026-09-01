<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\VoteTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class FinanceDashboardController extends Controller
{
    public function downloadPdf()
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $eventnerId = $eventner->id;

        // Ringkasan pendapatan (sama dengan FinanceDashboard::loadData)
        $feeRevenue = (float) Registration::where('eventner_id', $eventnerId)
            ->where('payment_status', 'paid')
            ->sum('total_fee');

        $voteRevenue = (float) VoteTransaction::where('eventner_id', $eventnerId)
            ->where('status', 'PAID')
            ->sum('amount');

        $ticketRevenue = (float) Ticket::where('eventner_id', $eventnerId)
            ->whereIn('status', ['PAID', 'CHECKED_IN'])
            ->sum('total_amount');

        $totalRevenue = $feeRevenue + $voteRevenue + $ticketRevenue;

        $pendingVerificationCount = Registration::where('eventner_id', $eventnerId)
            ->where('payment_status', 'pending_verification')
            ->count();

        // Breakdown per kategori (sama dengan FinanceDashboard::loadCategoryBreakdown)
        $categories = $eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->withCount(['registrations as total_paid' => fn($q) => $q->where('payment_status', 'paid')])
            ->withCount(['registrations as total_pending' => fn($q) => $q->where('payment_status', 'pending_verification')])
            ->withCount(['registrations as total_unpaid' => fn($q) => $q->where('payment_status', 'unpaid')])
            ->get();

        $categoryBreakdown = [];
        foreach ($categories as $cat) {
            $paidRevenue = (float) Registration::where('competition_category_id', $cat->id)
                ->where('payment_status', 'paid')
                ->sum('total_fee');

            $potentialRevenue = $cat->registration_fee
                ? (float) $cat->registration_fee * Registration::where('competition_category_id', $cat->id)
                    ->whereIn('payment_status', ['unpaid', 'pending_verification'])
                    ->count()
                : 0;

            $categoryBreakdown[] = [
                'id' => $cat->id,
                'name' => $cat->full_name,
                'fee' => $cat->registration_fee,
                'paid_count' => (int) $cat->total_paid,
                'pending_count' => (int) $cat->total_pending,
                'unpaid_count' => (int) $cat->total_unpaid,
                'paid_revenue' => $paidRevenue,
                'potential_revenue' => $potentialRevenue,
                'total_registrations' => (int) $cat->total_paid + (int) $cat->total_pending + (int) $cat->total_unpaid,
            ];
        }

        // Detail pembayaran semua registrasi
        $paymentDetails = Registration::with('competitionCategory')
            ->where('eventner_id', $eventnerId)
            ->whereIn('payment_status', ['paid', 'unpaid', 'pending_verification'])
            ->orderByRaw("CASE payment_status WHEN 'pending_verification' THEN 0 WHEN 'unpaid' THEN 1 ELSE 2 END")
            ->orderBy('updated_at', 'desc')
            ->get();

        $data = [
            'eventner' => $eventner,
            'totalRevenue' => $totalRevenue,
            'feeRevenue' => $feeRevenue,
            'voteRevenue' => $voteRevenue,
            'ticketRevenue' => $ticketRevenue,
            'pendingVerificationCount' => $pendingVerificationCount,
            'categoryBreakdown' => $categoryBreakdown,
            'paymentDetails' => $paymentDetails,
        ];

        $pdf = Pdf::loadView('eventner.finance.pdf_recap', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', '10mm')
            ->setOption('margin-bottom', '10mm')
            ->setOption('margin-left', '5mm')
            ->setOption('margin-right', '5mm');

        $filename = 'Laporan_Keuangan_' . str_replace(['/', '\\'], '-', $eventner->nama_event) . '.pdf';
        return $pdf->download($filename);
    }
}
