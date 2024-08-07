<?php

namespace Database\Factories;

use App\Enums\TirePosition;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class RotationFactory extends Factory
{
    protected $model = Rotation::class;

    public function definition(): array
    {
        return [
            'tire_id' => Tire::factory(),
            'starting_position' => $this->faker->randomElement(TirePosition::class),
            'rotated_on' => $this->faker->date(),
            'starting_odometer' => $this->faker->numberBetween(1, 16777215),
            'starting_tread' => $this->faker->numberBetween(0, 32),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
