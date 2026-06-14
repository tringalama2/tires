<?php

namespace App\Models;

use App\Enums\TireStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }

    public function rotations(): HasManyThrough
    {
        return $this->hasManyThrough(Rotation::class, Placement::class, 'tire_id', 'id', 'id', 'rotation_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', TireStatus::Active);
    }
}
