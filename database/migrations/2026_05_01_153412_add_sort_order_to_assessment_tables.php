<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_categories', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('name');
        });

        Schema::table('assessment_sub_categories', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('name');
        });

        Schema::table('assessment_criterias', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('name');
        });

        // Set initial sort_order based on existing id
        \DB::statement('UPDATE assessment_categories SET sort_order = id');
        \DB::statement('UPDATE assessment_sub_categories SET sort_order = id');
        \DB::statement('UPDATE assessment_criterias SET sort_order = id');
    }

    public function down(): void
    {
        Schema::table('assessment_categories', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::table('assessment_sub_categories', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::table('assessment_criterias', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
