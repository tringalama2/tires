<?php

namespace App\Models;

use App\Enums\TirePosition;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rotation extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
