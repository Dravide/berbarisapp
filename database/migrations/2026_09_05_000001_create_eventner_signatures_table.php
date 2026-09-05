<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventner_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eventner_id')->constrained('eventners')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('image'); // path PNG di disk public
            $table->timestamps();
        });

        Schema::table('eventners', function (Blueprint $table) {
            $table->enum('signature_mode', ['image', 'qr'])->default('qr');
            $table->foreignId('active_signature_id')->nullable()
                ->constrained('eventner_signatures')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_signature_id');
            $table->dropColumn('signature_mode');
        });

        Schema::dropIfExists('eventner_signatures');
    }
};
