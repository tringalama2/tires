<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum TirePosition: int
{
    use Labelable;

    case FrontLeft = 1;
    case FrontRight = 2;
    case RearLeft = 3;
    case RearRight = 4;
    case Spare = 5;

    public function side(): string
    {
        return substr(self::label(), strrpos(self::label(), ' ') + 1);
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
        return Str::camel(self::label());
    }

    public function snake(): string
    {
        return Str::snake(self::label());
    }
}
