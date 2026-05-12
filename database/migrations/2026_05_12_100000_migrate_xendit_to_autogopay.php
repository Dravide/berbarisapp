<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vote_transactions', function (Blueprint $table) {
            $table->renameColumn('xendit_invoice_id', 'autogopay_transaction_id');
            $table->renameColumn('xendit_invoice_url', 'qr_url');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->renameColumn('xendit_invoice_id', 'autogopay_transaction_id');
            $table->renameColumn('xendit_invoice_url', 'qr_url');
        });
    }

    public function down(): void
    {
        Schema::table('vote_transactions', function (Blueprint $table) {
            $table->renameColumn('autogopay_transaction_id', 'xendit_invoice_id');
            $table->renameColumn('qr_url', 'xendit_invoice_url');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->renameColumn('autogopay_transaction_id', 'xendit_invoice_id');
            $table->renameColumn('qr_url', 'xendit_invoice_url');
        });
    }
};
