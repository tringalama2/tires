<?php

namespace Database\Factories;

use App\Enums\TireStatus;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class TireFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'label' => fake()->bothify('T#'),
            'brand' => fake()->optional(80)->randomElement(['BF Goodrich', 'Falken', 'Continental', 'Goodyear', 'Toyo', 'Bridgestone']),
            'model' => fake()->optional(80)->bothify('###??'),
            'tin' => fake()->optional(40)->bothify('????????????'),
            'size' => fake()->optional(60)->bothify('###/##R##'),
            'purchased_on' => fake()->optional(60)->date(),
            'notes' => null,
            'has_cracking' => false,
            'has_bulge' => false,
            'has_cupping' => false,
            'has_puncture_repair' => false,
            'status' => TireStatus::Active,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => TireStatus::Active]);
    }

    public function retired(): static
    {
        return $this->state(['status' => TireStatus::Retired]);
    }
}
