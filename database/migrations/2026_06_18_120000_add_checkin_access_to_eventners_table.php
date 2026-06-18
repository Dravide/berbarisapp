<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom akses halaman check-in publik (tanpa login Eventner).
     * - checkin_token: secret URL path, panel scan di /scan/{token}
     * - checkin_pin: 6 digit PIN, panitia input untuk auth session
     */
    public function up(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->string('checkin_token', 48)->nullable()->unique()->after('scoring_code');
            $table->string('checkin_pin', 6)->nullable()->after('checkin_token');
        });
    }

    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dropUnique(['checkin_token']);
            $table->dropColumn(['checkin_token', 'checkin_pin']);
        });
    }
};
