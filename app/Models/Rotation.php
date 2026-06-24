<?php

namespace App\Models;

use App\Concerns\HasHashid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rotation extends Model
{
    use HasFactory, HasHashid;

    protected $fillable = [
        'vehicle_id',
        'rotated_on',
        'odometer',
        'note',
        'is_setup',
        'is_swap',
    ];

    protected function casts(): array
    {
        return [
            'rotated_on' => 'date:Y-m-d',
            'is_setup' => 'boolean',
            'is_swap' => 'boolean',
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

    public function scopeReal(Builder $query): void
    {
        $query->where('is_setup', false);
    }

    public function scopeSetup(Builder $query): void
    {
        $query->where('is_setup', true);
    }
}
