<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $hasRegIndex = collect(DB::select("SHOW INDEX FROM score_deductions WHERE Key_name = 'score_deductions_registration_id_index'"))->isNotEmpty();
            if (! $hasRegIndex) {
                Schema::table('score_deductions', function (Blueprint $table) {
                    $table->index('registration_id', 'score_deductions_registration_id_index');
                });
            }

            $hasFk = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'score_deductions' AND CONSTRAINT_NAME = 'score_deductions_deduction_criteria_id_foreign'"))->isNotEmpty();
            if ($hasFk) {
                Schema::table('score_deductions', function (Blueprint $table) {
                    $table->dropForeign(['deduction_criteria_id']);
                });
            }

            $hasUnique = collect(DB::select("SHOW INDEX FROM score_deductions WHERE Key_name = 'unique_deduction_per_registration'"))->isNotEmpty();
            if ($hasUnique) {
                Schema::table('score_deductions', function (Blueprint $table) {
                    $table->dropUnique('unique_deduction_per_registration');
                });
            }
        } else {
            Schema::table('score_deductions', function (Blueprint $table) {
                $table->dropUnique('unique_deduction_per_registration');
            });
        }

        Schema::table('score_deductions', function (Blueprint $table) {
            $table->foreignId('deduction_criteria_id')->nullable()->change();
            $table->foreign('deduction_criteria_id')->references('id')->on('deduction_criterias')->cascadeOnDelete();

            $table->foreignId('assessment_category_id')->nullable()->after('deduction_criteria_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competition_round_id')->nullable()->after('assessment_category_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('score_deductions', function (Blueprint $table) {
            $table->dropForeign(['assessment_category_id']);
            $table->dropColumn('assessment_category_id');

            $table->dropForeign(['competition_round_id']);
            $table->dropColumn('competition_round_id');

            $table->dropForeign(['deduction_criteria_id']);
            $table->foreignId('deduction_criteria_id')->nullable(false)->change();
            $table->foreign('deduction_criteria_id')->references('id')->on('deduction_criterias')->cascadeOnDelete();
            $table->unique(['registration_id', 'deduction_criteria_id'], 'unique_deduction_per_registration');

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->dropIndex('score_deductions_registration_id_index');
            }
        });
    }
};
