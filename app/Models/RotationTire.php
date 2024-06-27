<?php

namespace App\Models;

use App\Enums\TirePosition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class RotationTire extends Pivot
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rotation_id' => 'string',
            'tire_id' => 'string',
            'position' => TirePosition::class,
        ];
    }
}
