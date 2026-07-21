<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->decimal('total_fee', 12, 2)->nullable()->after('urutan_tampil');
            $table->enum('payment_status', ['unpaid', 'pending_verification', 'paid', 'expired', 'free'])->default('free')->after('total_fee');
            $table->string('payment_proof')->nullable()->after('payment_status');
            $table->foreignId('payment_bank_account_id')->nullable()->constrained('eventner_bank_accounts')->nullOnDelete()->after('payment_proof');
            $table->timestamp('payment_verified_at')->nullable()->after('payment_bank_account_id');
            $table->foreignId('payment_verified_by')->nullable()->constrained('users')->nullOnDelete()->after('payment_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropForeign(['payment_verified_by']);
            $table->dropForeign(['payment_bank_account_id']);
            $table->dropColumn([
                'total_fee',
                'payment_status',
                'payment_proof',
                'payment_bank_account_id',
                'payment_verified_at',
                'payment_verified_by',
            ]);
        });
    }
};
