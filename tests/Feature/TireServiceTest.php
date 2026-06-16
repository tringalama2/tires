<?php

use App\Enums\TirePosition;
use App\Models\Placement;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\Vehicle;
use App\Services\TireService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the correct current position for each seeded tire', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();
    $service = app(TireService::class);

    $position = fn (string $label) => $service->currentPosition(
        $vehicle->tires()->where('label', $label)->firstOrFail()
    );

    expect($position('T1'))->toBe(TirePosition::FrontRight)
        ->and($position('T2'))->toBe(TirePosition::Spare)
        ->and($position('T3'))->toBe(TirePosition::RearLeft)
        ->and($position('T4'))->toBe(TirePosition::RearRight)
        ->and($position('T5'))->toBe(TirePosition::FrontLeft);
});

it('uses the setup rotation position when no real rotation exists', function () {
    $vehicle = Vehicle::factory()->create(['starting_odometer' => 1000]);
    $setupRotation = Rotation::factory()->setup()->create(['vehicle_id' => $vehicle->id, 'odometer' => 1000]);
    $tire = Tire::factory()->create(['vehicle_id' => $vehicle->id]);

    Placement::factory()->create([
        'rotation_id' => $setupRotation->id,
        'tire_id' => $tire->id,
        'from_position' => null,
        'to_position' => TirePosition::FrontLeft->value,
        'tread_center' => 12.0,
    ]);

    expect(app(TireService::class)->currentPosition($tire))->toBe(TirePosition::FrontLeft);
});

it('returns null for a tire with no placements', function () {
    $tire = Tire::factory()->create();

    expect(app(TireService::class)->currentPosition($tire))->toBeNull();
});

it('returns the most recent placement position when multiple rotations exist', function () {
    $vehicle = Vehicle::factory()->create(['starting_odometer' => 1000]);
    $tire = Tire::factory()->create(['vehicle_id' => $vehicle->id]);

    $rot1 = Rotation::factory()->create(['vehicle_id' => $vehicle->id, 'odometer' => 5000]);
    Placement::factory()->create([
        'rotation_id' => $rot1->id,
        'tire_id' => $tire->id,
        'to_position' => TirePosition::FrontLeft->value,
        'tread_center' => 10.0,
    ]);

    $rot2 = Rotation::factory()->create(['vehicle_id' => $vehicle->id, 'odometer' => 10000]);
    Placement::factory()->create([
        'rotation_id' => $rot2->id,
        'tire_id' => $tire->id,
        'to_position' => TirePosition::RearRight->value,
        'tread_center' => 9.0,
    ]);

    expect(app(TireService::class)->currentPosition($tire))->toBe(TirePosition::RearRight);
});
