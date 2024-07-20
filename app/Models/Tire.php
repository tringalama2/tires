<?php

namespace App\Models;

use App\Enums\TireStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function rotations(): BelongsToMany
    {
        return $this->belongsToMany(Rotation::class)
            ->using(RotationTire::class)
            ->as('rotationDetails')
            ->withPivot('position', 'tread')
            ->withTimestamps();
    }
}
