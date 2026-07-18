<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_categories', function (Blueprint $table) {
            $table->foreignId('competition_category_id')->nullable()->after('eventner_id')->constrained('competition_categories')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_categories', function (Blueprint $table) {
            $table->dropForeign(['competition_category_id']);
            $table->dropColumn('competition_category_id');
        });
    }
};
