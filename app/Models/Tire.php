<?php

namespace App\Models;

use App\Concerns\HasHashid;
use App\Enums\TireStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Tire extends Model
{
    use HasFactory, HasHashid;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'purchased_on' => 'date:Y-m-d',
            'status' => TireStatus::class,
            'has_cracking' => 'boolean',
            'has_bulge' => 'boolean',
            'has_cupping' => 'boolean',
            'has_puncture_repair' => 'boolean',
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
