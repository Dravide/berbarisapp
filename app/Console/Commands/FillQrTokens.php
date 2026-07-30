<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('registrations:fill-qr-tokens')]
#[Description('Generate qr_token untuk registrasi lama yang belum punya')]
class FillQrTokens extends Command
{
    public function handle()
    {
        $count = 0;
        \App\Models\Registration::whereNull('qr_token')->chunk(100, function ($registrations) use (&$count) {
            foreach ($registrations as $reg) {
                $reg->qr_token = strtoupper(\Illuminate\Support\Str::random(8));
                $reg->save();
                $count++;
            }
        });

        $this->info("{$count} registrasi berhasil diisi qr_token.");
    }
}
