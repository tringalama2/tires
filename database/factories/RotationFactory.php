<?php

namespace Database\Factories;

use App\Models\Rotation;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class RotationFactory extends Factory
{
    protected $model = Rotation::class;

    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'rotated_on' => $this->faker->date(),
            'odometer' => $this->faker->numberBetween(1, 999999),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
