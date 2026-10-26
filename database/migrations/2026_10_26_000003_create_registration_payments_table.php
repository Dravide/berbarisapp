<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan penerimaan uang biaya pendaftaran.
 *
 * Sebelum ini tidak ada tempat menyimpan NOMINAL yang sudah dibayar — hanya
 * `payment_status`, yang biner secara praktis. Akibatnya uang muka tidak bisa
 * dicatat tanpa memalsukan arti "lunas", dan dua pembayaran berturut-turut
 * tidak bisa dibedakan dari satu pembayaran.
 *
 * Satu baris = satu bukti transfer yang diunggah peserta. Verifikasi panitia
 * mengubah `status` baris ini, lalu PaymentRecorder menjumlah ulang
 * `registrations.amount_paid` dari baris-baris ber-status verified.
 *
 * `amount` adalah nominal yang DIKLAIM peserta. Yang menentukan status lunas
 * tetap nominal yang diverifikasi panitia — dan panitia boleh mengoreksinya
 * bila bukti transfer menunjukkan angka lain (lihat PaymentRecorder::terima()).
 *
 * `proof` nullable: jalur portal selalu mewajibkan unggahan, tapi panitia bisa
 * mencatat pembayaran tunai yang tidak punya bukti transfer. Baris lama dari
 * backfill juga bisa kosong. Portal tetap menolak mengirim tanpa bukti —
 * kelonggaran ini untuk jalur panitia, bukan untuk peserta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('registrations')->cascadeOnDelete();

            // 'dp' = uang muka yang diminta panitia; 'pelunasan' = sisa tagihan.
            $table->enum('kind', ['dp', 'pelunasan'])->default('pelunasan');

            $table->decimal('amount', 12, 2);
            $table->string('proof')->nullable();

            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');

            $table->foreignId('bank_account_id')->nullable()
                ->constrained('eventner_bank_accounts')->nullOnDelete();

            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rejection_reason')->nullable();

            $table->timestamps();

            // Satu-satunya pola pencarian: "pembayaran apa saja milik
            // pendaftaran ini, dan mana yang masih menunggu".
            $table->index(['registration_id', 'status'], 'registration_payments_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_payments');
    }
};
