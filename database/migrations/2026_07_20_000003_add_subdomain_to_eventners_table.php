<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->string('subdomain', 63)->nullable()->unique()->after('slug');
            $table->index('subdomain', 'idx_eventners_subdomain');
        });
    }

    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dropIndex('idx_eventners_subdomain');
            $table->dropColumn('subdomain');
        });
    }
};
