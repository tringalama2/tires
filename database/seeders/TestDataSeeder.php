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

        $tire1 = Tire::factory()->installed()->count(1)->for($user)->create();
        $tire2 = Tire::factory()->installed()->count(1)->for($user)->create();
        $tire3 = Tire::factory()->installed()->count(1)->for($user)->create();
        $tire4 = Tire::factory()->installed()->count(1)->for($user)->create();
        $tire5 = Tire::factory()->installed()->count(1)->for($user)->create();

        Rotation::factory()
            ->for($user)->sequence(
                ['rotated_on' => '2021-12-31'],
                ['rotated_on' => '2022-04-10'],
                ['rotated_on' => '2022-09-15'],
            )
            ->for($user)->sequence(
                ['odometer' => '54718'],
                ['odometer' => '62736'],
                ['odometer' => '69627'],
            )
            ->hasAttached(
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
            )
            ->count(3)
            ->create();
    }
}
