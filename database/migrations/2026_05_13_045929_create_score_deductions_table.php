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
        Schema::create('score_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eventner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deduction_criteria_id')->constrained()->cascadeOnDelete();
            $table->integer('amount')->default(0);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['registration_id', 'deduction_criteria_id'], 'unique_deduction_per_registration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('score_deductions');
    }
};
