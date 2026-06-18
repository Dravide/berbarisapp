<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Izinkan nilai pengurangan desimal (mis. -2.5), sebelumnya integer.
     */
    public function up(): void
    {
        Schema::table('score_deductions', function (Blueprint $table) {
            $table->decimal('amount', 8, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('score_deductions', function (Blueprint $table) {
            $table->integer('amount')->default(0)->change();
        });
    }
};
