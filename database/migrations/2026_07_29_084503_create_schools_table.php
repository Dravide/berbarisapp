<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->string('npsn')->primary();
            $table->string('nama_sekolah');
            $table->string('logo_sekolah')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('school_email')->nullable();
            $table->timestamps();
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->string('label_pasukan', 10)->nullable()->after('competition_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('label_pasukan');
        });

        Schema::dropIfExists('schools');
    }
};
