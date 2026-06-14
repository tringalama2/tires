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

    /** True when inner and outer tread diverge enough to suggest scalloping. */
    public function isScalloped(): bool
    {
        if ($this->tread_inner === null || $this->tread_outer === null) {
            return false;
        }

        return abs($this->tread_inner - $this->tread_outer) >= 2;
    }
}
