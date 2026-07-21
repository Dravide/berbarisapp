<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_categories', function (Blueprint $table) {
            $table->decimal('registration_fee', 12, 2)->nullable()->after('max_registrations_per_school');
        });
    }

    public function down(): void
    {
        Schema::table('competition_categories', function (Blueprint $table) {
            $table->dropColumn('registration_fee');
        });
    }
};
