<?php

namespace App\Models;

use App\Enums\TirePosition;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Znck\Eloquent\Relations\BelongsToThrough;

class Rotation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'starting_position',
        'rotated_on',
        'starting_odometer',
        'starting_tread',
    ];

    protected function casts(): array
    {
        return [
            'rotated_on' => 'date:Y-m-d',
            'starting_position' => TirePosition::class,
        ];
    }

    public function tire(): BelongsTo
    {
        return $this->belongsTo(Tire::class);
    }

    public function vehicle(): BelongsToThrough
    {
        return $this->belongsToThrough(Vehicle::class, Tire::class);
    }

    public function user(): BelongsToThrough
    {
        return $this->belongsToThrough(User::class, [Vehicle::class, Tire::class]);
    }
}
