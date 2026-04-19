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
        Schema::table('eventners', function (Blueprint $table) {
            $table->string('tanggal_pendaftaran')->nullable()->after('tanggal');
            $table->string('technical_meeting')->nullable()->after('tanggal_pendaftaran');
            $table->string('tingkat_perlombaan')->nullable()->after('technical_meeting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dropColumn(['tanggal_pendaftaran', 'technical_meeting', 'tingkat_perlombaan']);
        });
    }
};
