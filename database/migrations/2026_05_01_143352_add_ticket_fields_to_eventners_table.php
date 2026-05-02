<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->boolean('ticket_active')->default(false)->after('longitude');
            $table->decimal('ticket_price', 12, 2)->nullable()->after('ticket_active');
            $table->text('ticket_description')->nullable()->after('ticket_price');
            $table->integer('ticket_max_per_order')->default(10)->after('ticket_description');
        });
    }

    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dropColumn(['ticket_active', 'ticket_price', 'ticket_description', 'ticket_max_per_order']);
        });
    }
};
