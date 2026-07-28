<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_text_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_template_id')->constrained('certificate_templates')->cascadeOnDelete();
            $table->string('field_key');
            $table->string('label');
            $table->float('x')->default(148.5);     // center X for A4 landscape
            $table->float('y')->default(105);       // center Y
            $table->integer('font_size')->default(18);
            $table->string('font_color')->default('#000000');
            $table->string('text_align')->default('center'); // left, center, right
            $table->string('font_weight')->default('normal'); // normal, bold
            $table->float('max_width')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_text_fields');
    }
};
