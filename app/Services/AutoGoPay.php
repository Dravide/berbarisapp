<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AutoGoPay
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.autogopay.api_key');
        $this->baseUrl = config('services.autogopay.base_url');
    }

    /**
     * Generate QRIS dinamis dengan nominal tertentu.
     *
     * @return array{success: bool, data: array{transaction_id: string, order_id: string, amount: int, transaction_status: string, qr_string: string, qr_url: string, transaction_time: string, expiry_time: string}}
     */
    public function generateQris(float $amount): array
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
     * Cek status transaksi.
     *
     * @return array{success: bool, data: array{transaction_id: string, transaction_status: string, transaction_time: string}}
     */
    public function checkStatus(string $transactionId): array
    {
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
     * Cancel transaksi yang masih pending.
     */
    public function cancelTransaction(string $transactionId): array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(10)
            ->connectTimeout(5)
            ->post("{$this->baseUrl}/qris/cancel", [
                'transaction_id' => $transactionId,
            ]);

        return $response->json();
    }

    /**
     * Verifikasi webhook signature (HMAC SHA256).
     */
    public function verifySignature(string $payload, string $signature): bool
    {
        $expected = hash_hmac('sha256', $payload, $this->apiKey);

        return hash_equals($expected, $signature);
    }
}
