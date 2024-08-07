<?php

namespace Database\Seeders;

use App\Models\Rotation;
use App\Models\Tire;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class BenchmarkSeeder extends Seeder
{
    public function run(): void
    {
        $vehicle_tires = 5;
        $rotations_per_vehicle = 20;
        $rotation_interval_miles = 5000;
        $rotation_interval_months = 6;
        $months_of_rotations = $rotations_per_vehicle * $rotation_interval_months;
        $start_rotation_on = (CarbonImmutable::now())->subMonths($months_of_rotations);

        User::factory()->count(5)
            ->has(
                Vehicle::factory()->count(3)
                ->has(
                    Tire::factory()->count($vehicle_tires)
                        ->installed()
                        ->has(
                        Rotation::factory()->count($rotations_per_vehicle)
                                ->state(new Sequence(
                                    ['starting_position' => 1],
                                    ['starting_position' => 2],
                                    ['starting_position' => 3],
                                    ['starting_position' => 4],
                                    ['starting_position' => 5],
                                ))
                                ->sequence(fn (Sequence $sequence) => ['starting_odometer' => $rotation_interval_miles * (($sequence->index % $rotations_per_vehicle)+1)])
                                ->sequence(fn (Sequence $sequence) => ['rotated_on' => $start_rotation_on->addMonths($rotation_interval_months * (($sequence->index % $rotations_per_vehicle)+1))->toDateString()])
                    )
                )
            )
            ->create();
    }
}
