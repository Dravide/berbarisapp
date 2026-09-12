<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tempat per tingkat lomba. Nullable = event satu tempat tetap boleh tidak
     * mengisi apa pun. nullOnDelete: hapus tempat tidak boleh ikut menghapus
     * tingkat lomba — cukup jadi "tempat belum ditentukan".
     */
    public function up(): void
    {
        Schema::table('competition_categories', function (Blueprint $table) {
            $table->foreignId('venue_id')->nullable()->after('parent_id')
                ->constrained('eventner_venues')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('competition_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('venue_id');
        });
    }
};
