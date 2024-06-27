<?php

namespace App\Enums;

enum TireStatus: int
{
    case Purchased = 1;
    case Installed = 2;
    case Removed = 3;
    case Retired = 4;
}
