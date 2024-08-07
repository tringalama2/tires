<?php

namespace Database\Seeders;

use App\Enums\TirePosition;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $vehicle_tires = 5;
        $rotations_per_vehicle = 20;
        $rotation_interval_miles = 5000;
        $rotation_interval_months = 6;
        $months_of_rotations = $rotations_per_vehicle * $rotation_interval_months;
        $start_rotation_on = (CarbonImmutable::now())->subMonths($months_of_rotations);

        $user = User::factory()->state([
            'first_name' => 'Steve',
            'last_name' => 'T',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ])->has(Vehicle::factory()
            ->state(['nickname' => 'Seeded Vehicle', 'tire_count' => $vehicle_tires, 'starting_odometer' => 1500])
            ->has(Tire::factory()->installed()->count($vehicle_tires)->sequence(
                    ['label' => 'Tire 1', 'tin' => '1111aa'],
                    ['label' => 'Tire 2', 'tin' => '2222bb'],
                    ['label' => 'Tire 3', 'tin' => '3333cc'],
                    ['label' => 'Tire 4', 'tin' => '4444dd'],
                    ['label' => 'Tire 5', 'tin' => '5555ee'],
                )->has(
                    Rotation::factory()->count($rotations_per_vehicle)
                        // starting_position follow the loop (1 through 5), but offsets by 1 every 5 (e.g. 2,3,4,5,1)
                        ->sequence(fn (Sequence $sequence) => ['starting_position' =>
                            (
                                (($sequence->index % $vehicle_tires) + (floor($sequence->index/$vehicle_tires)
                                    % 5))
                                % 5)
                            + 1
                        ])
                        ->sequence(fn (Sequence $sequence) => ['starting_odometer' => $rotation_interval_miles * (($sequence->index % $rotations_per_vehicle)+1)])
                        ->sequence(fn (Sequence $sequence) => ['rotated_on' => $start_rotation_on->addMonths($rotation_interval_months * (($sequence->index % $rotations_per_vehicle)+1))->toDateString()])
                )
            )
        )
        ->create();
    }
}
