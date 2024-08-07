<?php

namespace App\Models;

use App\Enums\TirePosition;
use App\Enums\TireStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Znck\Eloquent\Relations\BelongsToThrough;

class Tire extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'purchased_on' => 'date:Y-m-d',
            'status' => TireStatus::class,
        ];
    }

    public function user(): BelongsToThrough
    {
        return $this->belongsToThrough(User::class, Vehicle::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function rotations(): HasMany
    {
        return $this->hasMany(Rotation::class);
    }

    public function scopeInstalled(Builder $query): void
    {
        $query->where('status', TireStatus::Installed);
    }

    public function currentRotation(): HasOne
    {
        return $this->rotations()->one()->ofMany([
            'starting_odometer' => 'max',
        ]);
    }


    public function scopeCurrentRotationByPosition(Builder $query, TirePosition $tirePosition): void
    {
        $query->withWhereHas('currentRotation', function ($query) use ($tirePosition) {
            $query->where('starting_position', $tirePosition);
        });
    }
}
