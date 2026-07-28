<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: no ENUM support, skip column modification
            return;
        }

        DB::statement("ALTER TABLE registrations MODIFY COLUMN status_berkas ENUM('Menunggu', 'Terverifikasi', 'Ditolak', 'booking', 'confirmed', 'dibatalkan') NOT NULL DEFAULT 'booking'");
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE registrations MODIFY COLUMN status_berkas ENUM('Menunggu', 'Terverifikasi', 'Ditolak') NOT NULL DEFAULT 'Menunggu'");
    }
};
