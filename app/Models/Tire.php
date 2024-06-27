<?php

namespace App\Models;

use App\Enums\TireStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tire extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'purchased_on' => 'date',
            'status' => TireStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
