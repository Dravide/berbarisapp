<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Livewire\Eventner\VoteComment\Index as VoteCommentIndex;
use App\Models\VoteTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoteCommentController extends Controller
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
        $filterRegistration = $request->query('filterRegistration', '');
        $filterTier = $request->query('filterTier', '');
        $dateFrom = $request->query('dateFrom', '');
        $dateTo = $request->query('dateTo', '');

        // Query komentar: transaksi PAID dengan isi komentar
        $query = VoteTransaction::query()
            ->where('eventner_id', $eventnerId)
            ->where('status', 'PAID')
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->with(['registration:id,nama_sekolah,label_pasukan,npsn,competition_category_id', 'registration.competitionCategory:id,name']);

        // Filter search
        if ($search !== '') {
            $needle = '%' . $search . '%';
            $query->where(function ($w) use ($needle) {
                $w->where('voter_name', 'like', $needle)
                    ->orWhere('comment', 'like', $needle);
            });
        }

        // Filter kontingen
        if ($filterRegistration !== '') {
            $query->where('registration_id', $filterRegistration);
        }

        // Filter tier
        if ($filterTier !== '' && isset(VoteCommentIndex::TIERS[$filterTier])) {
            $query->where('votes_earned', '>=', VoteCommentIndex::TIERS[$filterTier]);
        }

        // Filter tanggal
        if ($dateFrom !== '') {
            $query->whereDate('paid_at', '>=', $dateFrom);
        }
        if ($dateTo !== '') {
            $query->whereDate('paid_at', '<=', $dateTo);
        }

        $comments = $query->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get();

        $fileName = 'laporan-komentar-voting-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($comments) {
            $file = fopen('php://output', 'w');

            // Tambahkan BOM untuk UTF-8 (agar Excel mendeteksi format dengan benar)
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header kolom
            fputcsv($file, [
                'No',
                'Nama Voter',
                'Email Voter',
                'Komentar',
                'Kontingen / Sekolah',
                'Kategori Lomba',
                'NPSN',
                'Jumlah Vote',
                'Tier',
                'Waktu Dibayar',
            ]);

            foreach ($comments as $index => $comment) {
                $tier = VoteCommentIndex::tierOf((int) $comment->votes_earned);

                fputcsv($file, [
                    $index + 1,
                    $comment->voter_name ?: 'Guest / Anonim',
                    $comment->voter_email ?: '-',
                    $comment->comment,
                    $comment->registration->display_name ?? '-',
                    $comment->registration->competitionCategory->name ?? '-',
                    $comment->registration->npsn ?? '-',
                    $comment->votes_earned,
                    $tier ? ucfirst($tier) : '-',
                    ($comment->status === 'PAID' && $comment->paid_at) ? $comment->paid_at->format('Y-m-d H:i:s') : '-',
                ]);
            }

            fclose($file);
        }, $fileName, $headers);
    }
}
