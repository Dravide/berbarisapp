<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\VoteTransaction;

/**
 * Ekspor CSV tingkat platform untuk admin — pola sama dengan
 * Eventner\TicketController::downloadCsv (stream + BOM UTF-8 agar
 * Excel membaca dengan benar).
 */
class ExportController extends Controller
{
    public function eventners()
    {
        $rows = Eventner::with('user')->orderByDesc('id')->get();

        $fileName = 'daftar-eventner-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($rows) {
            $file = fopen('php://output', 'w');

            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'No',
                'Nama Event',
                'Penyelenggara',
                'Email Akun',
                'Status',
                'Paket',
                'Sudah Bayar',
                'Tanggal Event',
                'Lokasi',
                'Terdaftar',
            ]);

            foreach ($rows as $index => $e) {
                fputcsv($file, [
                    $index + 1,
                    $e->nama_event,
                    $e->diselenggarakan_oleh,
                    $e->user?->email ?? '-',
                    $e->status,
                    $e->saasPlan?->name ?? ($e->plan === 'paid' ? 'Berbayar' : 'Gratis'),
                    $e->registration_paid_at ? 'Ya' : 'Belum',
                    $e->tanggal,
                    $e->lokasi,
                    $e->created_at ? $e->created_at->format('Y-m-d H:i:s') : '-',
                ]);
            }

            fclose($file);
        }, $fileName, $headers);
    }

    public function registrations()
    {
        $rows = Registration::query()
            ->with(['eventner:id,nama_event', 'competitionCategory:id,name'])
            ->orderByDesc('id')
            ->get();

        $fileName = 'daftar-pendaftar-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($rows) {
            $file = fopen('php://output', 'w');

            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'No',
                'Event',
                'Kategori Lomba',
                'Nama Sekolah',
                'NPSN',
                'Label Pasukan',
                'Pelatih',
                'No. HP',
                'Email Sekolah',
                'Status Berkas',
                'Status Bayar',
                'Waktu Daftar',
            ]);

            foreach ($rows as $index => $r) {
                fputcsv($file, [
                    $index + 1,
                    $r->eventner?->nama_event ?? '-',
                    $r->competitionCategory?->name ?? '-',
                    $r->nama_sekolah,
                    $r->npsn ?? '-',
                    $r->label_pasukan ?? '-',
                    $r->nama_pelatih,
                    $r->no_hp,
                    $r->school_email ?? '-',
                    $r->status_berkas,
                    $r->payment_status,
                    $r->created_at ? $r->created_at->format('Y-m-d H:i:s') : '-',
                ]);
            }

            fclose($file);
        }, $fileName, $headers);
    }

    public function transactions()
    {
        // Gabungan dua sumber uang: transaksi voting dan tiket.
        $fileName = 'transaksi-platform-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () {
            $file = fopen('php://output', 'w');

            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'No',
                'Jenis',
                'Event',
                'Referensi',
                'Pihak',
                'Jumlah',
                'Nominal (IDR)',
                'Status',
                'Waktu Bayar',
            ]);

            $no = 1;

            VoteTransaction::query()
                ->with('eventner:id,nama_event')
                ->orderByDesc('id')
                ->each(function (VoteTransaction $t) use ($file, &$no) {
                    fputcsv($file, [
                        $no++,
                        'Voting',
                        $t->eventner?->nama_event ?? '-',
                        $t->autogopay_transaction_id ?: '-',
                        $t->voter_name ?: 'Guest / Anonim',
                        $t->votes_earned . ' vote',
                        (int) $t->amount,
                        $t->status,
                        $t->paid_at ? $t->paid_at->format('Y-m-d H:i:s') : '-',
                    ]);
                });

            Ticket::query()
                ->with('eventner:id,nama_event')
                ->orderByDesc('id')
                ->each(function (Ticket $t) use ($file, &$no) {
                    fputcsv($file, [
                        $no++,
                        'Tiket',
                        $t->eventner?->nama_event ?? '-',
                        $t->order_code,
                        $t->buyer_name,
                        $t->quantity . ' tiket',
                        (int) $t->total_amount,
                        $t->status,
                        $t->paid_at ? $t->paid_at->format('Y-m-d H:i:s') : '-',
                    ]);
                });

            fclose($file);
        }, $fileName, $headers);
    }

    public function errorLogs()
    {
        $fileName = 'log-error-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () {
            $file = fopen('php://output', 'w');

            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'No',
                'Kode',
                'Kejadian',
                'Terakhir',
                'HTTP',
                'Exception',
                'Pesan',
                'URL',
                'Method',
                'User',
                'Event',
                'Status Selesai',
                'Diselesaikan Oleh',
            ]);

            $no = 1;

            \App\Models\ErrorLog::query()
                ->with(['user:id,name,email', 'eventner:id,nama_event', 'resolver:id,name'])
                ->orderByDesc('last_seen_at')
                ->each(function (\App\Models\ErrorLog $log) use ($file, &$no) {
                    fputcsv($file, [
                        $no++,
                        $log->code,
                        $log->occurrences ?? 1,
                        $log->last_seen_at ? $log->last_seen_at->format('Y-m-d H:i:s') : '-',
                        $log->http_status ?? '-',
                        $log->exception_class,
                        $log->message,
                        $log->url ?? '-',
                        $log->method ?? '-',
                        $log->user ? ($log->user->name . ' <' . $log->user->email . '>') : '-',
                        $log->eventner?->nama_event ?? '-',
                        $log->resolved_at ? 'Selesai' : 'Belum',
                        $log->resolver?->name ?? '-',
                    ]);
                });

            fclose($file);
        }, $fileName, $headers);
    }
}
