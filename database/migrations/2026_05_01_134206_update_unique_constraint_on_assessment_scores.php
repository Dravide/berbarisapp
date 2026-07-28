<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: recreate the table to change unique constraint
            Schema::table('assessment_scores', function ($table) {
                $table->dropIndex('unique_score_per_participant_criteria');
            });
            Schema::table('assessment_scores', function ($table) {
                $table->index(['registration_id', 'assessment_criteria_id', 'judge_id'], 'unique_score_participant_criteria_judge');
            });
            return;
        }

        // MySQL: drop FK, drop old unique, create new unique, recreate FK
        DB::statement('ALTER TABLE assessment_scores DROP FOREIGN KEY assessment_scores_registration_id_foreign');
        DB::statement('ALTER TABLE assessment_scores DROP INDEX unique_score_per_participant_criteria');
        DB::statement('ALTER TABLE assessment_scores ADD UNIQUE KEY unique_score_participant_criteria_judge (registration_id, assessment_criteria_id, judge_id)');
        DB::statement('ALTER TABLE assessment_scores ADD CONSTRAINT assessment_scores_registration_id_foreign FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            Schema::table('assessment_scores', function ($table) {
                $table->dropIndex('unique_score_participant_criteria_judge');
            });
            Schema::table('assessment_scores', function ($table) {
                $table->unique(['registration_id', 'assessment_criteria_id'], 'unique_score_per_participant_criteria');
            });
            return;
        }

        DB::statement('ALTER TABLE assessment_scores DROP FOREIGN KEY assessment_scores_registration_id_foreign');
        DB::statement('ALTER TABLE assessment_scores DROP INDEX unique_score_participant_criteria_judge');
        DB::statement('ALTER TABLE assessment_scores ADD UNIQUE KEY unique_score_per_participant_criteria (registration_id, assessment_criteria_id)');
        DB::statement('ALTER TABLE assessment_scores ADD CONSTRAINT assessment_scores_registration_id_foreign FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE');
    }
};
