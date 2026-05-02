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
        Schema::table('assessment_criterias', function (Blueprint $table) {
            $table->decimal('weight', 5, 2)->default(1.00)->after('score_options');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessment_criterias', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
    }
};
