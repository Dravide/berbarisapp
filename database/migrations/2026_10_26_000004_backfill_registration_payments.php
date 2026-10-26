<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill: pendaftaran yang sudah lunas sebelum DP ada.
 *
 * Tanpa ini, setiap baris `payment_status = 'paid'` yang sudah ada akan
 * memiliki `amount_paid = 0`. Akibatnya `sisaTagihan()` menganggap mereka
 * masih berutang PENUH — piutang di dashboard membengkak palsu, dan kuitansi
 * yang tercetak ulang akan menyebut sisa tagihan padahal sudah lunas.
 *
 * Yang ditulis: satu baris `registration_payments` senilai `total_fee`,
 * ber-status verified, bertanggal `payment_verified_at` (bukan `now()` —
 * laporan 30 hari membaca tanggal itu). Baris yang `total_fee`-nya nol atau
 * kosong dilewati: gratis memang tidak pernah menyetor uang.
 *
 * Aman dijalankan dua kali: baris yang sudah punya catatan pembayaran
 * dilewati, jadi migrasi yang diulang tidak menggandakan uang.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('registrations')
            ->where('payment_status', 'paid')
            ->whereNotNull('total_fee')
            ->where('total_fee', '>', 0)
            ->orderBy('id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $reg) {
                    $sudahAda = DB::table('registration_payments')
                        ->where('registration_id', $reg->id)
                        ->exists();

                    if ($sudahAda) {
                        continue;
                    }

                    $kapan = $reg->payment_verified_at ?: ($reg->updated_at ?: now());

                    DB::table('registration_payments')->insert([
                        'registration_id' => $reg->id,
                        'kind' => 'pelunasan',
                        'amount' => $reg->total_fee,
                        'proof' => $reg->payment_proof ?: null,
                        'status' => 'verified',
                        'bank_account_id' => $reg->payment_bank_account_id,
                        'verified_at' => $kapan,
                        'verified_by' => $reg->payment_verified_by,
                        'created_at' => $kapan,
                        'updated_at' => $kapan,
                    ]);

                    DB::table('registrations')
                        ->where('id', $reg->id)
                        ->update(['amount_paid' => $reg->total_fee]);
                }
            });
    }

    public function down(): void
    {
        // Tidak dihapus: baris backfill tidak bisa dibedakan dari pembayaran
        // sungguhan yang masuk setelah migrasi ini, dan menghapusnya berarti
        // menghapus catatan uang yang benar-benar diterima.
    }
};
