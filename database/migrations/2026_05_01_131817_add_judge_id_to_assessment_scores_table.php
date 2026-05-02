<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_scores', function (Blueprint $table) {
            $table->foreignId('judge_id')->nullable()->after('eventner_id')->constrained('judges')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assessment_scores', function (Blueprint $table) {
            $table->dropForeign(['judge_id']);
            $table->dropColumn('judge_id');
        });
    }
};
