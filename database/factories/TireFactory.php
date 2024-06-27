<?php

namespace Database\Factories;

use App\Enums\TireStatus;
use App\Models\Tire;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TireFactory extends Factory
{
    protected $model = Tire::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tin' => $this->faker->unique()->text(12),
            'label' => $this->faker->word(),
            'desc' => $this->faker->optional(20)->sentence(),
            'size' => $this->faker->optional(80)->word(),
            'purchased_on' => Carbon::now(),
            'notes' => $this->faker->optional(20)->word(),
            'status' => $this->faker->randomElement(TireStatus::class),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    public function installed(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => TireStatus::Installed,
            ];
        });
    }
}
