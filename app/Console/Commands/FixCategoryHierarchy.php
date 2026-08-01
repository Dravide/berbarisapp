<?php

namespace App\Console\Commands;

use App\Models\CompetitionCategory;
use App\Models\Registration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixCategoryHierarchy extends Command
{
    protected $signature = 'categories:fix-parent-registrations';
    protected $description = 'Pindahkan registration yang ada di kategori parent (flat) ke child baru';

    public function handle(): int
    {
        // Parent tanpa child yang punya registration — data lama flat.
        $parents = CompetitionCategory::whereNull('parent_id')
            ->whereDoesntHave('children')
            ->whereHas('registrations')
            ->get();

        if ($parents->isEmpty()) {
            $this->info('Tidak ada kategori parent flat dengan registration.');
            return self::SUCCESS;
        }

        $this->info('Ditemukan ' . $parents->count() . ' kategori parent flat dengan registration.');
        $bar = $this->output->createProgressBar($parents->count());
        $bar->start();

        foreach ($parents as $parent) {
            DB::transaction(function () use ($parent) {
                // Buat child default "Regu Inti" — turunkan pengaturan dari parent
                $child = CompetitionCategory::create([
                    'eventner_id' => $parent->eventner_id,
                    'parent_id' => $parent->id,
                    'name' => 'Regu Inti',
                    'tanggal_pelaksanaan' => $parent->tanggal_pelaksanaan,
                    'kuota' => $parent->kuota,
                    'max_registrations_per_school' => $parent->max_registrations_per_school,
                    'registration_fee' => $parent->registration_fee,
                    'sort_order' => $parent->sort_order,
                ]);

                // Pindahkan semua registration parent → child baru
                $moved = Registration::where('eventner_id', $parent->eventner_id)
                    ->where('competition_category_id', $parent->id)
                    ->update(['competition_category_id' => $child->id]);

                $this->line('');
                $this->line("  [{$parent->name}] → child 'Regu Inti' (id={$child->id}), pindah {$moved} registration");
            });

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Selesai. Registrasi parent telah dipindahkan ke child.');
        return self::SUCCESS;
    }
}
