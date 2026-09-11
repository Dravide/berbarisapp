<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Token akses tablet juri. Token sendiri adalah secret (pola sama dengan
     * eventners.checkin_token & registrations.magic_token), jadi tidak perlu PIN.
     */
    public function up(): void
    {
        Schema::table('judges', function (Blueprint $table) {
            $table->string('access_token', 16)->nullable()->unique()->after('photo');
        });

        // Backfill juri yang sudah ada supaya langsung bisa dipakai.
        foreach (DB::table('judges')->whereNull('access_token')->pluck('id') as $id) {
            DB::table('judges')->where('id', $id)->update([
                'access_token' => Str::random(16),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('judges', function (Blueprint $table) {
            $table->dropUnique(['access_token']);
            $table->dropColumn('access_token');
        });
    }
};
