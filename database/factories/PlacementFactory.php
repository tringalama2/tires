<?php

namespace Database\Factories;

use App\Enums\TirePosition;
use App\Models\Placement;
use App\Models\Rotation;
use App\Models\Tire;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Placement>
 */
class PlacementFactory extends Factory
{
    public function definition(): array
    {
        $positions = TirePosition::cases();

        return [
            'rotation_id' => Rotation::factory(),
            'tire_id' => Tire::factory(),
            'from_position' => fake()->randomElement($positions)->value,
            'to_position' => fake()->randomElement($positions)->value,
            'tread_center' => fake()->randomFloat(1, 2, 16),
            'tread_inner' => null,
            'tread_outer' => null,
            'note' => null,
            'is_feathering' => false,
            'is_cupped' => false,
        ];
    }
}
