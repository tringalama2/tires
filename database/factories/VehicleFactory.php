<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'year' => $this->faker->numberBetween(1900, 9999),
            'make' => $this->faker->text(50),
            'model' => $this->faker->text(50),
            'vin' => $this->faker->text(17),
            'nickname' => $this->faker->text(50),
            'tire_count' => $this->faker->numberBetween(4, 5),
            'last_selected_at' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
