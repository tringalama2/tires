<?php

namespace Database\Seeders;

use App\Models\Rotation;
use App\Models\Tire;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::oldest()->first();

        $tire1 = Tire::factory()->installed()->tire1()->count(1)->for($user)->create();
        $tire2 = Tire::factory()->installed()->tire2()->count(1)->for($user)->create();
        $tire3 = Tire::factory()->installed()->tire3()->count(1)->for($user)->create();
        $tire4 = Tire::factory()->installed()->tire4()->count(1)->for($user)->create();
        $tire5 = Tire::factory()->installed()->tire5()->count(1)->for($user)->create();

        Rotation::factory()
            ->for($user)->state(['rotated_on' => '2021-12-31', 'odometer' => '54718'])->hasAttached(
                $tire1,
                ['position' => 1, 'tread' => 10]
            )
            ->hasAttached(
                $tire2,
                ['position' => 2, 'tread' => 10]
            )
            ->hasAttached(
                $tire3,
                ['position' => 3, 'tread' => 10]
            )
            ->hasAttached(
                $tire4,
                ['position' => 4, 'tread' => 10]
            )
            ->hasAttached(
                $tire5,
                ['position' => 5, 'tread' => 10]
            )->create();


        Rotation::factory()
            ->for($user)->state(['rotated_on' => '2022-04-10', 'odometer' => '62736'])->hasAttached(
                $tire1,
                ['position' => 2, 'tread' => 8]
            )
            ->hasAttached(
                $tire2,
                ['position' => 3, 'tread' => 8]
            )
            ->hasAttached(
                $tire3,
                ['position' => 4, 'tread' => 9]
            )
            ->hasAttached(
                $tire4,
                ['position' => 5, 'tread' => 9]
            )
            ->hasAttached(
                $tire5,
                ['position' => 1, 'tread' => 9]
            )
            ->create();

        Rotation::factory()
            ->for($user)->state(['rotated_on' => '2022-09-15', 'odometer' => '69627'])->hasAttached(
                $tire1,
                ['position' => 3, 'tread' => 6]
            )
            ->hasAttached(
                $tire2,
                ['position' => 4, 'tread' => 8]
            )
            ->hasAttached(
                $tire3,
                ['position' => 5, 'tread' => 8]
            )
            ->hasAttached(
                $tire4,
                ['position' => 1, 'tread' => 7]
            )
            ->hasAttached(
                $tire5,
                ['position' => 2, 'tread' => 7]
            )->create();
    }

}
