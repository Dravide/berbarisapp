<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\VoteTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoteTransactionController extends Controller
{
    public function downloadCsv(Request $request)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $eventnerId = $eventner->id;

        // Ambil filter query parameter yang sama dengan Livewire component
        $search = $request->query('search', '');
        $filterStatus = $request->query('filterStatus', '');
        $filterRegistration = $request->query('filterRegistration', '');
        $dateFrom = $request->query('dateFrom', '');
        $dateTo = $request->query('dateTo', '');

        // Query transaksi
        $query = VoteTransaction::query()
            ->where('eventner_id', $eventnerId)
            ->with(['registration:id,nama_sekolah,npsn,competition_category_id', 'registration.competitionCategory:id,name']);

        // Filter search
        if ($search !== '') {
            $needle = '%' . $search . '%';
            $query->where(function ($w) use ($needle) {
                $w->where('voter_name', 'like', $needle)
                    ->orWhere('voter_email', 'like', $needle)
                    ->orWhere('autogopay_transaction_id', 'like', $needle);
            });
        }

        // Filter status
        if ($filterStatus !== '') {
            $query->where('status', $filterStatus);
        }

        // Filter kontingen
        if ($filterRegistration !== '') {
            $query->where('registration_id', $filterRegistration);
        }

        // Filter tanggal
        if ($dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $transactions = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $fileName = 'laporan-transaksi-voting-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($transactions) {
            $file = fopen('php://output', 'w');

            // Tambahkan BOM untuk UTF-8 (agar Excel mendeteksi format dengan benar)
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header kolom
            fputcsv($file, [
                'No',
                'ID Transaksi AutoGopay',
                'Nama Voter',
                'Email Voter',
                'Kontingen / Sekolah',
                'Kategori Lomba',
                'NPSN',
                'Jumlah Vote',
                'Nominal Bayar (IDR)',
                'Status',
                'Waktu Dibuat',
                'Waktu Dibayar',
            ]);

            foreach ($transactions as $index => $trx) {
                fputcsv($file, [
                    $index + 1,
                    $trx->autogopay_transaction_id ?: '-',
                    $trx->voter_name ?: 'Guest / Anonim',
                    $trx->voter_email ?: '-',
                    $trx->registration->nama_sekolah ?? '-',
                    $trx->registration->competitionCategory->name ?? '-',
                    $trx->registration->npsn ?? '-',
                    $trx->votes_earned,
                    (int) $trx->amount,
                    $trx->status,
                    $trx->created_at ? $trx->created_at->format('Y-m-d H:i:s') : '-',
                    ($trx->status === 'PAID' && $trx->paid_at) ? $trx->paid_at->format('Y-m-d H:i:s') : '-',
                ]);
            }

            fclose($file);
        }, $fileName, $headers);
    }
}
