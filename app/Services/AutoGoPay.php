<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gateway pembayaran QRIS via platform AutoGoPay.
 *
 * Dua gateway tersedia (dipilih admin di Pengaturan Situs):
 *  - gopay    : POST /qris/generate    — punya webhook + cek status per-transaksi.
 *  - instaqris: POST /instaqris/create — TIDAK punya webhook & TIDAK punya cek
 *               status per-transaksi. Konfirmasi hanya lewat daftar mutasi harian
 *               GET /instaqris/transactions (cocokkan field `bill`).
 *
 * InstaQRIS mengembalikan bentuk data yang sudah dinormalisasi ke bentuk GoPay,
 * jadi pemanggil (Livewire/Job/Webhook) tidak perlu tahu gateway mana yang aktif.
 * Untuk InstaQRIS, bill_number disimpan di kolom autogopay_transaction_id —
 * kolom itu satu-satunya identifier yang dibutuhkan jalur polling.
 */
class AutoGoPay
{
    public const GATEWAY_GOPAY = 'gopay';
    public const GATEWAY_INSTAQRIS = 'instaqris';

    /** Batas nominal per QR InstaQRIS (Rp 10 juta). */
    public const INSTAQRIS_MAX_AMOUNT = 10_000_000;

    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.autogopay.api_key');
        $this->baseUrl = config('services.autogopay.base_url');
    }

    /**
     * Gateway aktif sesuai pengaturan admin. Default GoPay supaya perilaku
     * tidak berubah sampai admin sengaja memindahkannya.
     */
    public static function activeGateway(): string
    {
        $gateway = Setting::get('payment_gateway', self::GATEWAY_GOPAY);

        return in_array($gateway, [self::GATEWAY_GOPAY, self::GATEWAY_INSTAQRIS], true)
            ? $gateway
            : self::GATEWAY_GOPAY;
    }

    public static function isInstaQris(): bool
    {
        return self::activeGateway() === self::GATEWAY_INSTAQRIS;
    }

    /**
     * Generate QRIS dinamis dengan nominal tertentu, lewat gateway yang aktif.
     *
     * @return array{success: bool, data: array{transaction_id: string, order_id: string, amount: int, transaction_status: string, qr_string: string|null, qr_url: string, transaction_time: string, expiry_time: string|int}}
     */
    public function generateQris(float $amount): array
    {
        return self::isInstaQris()
            ? $this->generateInstaQris($amount)
            : $this->generateGoPayQris($amount);
    }

    /**
     * Cek status transaksi (satu transaksi).
     *
     * @return array{success: bool, data: array{transaction_id: string, transaction_status: string}}
     */
    public function checkStatus(string $transactionId): array
    {
        if (self::isInstaQris()) {
            return [
                'success' => true,
                'data' => [
                    'transaction_id' => $transactionId,
                    'transaction_status' => $this->instaQrisStatus($transactionId) ?? '',
                ],
            ];
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(10)
            ->connectTimeout(5)
            ->post("{$this->baseUrl}/qris/status", [
                'transaction_id' => $transactionId,
            ]);

        if (!$response->successful()) {
            Log::error('AutoGoPay checkStatus failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'transaction_id' => $transactionId,
            ]);
            $response->throw();
        }

        return $response->json();
    }

    /**
     * Cek status banyak transaksi sekaligus.
     * Mengembalikan map [transaction_id => transaction_status].
     * Transaksi gagal dicek tidak dimasukkan ke map.
     *
     * @param  array<int|string, string>  $transactionIds
     * @return array<string, string>
     */
    public function checkStatusMany(array $transactionIds): array
    {
        if (empty($transactionIds)) {
            return [];
        }

        // InstaQRIS: satu panggilan daftar mutasi mencakup semua bill sekaligus.
        if (self::isInstaQris()) {
            $mutations = $this->instaQrisMutations();

            $statuses = [];
            foreach ($transactionIds as $id) {
                $id = (string) $id;
                if (isset($mutations[$id])) {
                    $statuses[$id] = $mutations[$id];
                }
            }

            return $statuses;
        }

        // Key pool = transaction_id supaya mudah dipetakan balik
        $keyed = [];
        foreach ($transactionIds as $id) {
            $keyed[$id] = $id;
        }

        $responses = Http::pool(function ($pool) use ($keyed) {
            foreach ($keyed as $id => $txnId) {
                $pool->as((string) $txnId)
                    ->withToken($this->apiKey)
                    ->timeout(10)
                    ->connectTimeout(5)
                    ->post("{$this->baseUrl}/qris/status", [
                        'transaction_id' => $txnId,
                    ]);
            }
        });

        $statuses = [];
        foreach ($responses as $txnId => $response) {
            if (!$response instanceof \Illuminate\Http\Client\Response) {
                Log::error('AutoGoPay checkStatusMany: response tidak valid', ['transaction_id' => $txnId]);
                continue;
            }

            if (!$response->successful()) {
                Log::error('AutoGoPay checkStatusMany failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'transaction_id' => $txnId,
                ]);
                continue;
            }

            $json = $response->json();
            $status = $json['data']['transaction_status'] ?? null;
            if ($status !== null) {
                $statuses[(string) $txnId] = $status;
            }
        }

        return $statuses;
    }

    /**
     * Cancel transaksi yang masih pending. Hanya gateway GoPay yang punya endpoint ini;
     * InstaQRIS tidak punya cancel — bill dibiarkan kadaluarsa sendiri (15 menit).
     */
    public function cancelTransaction(string $transactionId): array
    {
        if (self::isInstaQris()) {
            return ['success' => false, 'message' => 'InstaQRIS tidak menyediakan endpoint cancel.'];
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(10)
            ->connectTimeout(5)
            ->post("{$this->baseUrl}/qris/cancel", [
                'transaction_id' => $transactionId,
            ]);

        return $response->json();
    }

    /**
     * Map status gateway (settlement/expire/cancel) ke status internal
     * yang valid di enum vote_transactions/tickets (PENDING/PAID/EXPIRED/FAILED).
     * Status lain return null (biarkan baris tidak berubah).
     */
    public static function mapStatus(?string $gatewayStatus): ?string
    {
        return match ($gatewayStatus) {
            'settlement' => 'PAID',
            'expire' => 'EXPIRED',
            'cancel' => 'FAILED',
            default => null,
        };
    }

    /**
     * Verifikasi webhook signature (HMAC SHA256). Hanya dipakai gateway GoPay —
     * InstaQRIS tidak mengirim webhook sama sekali.
     */
    public function verifySignature(string $payload, string $signature): bool
    {
        $expected = hash_hmac('sha256', $payload, $this->apiKey);

        return hash_equals($expected, $signature);
    }

    /**
     * GoPay: QRIS dinamis + auto-poller di sisi gateway + webhook.
     */
    private function generateGoPayQris(float $amount): array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(15)
            ->connectTimeout(5)
            ->retry(2, 500)
            ->post("{$this->baseUrl}/qris/generate", [
                'amount' => (int) $amount,
            ]);

        if (!$response->successful()) {
            Log::error('AutoGoPay generateQris failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'amount' => $amount,
            ]);
            $response->throw();
        }

        return $response->json();
    }

    /**
     * InstaQRIS (PT Bank Mega): QRIS dinamis, tanpa webhook & tanpa cek status
     * per-transaksi. Respons dinormalisasi ke bentuk GoPay.
     */
    private function generateInstaQris(float $amount): array
    {
        if ($amount > self::INSTAQRIS_MAX_AMOUNT) {
            throw new \InvalidArgumentException(
                'InstaQRIS maksimal Rp ' . number_format(self::INSTAQRIS_MAX_AMOUNT, 0, ',', '.')
                . ' per transaksi. Nominal: Rp ' . number_format($amount, 0, ',', '.') . '.'
            );
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(15)
            ->connectTimeout(5)
            ->retry(2, 500)
            ->post("{$this->baseUrl}/instaqris/create", [
                'amount' => (int) $amount,
            ]);

        if (!$response->successful()) {
            Log::error('AutoGoPay InstaQRIS create failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'amount' => $amount,
            ]);
            $response->throw();
        }

        $json = $response->json();

        if (!($json['success'] ?? false) || empty($json['data']['bill_number'])) {
            Log::error('AutoGoPay InstaQRIS create ditolak', [
                'body' => $response->body(),
                'amount' => $amount,
            ]);
            throw new \RuntimeException($json['message'] ?? 'Gagal membuat QRIS InstaQRIS.');
        }

        $data = $json['data'];

        // bill_number jadi identifier tunggal: dipakai untuk cek status dan
        // disimpan pemanggil di kolom autogopay_transaction_id.
        // expiry_time disamakan bentuknya dgn GoPay, yaitu datetime absolut —
        // timer di halaman bayar mem-parse-nya sebagai absolut.
        return [
            'success' => true,
            'data' => [
                'transaction_id' => (string) $data['bill_number'],
                'order_id' => (string) $data['bill_number'],
                'amount' => $data['amount'] ?? (int) $amount,
                'transaction_status' => 'pending',
                'qr_string' => null,
                'qr_url' => $data['qr_url'],
                'transaction_time' => now()->format('Y-m-d H:i:s'),
                'expiry_time' => now()->addSeconds($data['expires_in'] ?? 900)->format('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Status satu bill InstaQRIS, atau null kalau belum ada mutasi masuk.
     */
    private function instaQrisStatus(string $billNumber): ?string
    {
        return $this->instaQrisMutations()[(string) $billNumber] ?? null;
    }

    /**
     * Peta [bill_number => 'settlement'] dari mutasi masuk hari ini.
     *
     * ponytail: dokumentasi hanya memberi contoh `bill` 8 digit sementara
     * /instaqris/create mengembalikan bill_number 16 digit, jadi pencocokan
     * persis belum bisa diverifikasi langsung (endpoint mutasi sedang 502).
     * Kalau bill tidak pernah cocok, cek log 'InstaQRIS mutations tidak cocok'
     * dan sesuaikan field pencocokannya.
     *
     * @return array<string, string>
     */
    private function instaQrisMutations(): array
    {
        $today = now()->toDateString();

        // Cache pendek: halaman bayar poll tiap 5 detik, job sinkron tiap 5 menit,
        // dan tombol Sinkron manual — semuanya membaca daftar mutasi yang sama.
        // Tanpa cache, satu QR bisa memicu ratusan request ke endpoint ini.
        // Nilai kosong (termasuk saat endpoint 502) ikut di-cache supaya tidak
        // menghajar endpoint yang sedang bermasalah.
        return \Illuminate\Support\Facades\Cache::remember(
            "instaqris-mutations:{$today}",
            15,
            fn () => $this->fetchInstaQrisMutations()
        );
    }

    /**
     * @return array<string, string>
     */
    private function fetchInstaQrisMutations(): array
    {
        $today = now()->toDateString();

        $response = Http::withToken($this->apiKey)
            ->timeout(15)
            ->connectTimeout(5)
            ->get("{$this->baseUrl}/instaqris/transactions", [
                'start_date' => $today,
                'end_date' => $today,
            ]);

        if (!$response->successful()) {
            Log::warning('AutoGoPay InstaQRIS mutations gagal diambil', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        }

        $rows = $response->json('data.transactions') ?? [];

        if (empty($rows)) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            $bill = (string) ($row['bill'] ?? '');
            $type = strtoupper((string) ($row['type'] ?? ''));
            $rc = (string) ($row['rc'] ?? '');

            // Hanya mutasi masuk yang sukses.
            if ($bill === '' || $type !== 'IN' || $rc !== '00') {
                continue;
            }

            $map[$bill] = 'settlement';
        }

        Log::debug('InstaQRIS mutations', ['count' => count($rows), 'matched' => count($map)]);

        return $map;
    }
}
