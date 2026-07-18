<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dateTime('vote_start')->nullable()->after('vote_price');
            $table->dateTime('vote_end')->nullable()->after('vote_start');
        });
    }

    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dropColumn(['vote_start', 'vote_end']);
        });
    }
};
