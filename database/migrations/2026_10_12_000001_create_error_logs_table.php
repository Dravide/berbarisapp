<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            // Kode pelaporan publik (mis. ER-98673): diberikan ke user agar
            // admin bisa mencarinya di halaman ini tanpa info sensitif.
            $table->string('code', 16)->unique();
            $table->text('message');
            $table->string('exception_class');
            $table->string('file')->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->unsignedInteger('http_status')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('eventner_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_agent')->nullable();
            $table->string('ip', 45)->nullable();
            $table->longText('trace')->nullable();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('created_at');
            $table->index('http_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
