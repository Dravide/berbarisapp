<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\RegistrationPayment;
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

        // Ringkasan pendapatan (sama dengan FinanceDashboard::loadData).
        //
        // Uang pendaftaran dihitung dari `sqlDibayar()`, bukan dari
        // `SUM(total_fee) WHERE payment_status = 'paid'`: begitu DP ada, cara
        // lama menghitung pasukan yang baru menyetor 30% sebagai 100% dan
        // membuang DP itu dari laporan sama sekali.
        $feeRevenue = (float) (Registration::where('eventner_id', $eventnerId)
            ->selectRaw('COALESCE(SUM(' . Registration::sqlDibayar() . '), 0) as total')
            ->first()?->total ?? 0);

        // Tagihan yang pernah terbit = snapshot harga saat booking.
        $totalTagihan = (float) Registration::where('eventner_id', $eventnerId)
            ->where('total_fee', '>', 0)
            ->sum('total_fee');

        $piutang = max(0, $totalTagihan - $feeRevenue);

        $voteRevenue = (float) VoteTransaction::where('eventner_id', $eventnerId)
            ->where('status', 'PAID')
            ->sum('amount');

        $ticketRevenue = (float) Ticket::where('eventner_id', $eventnerId)
            ->whereIn('status', ['PAID', 'CHECKED_IN'])
            ->sum('total_amount');

        $totalRevenue = $feeRevenue + $voteRevenue + $ticketRevenue;

        // Antrean verifikasi per BUKTI, bukan per pendaftaran — satu pasukan
        // bisa mengirim DP lalu pelunasan dan keduanya diverifikasi terpisah.
        $pendingVerificationCount = RegistrationPayment::where('status', RegistrationPayment::STATUS_PENDING)
            ->whereHas('registration', fn ($q) => $q->where('eventner_id', $eventnerId))
            ->count();

        // Breakdown per kategori (sama dengan FinanceDashboard::loadCategoryBreakdown)
        $categories = $eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->withCount(['registrations as total_paid' => fn($q) => $q->where('payment_status', 'paid')])
            ->withCount(['registrations as total_dp' => fn($q) => $q->where('payment_status', 'dp_paid')])
            ->withCount(['registrations as total_pending' => fn($q) => $q->where('payment_status', 'pending_verification')])
            ->withCount(['registrations as total_unpaid' => fn($q) => $q->where('payment_status', 'unpaid')])
            ->get();

        $categoryBreakdown = [];
        foreach ($categories as $cat) {
            $paidRevenue = (float) (Registration::where('competition_category_id', $cat->id)
                ->selectRaw('COALESCE(SUM(' . Registration::sqlDibayar() . '), 0) as total')
                ->first()?->total ?? 0);

            $sisaTagihan = (float) (Registration::where('competition_category_id', $cat->id)
                ->where('total_fee', '>', 0)
                ->selectRaw('COALESCE(SUM(total_fee - ' . Registration::sqlDibayar() . '), 0) as sisa')
                ->first()?->sisa ?? 0);

            // Potensi = harga yang berlaku hari ini × pendaftar yang belum
            // lunas. Lewat resolver, sama seperti FinanceDashboard: kalau
            // penagih memakai gelombang dan rekap ini memakai kolom mentah,
            // angka potensi yang tampil bukan angka yang akan ditagih.
            $biayaKini = $cat->biayaPada();

            $potentialRevenue = $biayaKini
                ? (float) $biayaKini * Registration::where('competition_category_id', $cat->id)
                    ->whereIn('payment_status', ['unpaid', 'pending_verification'])
                    ->count()
                : 0;

            $categoryBreakdown[] = [
                'id' => $cat->id,
                'name' => $cat->full_name,
                'fee' => $biayaKini,
                'paid_count' => (int) $cat->total_paid,
                'dp_count' => (int) $cat->total_dp,
                'pending_count' => (int) $cat->total_pending,
                'unpaid_count' => (int) $cat->total_unpaid,
                'paid_revenue' => $paidRevenue,
                'sisa_tagihan' => max(0, $sisaTagihan),
                'potential_revenue' => $potentialRevenue,
                'total_registrations' => (int) $cat->total_paid + (int) $cat->total_dp
                    + (int) $cat->total_pending + (int) $cat->total_unpaid,
            ];
        }

        // Detail pembayaran semua registrasi — urut kategori lomba, lalu status, lalu nama sekolah
        $paymentDetails = Registration::with('competitionCategory')
            ->where('registrations.eventner_id', $eventnerId)
            ->whereIn('registrations.payment_status', ['paid', 'dp_paid', 'unpaid', 'pending_verification'])
            ->join('competition_categories', 'registrations.competition_category_id', '=', 'competition_categories.id')
            ->orderBy('competition_categories.sort_order')
            ->orderBy('competition_categories.id')
            ->orderByRaw("CASE registrations.payment_status WHEN 'pending_verification' THEN 0 WHEN 'unpaid' THEN 1 WHEN 'dp_paid' THEN 2 ELSE 3 END")
            ->orderBy('registrations.nama_sekolah')
            ->select('registrations.*')
            ->get();

        $data = [
            'eventner' => $eventner,
            'totalRevenue' => $totalRevenue,
            'feeRevenue' => $feeRevenue,
            'totalTagihan' => $totalTagihan,
            'piutang' => $piutang,
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
