<?php

namespace App\Models;

use App\Enums\TirePosition;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Znck\Eloquent\Relations\BelongsToThrough;

class Rotation extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = ['id'];

    public function user(): BelongsToThrough
    {
        return $this->belongsToThrough(User::class, Vehicle::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function tires(): BelongsToMany
    {
        return $this->belongsToMany(Tire::class)
            ->using(RotationTire::class)
            ->as('tireDetails')
            ->withPivot('position', 'tread')
            ->withTimestamps();
    }

    public function tiresByPosition(TirePosition $position): BelongsToMany
    {
        return $this->belongsToMany(Tire::class)
            ->using(RotationTire::class)
            ->as('tireDetails')
            ->withPivot('position', 'tread')
            ->wherePivot('position', $position)
            ->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'rotated_on' => 'date',
        ];
    }
}
