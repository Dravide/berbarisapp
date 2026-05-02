<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop all foreign keys that might depend on the unique index
        DB::statement('ALTER TABLE assessment_scores DROP FOREIGN KEY assessment_scores_registration_id_foreign');

        // Drop old unique index
        DB::statement('ALTER TABLE assessment_scores DROP INDEX unique_score_per_participant_criteria');

        // Create new unique index with judge_id
        DB::statement('ALTER TABLE assessment_scores ADD UNIQUE KEY unique_score_participant_criteria_judge (registration_id, assessment_criteria_id, judge_id)');

        // Recreate FK
        DB::statement('ALTER TABLE assessment_scores ADD CONSTRAINT assessment_scores_registration_id_foreign FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE assessment_scores DROP FOREIGN KEY assessment_scores_registration_id_foreign');
        DB::statement('ALTER TABLE assessment_scores DROP INDEX unique_score_participant_criteria_judge');
        DB::statement('ALTER TABLE assessment_scores ADD UNIQUE KEY unique_score_per_participant_criteria (registration_id, assessment_criteria_id)');
        DB::statement('ALTER TABLE assessment_scores ADD CONSTRAINT assessment_scores_registration_id_foreign FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE');
    }
};
