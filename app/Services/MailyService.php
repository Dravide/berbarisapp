<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MailyService
{
    private string $apiKey;
    private string $baseUrl = 'https://maily.id/api/v1/emails/send';

    public function __construct()
    {
        $this->apiKey = 'ml_live_C-pV-esWoQ6rU3zOHbkNQ_VZTFip8RJF';
    }

    public function sendBookingConfirmation(
        string $toEmail,
        string $schoolName,
        string $eventName,
        string $magicLink,
        array  $categories,
        string $npsn,
        string $noHp
    ): bool {
        $categoryList = implode('', array_map(fn($cat) => "
            <tr>
                <td style='padding:8px 12px;border-bottom:1px solid #f0f0f0;font-weight:600;'>{$cat['name']}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #f0f0f0;text-align:center;'>{$cat['teams']}</td>
            </tr>
        ", $categories));

        $html = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);'>
            <div style='background:linear-gradient(135deg,#0072FF,#0046b3);padding:32px 24px;text-align:center;'>
                <h1 style='color:#fff;margin:0;font-size:22px;font-weight:700;'>Booking Berhasil!</h1>
                <p style='color:rgba(255,255,255,0.85);margin:8px 0 0;font-size:14px;'>{$eventName}</p>
            </div>
            <div style='padding:28px 24px;'>
                <p style='color:#374151;font-size:15px;line-height:1.6;margin:0 0 16px;'>
                    Yth. <strong>{$schoolName}</strong>,
                </p>
                <p style='color:#6b7280;font-size:14px;line-height:1.6;margin:0 0 20px;'>
                    Booking pendaftaran Anda telah berhasil dicatat. Berikut ringkasan data pendaftaran Anda:
                </p>

                <div style='background:#f8fafc;border-radius:10px;padding:16px;margin-bottom:20px;'>
                    <table style='width:100%;font-size:14px;color:#374151;'>
                        <tr><td style='padding:4px 0;color:#9ca3af;'>NPSN</td><td style='padding:4px 0;font-weight:600;text-align:right;'>{$npsn}</td></tr>
                        <tr><td style='padding:4px 0;color:#9ca3af;'>No HP</td><td style='padding:4px 0;font-weight:600;text-align:right;'>{$noHp}</td></tr>
                        <tr><td style='padding:4px 0;color:#9ca3af;'>Email</td><td style='padding:4px 0;font-weight:600;text-align:right;'>{$toEmail}</td></tr>
                    </table>
                </div>

                <h3 style='color:#111827;font-size:15px;margin:0 0 10px;font-weight:600;'>Kategori Terdaftar</h3>
                <table style='width:100%;border-collapse:collapse;margin-bottom:20px;font-size:14px;'>
                    <thead>
                        <tr style='background:#f8fafc;'>
                            <th style='padding:8px 12px;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;'>Kategori</th>
                            <th style='padding:8px 12px;text-align:center;font-weight:600;color:#374151;border-bottom:2px solid #e5e7eb;'>Jml Tim</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$categoryList}
                    </tbody>
                </table>

                <div style='background:rgba(0,114,255,0.06);border:1px solid rgba(0,114,255,0.15);border-radius:10px;padding:16px;margin-bottom:24px;'>
                    <p style='font-weight:600;color:#0072FF;margin:0 0 8px;font-size:14px;'>Langkah Selanjutnya:</p>
                    <ol style='color:#374151;font-size:13px;line-height:1.7;margin:0;padding-left:18px;'>
                        <li>Klik tombol <strong>Kelola Pendaftaran</strong> di bawah untuk masuk ke halaman manajemen pasukan.</li>
                        <li>Upload berkas yang diperlukan: logo sekolah, surat tugas, foto pelatih, dan foto peserta.</li>
                        <li>Isi data peserta (danton & anggota) secara lengkap.</li>
                        <li>Setelah semua data lengkap, klik <strong>Finalisasi & Kirim</strong> untuk mengirim data ke panitia.</li>
                    </ol>
                </div>

                <div style='text-align:center;margin-bottom:24px;'>
                    <a href='{$magicLink}' style='display:inline-block;background:linear-gradient(135deg,#0072FF,#0046b3);color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:10px;font-weight:700;font-size:15px;box-shadow:0 4px 14px rgba(0,114,255,0.3);'>
                        Kelola Pendaftaran
                    </a>
                    <p style='color:#9ca3af;font-size:12px;margin:10px 0 0;'>Simpan email ini untuk akses kapan saja ke halaman manajemen pasukan Anda.</p>
                </div>

                <div style='border-top:1px solid #f0f0f0;padding-top:16px;text-align:center;'>
                    <p style='color:#9ca3af;font-size:12px;margin:0;'>
                        Link di atas bersifat rahasia. Jangan bagikan kepada pihak yang tidak berkepentingan.
                    </p>
                </div>
            </div>
        </div>";

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, [
                'from' => 'noreply@berbaris.app',
                'to' => $toEmail,
                'subject' => "Booking {$eventName} - {$schoolName}",
                'html' => $html,
            ]);

            if ($response->successful()) {
                Log::info("Maily.id: Email sent to {$toEmail} for event {$eventName}");
                return true;
            }

            Log::error("Maily.id: Failed to send email to {$toEmail}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("Maily.id: Exception sending email to {$toEmail}", [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Kirim email notifikasi tiket ke buyer.
     * Idempoten: jika sudah pernah dikirim, skip.
     *
     * @return bool true jika email benar-benar terkirim
     */
    public function sendTicketConfirmation(Ticket $ticket): bool
    {
        $cacheKey = 'ticket:email_sent:' . $ticket->id;
        if (Cache::has($cacheKey)) {
            return false;
        }

        $eventner = $ticket->eventner;
        $eventName = $eventner->nama_event ?? 'Event';
        $url = route('event.ticket', ['slug' => $eventner->slug, 'confirmOrder' => $ticket->order_code]);

        // Embed QR inline sebagai data URI (kalau file ada)
        $qrSrc = '';
        if ($ticket->qr_code_path && Storage::disk('public')->exists($ticket->qr_code_path)) {
            $bin = Storage::disk('public')->get($ticket->qr_code_path);
            $qrSrc = 'data:image/png;base64,' . base64_encode($bin);
        }

        $total = number_format($ticket->total_amount, 0, ',', '.');
        $paidAt = optional($ticket->paid_at)->translatedFormat('d F Y, H:i') ?? '-';
        $pricePer = number_format($ticket->price_per_ticket, 0, ',', '.');
        $qty = (int) $ticket->quantity;
        $orderCode = htmlspecialchars($ticket->order_code, ENT_QUOTES);
        $buyerName = htmlspecialchars($ticket->buyer_name, ENT_QUOTES);

        $qrBlock = $qrSrc !== ''
            ? "<div style='text-align:center;margin:20px 0;'>
                    <img src='{$qrSrc}' alt='QR Code' width='220' height='220' style='display:inline-block;border:1px solid #e5e7eb;border-radius:12px;padding:12px;background:#fff;' />
                    <p style='color:#9ca3af;font-size:12px;margin:8px 0 0;'>Tunjukkan QR ini di gerbang event</p>
               </div>"
            : '';

        $html = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);'>
            <div style='background:linear-gradient(135deg,#0072FF,#0046b3);padding:32px 24px;text-align:center;'>
                <h1 style='color:#fff;margin:0;font-size:22px;font-weight:700;'>Tiket Anda Sudah Aktif!</h1>
                <p style='color:rgba(255,255,255,0.85);margin:8px 0 0;font-size:14px;'>{$eventName}</p>
            </div>
            <div style='padding:28px 24px;'>
                <p style='color:#374151;font-size:15px;line-height:1.6;margin:0 0 16px;'>
                    Yth. <strong>{$buyerName}</strong>,
                </p>
                <p style='color:#6b7280;font-size:14px;line-height:1.6;margin:0 0 20px;'>
                    Pembayaran tiket Anda telah berhasil. Berikut detail tiket Anda:
                </p>

                <div style='background:#f8fafc;border-radius:10px;padding:16px;margin-bottom:20px;'>
                    <table style='width:100%;font-size:14px;color:#374151;'>
                        <tr><td style='padding:6px 0;color:#9ca3af;'>Kode Order</td><td style='padding:6px 0;font-weight:600;text-align:right;font-family:monospace;'>{$orderCode}</td></tr>
                        <tr><td style='padding:6px 0;color:#9ca3af;'>Nama</td><td style='padding:6px 0;font-weight:600;text-align:right;'>{$buyerName}</td></tr>
                        <tr><td style='padding:6px 0;color:#9ca3af;'>Jumlah</td><td style='padding:6px 0;font-weight:600;text-align:right;'>{$qty} tiket</td></tr>
                        <tr><td style='padding:6px 0;color:#9ca3af;'>Harga / Tiket</td><td style='padding:6px 0;font-weight:600;text-align:right;'>Rp {$pricePer}</td></tr>
                        <tr><td style='padding:6px 0;color:#9ca3af;'>Total Bayar</td><td style='padding:6px 0;font-weight:600;text-align:right;'>Rp {$total}</td></tr>
                        <tr><td style='padding:6px 0;color:#9ca3af;'>Waktu Bayar</td><td style='padding:6px 0;font-weight:600;text-align:right;'>{$paidAt}</td></tr>
                    </table>
                </div>

                {$qrBlock}

                <div style='background:rgba(0,114,255,0.06);border:1px solid rgba(0,114,255,0.15);border-radius:10px;padding:16px;margin-bottom:24px;'>
                    <p style='font-weight:600;color:#0072FF;margin:0 0 8px;font-size:14px;'>Cara Masuk Event:</p>
                    <ol style='color:#374151;font-size:13px;line-height:1.7;margin:0;padding-left:18px;'>
                        <li>Simpan atau screenshot email ini.</li>
                        <li>Datang ke lokasi event pada hari H.</li>
                        <li>Tunjukkan QR Code di atas kepada panitia di gerbang.</li>
                        <li>Panitia akan scan QR dan memberikan gelang/tanda masuk.</li>
                    </ol>
                </div>

                <div style='text-align:center;margin-bottom:24px;'>
                    <a href='{$url}' style='display:inline-block;background:linear-gradient(135deg,#0072FF,#0046b3);color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:10px;font-weight:700;font-size:15px;box-shadow:0 4px 14px rgba(0,114,255,0.3);'>
                        Lihat Tiket
                    </a>
                    <p style='color:#9ca3af;font-size:12px;margin:10px 0 0;'>Akses ulang tiket Anda kapan saja melalui tombol di atas.</p>
                </div>

                <div style='border-top:1px solid #f0f0f0;padding-top:16px;text-align:center;'>
                    <p style='color:#9ca3af;font-size:12px;margin:0;'>
                        Jika Anda tidak merasa membeli tiket ini, abaikan email ini.
                    </p>
                </div>
            </div>
        </div>";

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, [
                'from' => 'noreply@berbaris.app',
                'to' => $ticket->buyer_email,
                'subject' => "Tiket {$eventName} - {$ticket->order_code}",
                'html' => $html,
            ]);

            if ($response->successful()) {
                Cache::forever($cacheKey, true);
                Log::info("Maily.id: Ticket email sent to {$ticket->buyer_email} for order {$ticket->order_code}");
                return true;
            }

            Log::error("Maily.id: Failed to send ticket email to {$ticket->buyer_email}", [
                'status' => $response->status(),
                'body' => $response->body(),
                'order' => $ticket->order_code,
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("Maily.id: Exception sending ticket email to {$ticket->buyer_email}", [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
