<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('error_logs', function (Blueprint $table) {
            // Error sama (kode sama) dicatat sebagai satu baris:
            // occurrences = berapa kali terjadi, last_seen_at = kategori
            // terakhir dilihat. Nullable: baris yang dibuat sebelum kolom
            // ada tetap valid (tampil sebagai 1×).
            $table->unsignedInteger('occurrences')->nullable()->after('code');
            $table->timestamp('last_seen_at')->nullable()->index()->after('occurrences');
        });
    }

    public function down(): void
    {
        Schema::table('error_logs', function (Blueprint $table) {
            $table->dropIndex(['last_seen_at']);
            $table->dropColumn(['occurrences', 'last_seen_at']);
        });
    }
};
