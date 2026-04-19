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
        Schema::table('eventners', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('nama_event');
        });

        // Populate existing slugs
        $eventners = \App\Models\Eventner::all();
        foreach ($eventners as $eventner) {
            $eventner->slug = \Illuminate\Support\Str::slug($eventner->nama_event . '-' . $eventner->id);
            $eventner->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
