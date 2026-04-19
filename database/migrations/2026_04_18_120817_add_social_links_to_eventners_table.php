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
            $table->string('link_instagram')->nullable();
            $table->string('link_tiktok')->nullable();
            $table->string('link_whatsapp')->nullable();
            $table->string('link_livestreaming')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dropColumn(['link_instagram', 'link_tiktok', 'link_whatsapp', 'link_livestreaming']);
        });
    }
};
