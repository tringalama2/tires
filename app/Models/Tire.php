<?php

namespace App\Models;

use App\Concerns\HasHashid;
use App\Enums\TirePosition;
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

    /**
     * Non-setup placements ordered by rotation odometer, with the rotation's
     * odometer pulled alongside each row as `rotation_odometer`. The basis
     * for every wear calculation.
     */
    public function wearPlacements(): HasMany
    {
        return $this->placements()
            ->join('rotations', 'rotations.id', '=', 'placements.rotation_id')
            ->where('rotations.is_setup', false)
            ->orderBy('rotations.odometer')
            ->select('placements.*', 'rotations.odometer as rotation_odometer');
    }

    /**
     * Rule A — Current position: to_position of the tire's latest placement
     * (including the setup placement, which anchors position until overwritten).
     */
    public function currentPosition(): ?TirePosition
    {
        return $this->placements()
            ->join('rotations', 'rotations.id', '=', 'placements.rotation_id')
            ->orderByDesc('rotations.odometer')
            ->value('placements.to_position');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', TireStatus::Active);
    }
}
