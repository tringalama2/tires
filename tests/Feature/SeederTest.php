<?php

use App\Enums\TirePosition;
use App\Models\Placement;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\Vehicle;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds the correct counts', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Tire::count())->toBe(5)
        ->and(Rotation::where('is_setup', false)->count())->toBe(4)
        ->and(Placement::count())->toBe(20);
});

it('seeds known-good current positions', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();

    $currentPosition = function (string $label) use ($vehicle): TirePosition {
        $tire = $vehicle->tires()->where('label', $label)->firstOrFail();

        return $tire->placements()
            ->join('rotations', 'rotations.id', 'placements.rotation_id')
            ->where('rotations.is_setup', false)
            ->orderByDesc('rotations.odometer')
            ->value('to_position');
    };

    expect($currentPosition('T1'))->toBe(TirePosition::FrontRight)
        ->and($currentPosition('T2'))->toBe(TirePosition::Spare)
        ->and($currentPosition('T3'))->toBe(TirePosition::RearLeft)
        ->and($currentPosition('T4'))->toBe(TirePosition::RearRight)
        ->and($currentPosition('T5'))->toBe(TirePosition::FrontLeft);
});

it('seeds known-good latest center tread', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();

    $latestTread = function (string $label) use ($vehicle): float {
        $tire = $vehicle->tires()->where('label', $label)->firstOrFail();

        return (float) $tire->placements()
            ->join('rotations', 'rotations.id', 'placements.rotation_id')
            ->where('rotations.is_setup', false)
            ->orderByDesc('rotations.odometer')
            ->value('tread_center');
    };

    expect($latestTread('T1'))->toBe(7.0)
        ->and($latestTread('T2'))->toBe(6.0)
        ->and($latestTread('T3'))->toBe(12.0)
        ->and($latestTread('T4'))->toBe(10.0)
        ->and($latestTread('T5'))->toBe(9.0);
});

it('seeds the correct placement note counts per tire', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();

    $noteCount = fn (string $label) => $vehicle->tires()
        ->where('label', $label)
        ->firstOrFail()
        ->placements()
        ->whereNotNull('note')
        ->count();

    expect($noteCount('T1'))->toBe(1)
        ->and($noteCount('T2'))->toBe(2)
        ->and($noteCount('T3'))->toBe(1)
        ->and($noteCount('T4'))->toBe(0)
        ->and($noteCount('T5'))->toBe(1);
});
