<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->string('autogopay_transaction_id')->nullable()->after('registration_source');
            $table->text('qr_url')->nullable()->after('autogopay_transaction_id');
            $table->text('qr_string')->nullable()->after('qr_url');
            $table->timestamp('registration_paid_at')->nullable()->after('qr_string');
        });
    }

    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dropColumn(['autogopay_transaction_id', 'qr_url', 'qr_string', 'registration_paid_at']);
        });
    }
};
