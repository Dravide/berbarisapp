<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: tulis ulang tabel karena ENUM di-emulate via CHECK constraint,
            // dan migration 2026_05_12 (rename xendit->autogopay) tidak berjalan di SQLite.
            Schema::drop('tickets');
            Schema::create('tickets', function ($table) {
                $table->id();
                $table->foreignId('eventner_id')->constrained('eventners')->cascadeOnDelete();
                $table->string('order_code', 20)->unique();
                $table->string('buyer_name');
                $table->string('buyer_email');
                $table->string('buyer_phone')->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('price_per_ticket', 12, 2)->default(0);
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->string('autogopay_transaction_id')->nullable();
                $table->string('qr_url')->nullable();
                $table->string('qr_code_path')->nullable();
                $table->enum('status', ['PENDING', 'PAID', 'EXPIRED', 'CHECKED_IN', 'ACTIVE'])->default('PENDING');
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('checked_in_at')->nullable();
                $table->unsignedBigInteger('checked_in_by')->nullable();
                $table->timestamps();
            });
            return;
        }

        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('PENDING', 'PAID', 'EXPIRED', 'CHECKED_IN', 'ACTIVE') NOT NULL DEFAULT 'PENDING'");
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: tulis ulang tabel (down migration) — balik ke skema asli tanpa ACTIVE.
            Schema::drop('tickets');
            Schema::create('tickets', function ($table) {
                $table->id();
                $table->foreignId('eventner_id')->constrained('eventners')->cascadeOnDelete();
                $table->string('order_code', 20)->unique();
                $table->string('buyer_name');
                $table->string('buyer_email');
                $table->string('buyer_phone')->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('price_per_ticket', 12, 2)->default(0);
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->string('autogopay_transaction_id')->nullable();
                $table->string('qr_url')->nullable();
                $table->string('qr_code_path')->nullable();
                $table->enum('status', ['PENDING', 'PAID', 'EXPIRED', 'CHECKED_IN'])->default('PENDING');
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('checked_in_at')->nullable();
                $table->unsignedBigInteger('checked_in_by')->nullable();
                $table->timestamps();
            });
            return;
        }

        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('PENDING', 'PAID', 'EXPIRED', 'CHECKED_IN') NOT NULL DEFAULT 'PENDING'");
    }
};
