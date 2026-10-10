<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['percent', 'flat']);
            $table->unsignedInteger('value');
            // Cap untuk tipe percent; null = tanpa batas atas.
            $table->unsignedInteger('max_discount')->nullable();
            // Kuota pemakaian (eventner yang sudah membayar); null = tanpa batas.
            $table->unsignedInteger('max_uses')->nullable();
            // Null = berlaku untuk semua paket berbayar.
            $table->foreignId('saas_plan_id')->nullable()->constrained('saas_plans')->nullOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('eventners', function (Blueprint $table) {
            $table->foreignId('registration_voucher_id')->nullable()->constrained('registration_vouchers')->nullOnDelete()->after('saas_plan_id');
            // Snapshot nominal potongan saat pendaftaran — webhook membandingkan
            // settlement ke effective_price dikurangi angka beku ini, jadi
            // menyunting/menonaktifkan voucher belakangan tak mematahkan transaksi.
            $table->unsignedInteger('voucher_discount')->default(0)->after('registration_voucher_id');
        });
    }

    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dropForeign(['registration_voucher_id']);
            $table->dropColumn(['registration_voucher_id', 'voucher_discount']);
        });

        Schema::dropIfExists('registration_vouchers');
    }
};
