<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Eventner;
use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class TicketPdfController extends Controller
{
    public function download($slug = null, $orderCode = null)
    {
        $resolved = app()->bound('current_eventner') ? app('current_eventner') : null;
        $eventner = $resolved ?: Eventner::approved()->where('slug', $slug)->firstOrFail();

        $ticket = Ticket::where('eventner_id', $eventner->id)
            ->where('order_code', strtoupper(trim($orderCode)))
            ->whereIn('status', ['PAID', 'CHECKED_IN'])
            ->firstOrFail();

        // Terbitkan QR di sini juga. QR murni lokal dari order_code, jadi tiket
        // lama yang terlanjur PAID tanpa file tetap bisa diunduh sebagai PDF.
        if (!$ticket->qr_code_path || !Storage::disk('public')->exists($ticket->qr_code_path)) {
            $ticket->qr_code_path = $ticket->generateEntryQr();
            $ticket->save();
        }

        $pdf = Pdf::loadView('livewire.public.partials.pdf.ticket', [
            'eventner' => $eventner,
            'ticket' => $ticket,
            'qrPath' => public_path('storage/' . $ticket->qr_code_path),
        ])
            ->setPaper('A5', 'portrait')
            ->setOption('margin-top', '10mm')
            ->setOption('margin-bottom', '10mm')
            ->setOption('margin-left', '10mm')
            ->setOption('margin-right', '10mm');

        return $pdf->download('tiket-' . $ticket->order_code . '.pdf');
    }
}
