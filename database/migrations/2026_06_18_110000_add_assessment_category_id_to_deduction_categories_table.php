<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rubrik Pengurangan kini menempel pada Kategori Penilaian tertentu.
     * assessment_category_id nullable agar data lama (global) tetap valid,
     * namun tidak mengurangi kategori mana pun sampai panitia menentukan targetnya.
     */
    public function up(): void
    {
        Schema::table('deduction_categories', function (Blueprint $table) {
            $table->foreignId('assessment_category_id')
                ->nullable()
                ->after('eventner_id')
                ->constrained('assessment_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deduction_categories', function (Blueprint $table) {
            $table->dropForeign(['assessment_category_id']);
            $table->dropColumn('assessment_category_id');
        });
    }
};
