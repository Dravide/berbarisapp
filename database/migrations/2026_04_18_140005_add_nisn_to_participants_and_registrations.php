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
        Schema::table('participants', function (Blueprint $table) {
            $table->string('nisn')->nullable()->after('nama');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->string('danton_nisn')->nullable()->after('danton_nama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('nisn');
        });
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('danton_nisn');
        });
    }
};
