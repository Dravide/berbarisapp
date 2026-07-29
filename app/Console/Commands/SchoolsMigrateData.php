<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\School;
use App\Models\Registration;
use Illuminate\Support\Facades\DB;

class SchoolsMigrateData extends Command
{
    protected $signature = 'schools:migrate-data';
    protected $description = 'Migrate school data from registrations to schools table';

    public function handle()
    {
        $this->info('Migrating school data...');

        $npsnGroups = Registration::query()
            ->select('npsn')
            ->groupBy('npsn')
            ->pluck('npsn');

        $bar = $this->output->createProgressBar($npsnGroups->count());
        $bar->start();

        foreach ($npsnGroups as $npsn) {
            DB::transaction(function () use ($npsn) {
                $all = Registration::where('npsn', $npsn)->get();

                // Strip suffix dari nama_sekolah untuk nama bersih
                $cleanName = preg_replace('/\s*\([A-Z]+\)$/', '', $all->first()->nama_sekolah);

                // Ambil data terbaik per NPSN
                $logo = $all->pluck('logo_sekolah')->filter()->first();
                $noHp = $all->pluck('no_hp')->filter()->first();
                $email = $all->pluck('school_email')->filter()->first();

                // Insert atau update schools
                School::updateOrCreate(
                    ['npsn' => $npsn],
                    [
                        'nama_sekolah' => $cleanName,
                        'logo_sekolah' => $logo,
                        'no_hp' => $noHp,
                        'school_email' => $email,
                    ]
                );

                // Update tiap registrasi: strip suffix jadi label_pasukan
                foreach ($all as $reg) {
                    $label = null;
                    if (preg_match('/\(([A-Z]+)\)$/', $reg->nama_sekolah, $m)) {
                        $label = $m[1];
                    }

                    $reg->updateQuietly([
                        'nama_sekolah' => $cleanName,
                        'label_pasukan' => $label,
                    ]);
                }
            });

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done! ' . $npsnGroups->count() . ' schools migrated.');
    }
}
