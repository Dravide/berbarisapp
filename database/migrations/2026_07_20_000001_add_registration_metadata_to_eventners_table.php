<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved')->after('user_id');
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('approved_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
            $table->enum('plan', ['free', 'paid'])->default('paid')->after('rejection_reason');
            $table->timestamp('trial_ends_at')->nullable()->after('plan');
            $table->enum('registration_source', ['self', 'admin'])->default('admin')->after('trial_ends_at');

            $table->index('status', 'idx_eventners_status');
            $table->index('plan', 'idx_eventners_plan');
            $table->index('trial_ends_at', 'idx_eventners_trial_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dropIndex('idx_eventners_status');
            $table->dropIndex('idx_eventners_plan');
            $table->dropIndex('idx_eventners_trial_ends_at');

            $table->dropColumn([
                'status',
                'approved_at',
                'approved_by',
                'rejected_at',
                'rejection_reason',
                'plan',
                'trial_ends_at',
                'registration_source',
            ]);
        });
    }
};
