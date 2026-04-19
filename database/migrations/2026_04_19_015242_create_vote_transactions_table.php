<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vote_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eventner_id')->constrained()->onDelete('cascade');
            $table->foreignId('registration_id')->constrained()->onDelete('cascade');
            $table->string('xendit_invoice_id')->unique();
            $table->string('xendit_invoice_url');
            $table->decimal('amount', 15, 2);
            $table->integer('votes_earned');
            $table->string('voter_name')->nullable();
            $table->string('voter_email')->nullable();
            $table->enum('status', ['PENDING', 'PAID', 'EXPIRED', 'FAILED'])->default('PENDING');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vote_transactions');
    }
};
