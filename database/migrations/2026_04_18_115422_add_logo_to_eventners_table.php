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
            $table->string('logo_event')->nullable()->after('tingkat_perlombaan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dropColumn('logo_event');
        });
    }
};
