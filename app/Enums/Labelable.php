<?php

namespace App\Enums;

trait Labelable
{
    public static function labels(): array
    {
        foreach (self::cases() as $case) {
            $array[$case->value] = $case->label();
        }

        return $array;
    }
}
