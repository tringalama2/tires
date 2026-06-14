<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum TirePosition: string
{
    use Labelable;

    case FrontLeft = 'FL';
    case FrontRight = 'FR';
    case RearLeft = 'RL';
    case RearRight = 'RR';
    case Spare = 'SP';

    /** Canonical display order for forms and reports. */
    public static function order(): array
    {
        return [self::FrontLeft, self::FrontRight, self::RearLeft, self::RearRight, self::Spare];
    }

    public function label(): string
    {
        return match ($this) {
            self::FrontLeft => 'Front Left',
            self::FrontRight => 'Front Right',
            self::RearLeft => 'Rear Left',
            self::RearRight => 'Rear Right',
            self::Spare => 'Spare',
        };
    }

    public function camel(): string
    {
        return Str::camel($this->label());
    }

    public function snake(): string
    {
        return Str::snake($this->label());
    }
}
