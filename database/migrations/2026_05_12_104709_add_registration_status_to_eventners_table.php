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
            $table->string('registration_status')->default('open')->comment('open, booking, closed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dropColumn('registration_status');
        });
    }
};
