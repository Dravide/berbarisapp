<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dateTime('ticket_start')->nullable()->after('ticket_active');
            $table->dateTime('ticket_end')->nullable()->after('ticket_start');
        });
    }

    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dropColumn(['ticket_start', 'ticket_end']);
        });
    }
};
