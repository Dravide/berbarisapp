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
        // 1. Add assessment_sub_category_id column (nullable initially for migration) if not exists
        if (!Schema::hasColumn('champion_assessment', 'assessment_sub_category_id')) {
            Schema::table('champion_assessment', function (Blueprint $table) {
                $table->foreignId('assessment_sub_category_id')
                    ->nullable()
                    ->after('champion_category_id')
                    ->constrained('assessment_sub_categories')
                    ->cascadeOnDelete();
            });
        }

        // 2. Data backfill: Expand category-level pivot rows into subcategory-level pivot rows
        if (Schema::hasColumn('champion_assessment', 'assessment_category_id')) {
            $oldPivots = DB::table('champion_assessment')
                ->whereNotNull('assessment_category_id')
                ->get();

            foreach ($oldPivots as $pivot) {
                // Find all subcategories for this assessment category
                $subCategories = DB::table('assessment_sub_categories')
                    ->where('assessment_category_id', $pivot->assessment_category_id)
                    ->get();

                foreach ($subCategories as $sub) {
                    // Check if this subcategory pivot already exists to prevent duplicate key errors
                    $exists = DB::table('champion_assessment')
                        ->where('champion_category_id', $pivot->champion_category_id)
                        ->where('assessment_sub_category_id', $sub->id)
                        ->exists();

                    if (!$exists) {
                        $insertData = [
                            'champion_category_id' => $pivot->champion_category_id,
                            'assessment_sub_category_id' => $sub->id,
                            'created_at' => $pivot->created_at,
                            'updated_at' => $pivot->updated_at,
                        ];

                        if (Schema::hasColumn('champion_assessment', 'assessment_category_id')) {
                            $insertData['assessment_category_id'] = $pivot->assessment_category_id;
                        }

                        DB::table('champion_assessment')->insert($insertData);
                    }
                }
            }

            // Remove the old category rows (where assessment_sub_category_id is null)
            DB::table('champion_assessment')->whereNull('assessment_sub_category_id')->delete();
        }

        // Make assessment_sub_category_id non-nullable now that old rows are removed/backfilled
        Schema::table('champion_assessment', function (Blueprint $table) {
            $table->unsignedBigInteger('assessment_sub_category_id')->nullable(false)->change();
        });

        // 3. Drop foreign key and column for assessment_category_id
        if (Schema::hasColumn('champion_assessment', 'assessment_category_id')) {
            try {
                Schema::table('champion_assessment', function (Blueprint $table) {
                    $table->dropForeign(['assessment_category_id']);
                });
            } catch (\Exception $e) {
                // Ignore if foreign key constraint doesn't exist
            }

            Schema::table('champion_assessment', function (Blueprint $table) {
                $table->dropColumn('assessment_category_id');
            });
        }

        // Add unique constraint for champion_category_id and assessment_sub_category_id
        try {
            Schema::table('champion_assessment', function (Blueprint $table) {
                $table->unique(['champion_category_id', 'assessment_sub_category_id'], 'champion_sub_category_unique');
            });
        } catch (\Exception $e) {
            // Ignore if index already exists
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('champion_assessment', function (Blueprint $table) {
            $table->dropUnique('champion_sub_category_unique');

            $table->foreignId('assessment_category_id')
                ->nullable()
                ->after('champion_category_id')
                ->constrained('assessment_categories')
                ->cascadeOnDelete();
        });

        // Reconstruct category-level rows based on the subcategories selected
        $subPivots = DB::table('champion_assessment')->get();

        foreach ($subPivots as $pivot) {
            // Find parent category for this subcategory
            $sub = DB::table('assessment_sub_categories')->find($pivot->assessment_sub_category_id);
            if ($sub) {
                $exists = DB::table('champion_assessment')
                    ->where('champion_category_id', $pivot->champion_category_id)
                    ->where('assessment_category_id', $sub->assessment_category_id)
                    ->exists();

                if (!$exists) {
                    $insertData = [
                        'champion_category_id' => $pivot->champion_category_id,
                        'assessment_category_id' => $sub->assessment_category_id,
                        'created_at' => $pivot->created_at,
                        'updated_at' => $pivot->updated_at,
                    ];

                    if (Schema::hasColumn('champion_assessment', 'assessment_sub_category_id')) {
                        $insertData['assessment_sub_category_id'] = $pivot->assessment_sub_category_id;
                    }

                    DB::table('champion_assessment')->insert($insertData);
                }
            }
        }

        // Remove the subcategory rows
        DB::table('champion_assessment')->whereNull('assessment_category_id')->delete();

        Schema::table('champion_assessment', function (Blueprint $table) {
            $table->unsignedBigInteger('assessment_category_id')->nullable(false)->change();

            $table->dropForeign(['assessment_sub_category_id']);
            $table->dropColumn('assessment_sub_category_id');
        });
    }
};
