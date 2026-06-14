<?php

namespace App\Enums;

enum TireStatus: int
{
    case Active = 1;
    case Retired = 2;

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Retired => 'Retired',
        };
    }
}
