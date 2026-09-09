<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('registration_fee')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_free')->default(false);
            $table->boolean('highlight')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('saas_plan_feature', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saas_plan_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key');
            $table->unique(['saas_plan_id', 'feature_key']);
        });

        Schema::table('eventners', function (Blueprint $table) {
            $table->foreignId('saas_plan_id')->nullable()->after('plan')->constrained('saas_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dropConstrainedForeignId('saas_plan_id');
        });
        Schema::dropIfExists('saas_plan_feature');
        Schema::dropIfExists('saas_plans');
    }
};
