<?php

namespace App\Enums;

enum Plan: string
{
    case Free = 'free';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Gratis',
            self::Paid => 'Berbayar',
        };
    }
}
