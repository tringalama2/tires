<?php

use App\Enums\TirePosition;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

uses()
    ->group('auth')
    ->in('Feature/Auth');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Build a vehicle with a user, setup rotation, and two real rotations.
 *
 * Rotation 1 (55,000 mi): T1 FR→RL(8), T2 FL→FR(12), T3 RL→SP(12), T4 RR→FL(12), T5 SP→RR(12)
 * Rotation 2 (60,000 mi): T1 RL→FL(8), T2 FR→RR(8),  T3 SP→FR(12), T4 FL→RL(12), T5 RR→SP(12)
 *
 * After Rot 2: T1@FL(8), T2@RR(8), T3@FR(12), T4@RL(12), T5@SP(12)
 * FR wears at 0.8/1k (clearly fastest, outlier). SP = 0.
 *
 * @return array{0: User, 1: Vehicle, 2: array<string, Tire>}
 */
function vehicleWithHistory(): array
{
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create([
        'user_id' => $user->id,
        'tire_count' => 5,
        'starting_odometer' => 50000,
    ]);

    $startPositions = [
        'T1' => TirePosition::FrontRight,
        'T2' => TirePosition::FrontLeft,
        'T3' => TirePosition::RearLeft,
        'T4' => TirePosition::RearRight,
        'T5' => TirePosition::Spare,
    ];

    $tires = [];
    $setup = Rotation::factory()->setup()->for($vehicle)->create(['odometer' => 50000, 'rotated_on' => '2024-01-01']);
    foreach ($startPositions as $label => $pos) {
        $tire = Tire::factory()->for($vehicle)->create(['label' => $label]);
        $setup->placements()->create(['tire_id' => $tire->id, 'from_position' => null, 'to_position' => $pos, 'tread_center' => 12]);
        $tires[$label] = $tire;
    }

    // Rotation 1: T1 wears 4/32" at FR (0.8/1k). All others wear 0.
    $rot1 = Rotation::factory()->for($vehicle)->create(['odometer' => 55000, 'rotated_on' => '2025-01-01']);
    $rot1->placements()->create(['tire_id' => $tires['T1']->id, 'from_position' => 'FR', 'to_position' => 'RL', 'tread_center' => 8]);
    $rot1->placements()->create(['tire_id' => $tires['T2']->id, 'from_position' => 'FL', 'to_position' => 'FR', 'tread_center' => 12]);
    $rot1->placements()->create(['tire_id' => $tires['T3']->id, 'from_position' => 'RL', 'to_position' => 'SP', 'tread_center' => 12]);
    $rot1->placements()->create(['tire_id' => $tires['T4']->id, 'from_position' => 'RR', 'to_position' => 'FL', 'tread_center' => 12]);
    $rot1->placements()->create(['tire_id' => $tires['T5']->id, 'from_position' => 'SP', 'to_position' => 'RR', 'tread_center' => 12]);

    // Rotation 2: T2 wears 4/32" at FR (0.8/1k). All others wear 0.
    $rot2 = Rotation::factory()->for($vehicle)->create(['odometer' => 60000, 'rotated_on' => '2025-07-01']);
    $rot2->placements()->create(['tire_id' => $tires['T1']->id, 'from_position' => 'RL', 'to_position' => 'FL', 'tread_center' => 8]);
    $rot2->placements()->create(['tire_id' => $tires['T2']->id, 'from_position' => 'FR', 'to_position' => 'RR', 'tread_center' => 8]);
    $rot2->placements()->create(['tire_id' => $tires['T3']->id, 'from_position' => 'SP', 'to_position' => 'FR', 'tread_center' => 12]);
    $rot2->placements()->create(['tire_id' => $tires['T4']->id, 'from_position' => 'FL', 'to_position' => 'RL', 'tread_center' => 12]);
    $rot2->placements()->create(['tire_id' => $tires['T5']->id, 'from_position' => 'RR', 'to_position' => 'SP', 'tread_center' => 12]);

    return [$user, $vehicle, $tires];
}
