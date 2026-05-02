<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE registrations MODIFY COLUMN status_berkas ENUM('Menunggu', 'Terverifikasi', 'Ditolak', 'booking', 'confirmed', 'dibatalkan') NOT NULL DEFAULT 'booking'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE registrations MODIFY COLUMN status_berkas ENUM('Menunggu', 'Terverifikasi', 'Ditolak') NOT NULL DEFAULT 'Menunggu'");
    }
};
