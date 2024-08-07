<?php

namespace App\Models;

use App\Enums\TirePosition;
use App\Enums\TireStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    const MAX_VEHICLES_PER_USER = 5;

    protected $fillable = [
        'user_id',
        'year',
        'make',
        'model',
        'vin',
        'nickname',
        'tire_count',
        'starting_odometer',
    ];

    protected function casts(): array
    {
        return [
            'last_selected_at' => 'datetime',
        ];
    }

    protected function yearMakeModel(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $attributes['year'] . ' ' . $attributes['make'] . ' ' . $attributes['model']
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tires(): HasMany
    {
        return $this->hasMany(Tire::class);
    }

    public function installedTires(): HasMany
    {
        return $this->hasMany(Tire::class)->where('status', TireStatus::Installed);
    }

    public function rotations(): HasManyThrough
    {
        return $this->hasManyThrough(Rotation::class, Tire::class);
    }
}
