<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class RotationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'rotated_on' => fake()->date(),
            'odometer' => fake()->numberBetween(50000, 200000),
            'note' => null,
            'is_setup' => false,
        ];
    }

    public function setup(): static
    {
        return $this->state(['is_setup' => true]);
    }
}
