<?php

namespace Database\Factories;

use App\Enums\TirePosition;
use App\Models\Rotation;
use App\Models\RotationTire;
use App\Models\Tire;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class RotationTireFactory extends Factory
{
    protected $model = RotationTire::class;

    public function definition(): array
    {
        return [
            'rotation_id' => Rotation::factory(),
            'tire_id' => Tire::factory(),
            'position' => $this->faker->randomElement(TirePosition::class),
            'tread' => $this->faker->numberBetween(0, 32),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
