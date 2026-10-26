<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ringkasan pembayaran di baris pendaftaran, plus status DP.
 *
 * `amount_paid` adalah CACHE dari SUM(registration_payments.amount) yang sudah
 * diverifikasi, bukan angka yang diketik siapa pun. Ia disimpan karena
 * `payment_status` dipakai di query agregat — laporan keuangan, rekap PDF,
 * grafik 30 hari, withCount per kategori — dan semua itu tidak bisa memanggil
 * accessor per baris. Satu-satunya penulisnya: App\Services\PaymentRecorder.
 *
 * `dp_deadline` diisi saat DP diverifikasi (tanggal verifikasi + dp_days milik
 * tingkatnya). Null = tanpa tenggat, atau DP belum diverifikasi.
 *
 * Enum `payment_status` dapat nilai `dp_paid`: sudah menyetor sebagian, sisa
 * tagihan masih ada. Nilai ini juga cermin, bukan sumber kebenaran — yang
 * menentukan tetap perbandingan amount_paid dengan total_fee.
 *
 * `payment_verified_at` / `payment_verified_by` TIDAK berubah arti: keduanya
 * tetap hanya terisi saat benar-benar lunas. Invoice gabungan dan laporan lama
 * membacanya, jadi mengisinya saat DP akan membuat kwitansi "LUNAS" untuk
 * pasukan yang baru bayar 30%.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->decimal('amount_paid', 12, 2)->default(0)->after('total_fee');
            $table->timestamp('dp_deadline')->nullable()->after('amount_paid');
        });

        // SQLite mengemulasi enum sebagai varchar tanpa CHECK, jadi tidak ada
        // yang perlu diubah — dan menulis MODIFY COLUMN di sana hanya galat.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE registrations MODIFY COLUMN payment_status ENUM('unpaid', 'pending_verification', 'dp_paid', 'paid', 'expired', 'free') NOT NULL DEFAULT 'free'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            // Baris ber-status dp_paid akan menolak enum lama, jadi turunkan
            // dulu ke unpaid — jumlah uangnya tetap utuh di amount_paid dan
            // registration_payments.
            DB::statement("UPDATE registrations SET payment_status = 'unpaid' WHERE payment_status = 'dp_paid'");
            DB::statement("ALTER TABLE registrations MODIFY COLUMN payment_status ENUM('unpaid', 'pending_verification', 'paid', 'expired', 'free') NOT NULL DEFAULT 'free'");
        }

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'dp_deadline']);
        });
    }
};
