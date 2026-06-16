<?php

namespace App\Models;

use App\Enums\TirePosition;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Placement extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'from_position' => TirePosition::class,
            'to_position' => TirePosition::class,
            'tread_center' => 'decimal:1',
            'tread_inner' => 'decimal:1',
            'tread_outer' => 'decimal:1',
            'is_feathering' => 'boolean',
            'is_cupped' => 'boolean',
        ];
    }

    public function rotation(): BelongsTo
    {
        return $this->belongsTo(Rotation::class);
    }

    public function tire(): BelongsTo
    {
        return $this->belongsTo(Tire::class);
    }

    /** True when center tread is 2+ /32" lower than avg(inner, outer) — overinflation signature. */
    public function isCenterWear(): bool
    {
        if ($this->tread_inner === null || $this->tread_outer === null) {
            return false;
        }

        $avg = ((float) $this->tread_inner + (float) $this->tread_outer) / 2;

        return ((float) $this->tread_center - $avg) <= -2.0;
    }

    /** True when center tread is 2+ /32" higher than avg(inner, outer) — underinflation signature. */
    public function isEdgeWear(): bool
    {
        if ($this->tread_inner === null || $this->tread_outer === null) {
            return false;
        }

        $avg = ((float) $this->tread_inner + (float) $this->tread_outer) / 2;

        return ((float) $this->tread_center - $avg) >= 2.0;
    }
}
