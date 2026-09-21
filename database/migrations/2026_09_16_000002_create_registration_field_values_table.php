<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('registrations')->cascadeOnDelete();
            $table->foreignId('registration_field_id')->constrained('registration_fields')->cascadeOnDelete();
            // Untuk tipe file/image, isinya path disk 'public' (registrations/fields/{id}/...).
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['registration_id', 'registration_field_id'], 'registration_field_value_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_field_values');
    }
};
