<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('password')->nullable()->after('magic_token');
            $table->string('school_email')->nullable()->after('no_hp');
        });

        Schema::table('competition_categories', function (Blueprint $table) {
            $table->unsignedInteger('max_registrations_per_school')->default(1)->after('kuota');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn(['password', 'school_email']);
        });

        Schema::table('competition_categories', function (Blueprint $table) {
            $table->dropColumn('max_registrations_per_school');
        });
    }
};
