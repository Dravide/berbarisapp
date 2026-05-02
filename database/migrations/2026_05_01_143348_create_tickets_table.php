<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eventner_id')->constrained('eventners')->cascadeOnDelete();
            $table->string('order_code', 20)->unique();
            $table->string('buyer_name');
            $table->string('buyer_email');
            $table->string('buyer_phone')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('price_per_ticket', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('xendit_invoice_id')->nullable();
            $table->string('xendit_invoice_url')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->enum('status', ['PENDING', 'PAID', 'EXPIRED', 'CHECKED_IN'])->default('PENDING');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->unsignedBigInteger('checked_in_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
