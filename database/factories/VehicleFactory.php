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
            'year' => $this->faker->year(),
            'make' => $this->faker->word(),
            'model' => $this->faker->word(),
            'vin' => $this->faker->word(),
            'nickname' => $this->faker->word(),
            'last_selected_at' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}