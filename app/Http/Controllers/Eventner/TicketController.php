<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
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
        $dateFrom = $request->query('dateFrom', '');
        $dateTo = $request->query('dateTo', '');

        // Query tiket
        $query = Ticket::query()->where('eventner_id', $eventnerId);

        // Filter search
        if ($search !== '') {
            $needle = '%' . $search . '%';
            $query->where(function ($w) use ($needle) {
                $w->where('order_code', 'like', $needle)
                    ->orWhere('buyer_name', 'like', $needle)
                    ->orWhere('buyer_email', 'like', $needle)
                    ->orWhere('autogopay_transaction_id', 'like', $needle);
            });
        }

        // Filter status
        if ($filterStatus !== '') {
            $query->where('status', $filterStatus);
        }

        // Filter tanggal
        if ($dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $tickets = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $fileName = 'laporan-tiket-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($tickets) {
            $file = fopen('php://output', 'w');

            // Tambahkan BOM untuk UTF-8 (agar Excel mendeteksi format dengan benar)
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header kolom
            fputcsv($file, [
                'No',
                'Kode Order',
                'Nama Pembeli',
                'Email Pembeli',
                'Jumlah Tiket',
                'Total Bayar (IDR)',
                'ID Transaksi AutoGopay',
                'Status',
                'Waktu Dibuat',
                'Waktu Dibayar',
                'Waktu Check-in',
            ]);

            foreach ($tickets as $index => $ticket) {
                fputcsv($file, [
                    $index + 1,
                    $ticket->order_code,
                    $ticket->buyer_name,
                    $ticket->buyer_email ?: '-',
                    $ticket->quantity,
                    (int) $ticket->total_amount,
                    $ticket->autogopay_transaction_id ?: '-',
                    $ticket->status,
                    $ticket->created_at ? $ticket->created_at->format('Y-m-d H:i:s') : '-',
                    ($ticket->paid_at) ? $ticket->paid_at->format('Y-m-d H:i:s') : '-',
                    ($ticket->checked_in_at) ? $ticket->checked_in_at->format('Y-m-d H:i:s') : '-',
                ]);
            }

            fclose($file);
        }, $fileName, $headers);
    }
}
