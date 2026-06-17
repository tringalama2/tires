<?php

use App\Models\Placement;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\Vehicle;
use Illuminate\Database\UniqueConstraintViolationException;

it('enforces unique(rotation_id, tire_id) on placements', function () {
    $vehicle = Vehicle::factory()->create();
    $tire = Tire::factory()->for($vehicle)->create();
    $rotation = Rotation::factory()->for($vehicle)->create();

    Placement::factory()->create(['rotation_id' => $rotation->id, 'tire_id' => $tire->id, 'to_position' => 'FL', 'tread_center' => 10]);

    expect(fn () => Placement::create([
        'rotation_id' => $rotation->id,
        'tire_id' => $tire->id,
        'from_position' => 'FL',
        'to_position' => 'RR',
        'tread_center' => 10,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

it('enforces unique(rotation_id, to_position) on placements', function () {
    $vehicle = Vehicle::factory()->create();
    $rotation = Rotation::factory()->for($vehicle)->create();
    $tireA = Tire::factory()->for($vehicle)->create();
    $tireB = Tire::factory()->for($vehicle)->create();

    Placement::factory()->create(['rotation_id' => $rotation->id, 'tire_id' => $tireA->id, 'to_position' => 'FL', 'tread_center' => 10]);

    expect(fn () => Placement::create([
        'rotation_id' => $rotation->id,
        'tire_id' => $tireB->id,
        'from_position' => 'SP',
        'to_position' => 'FL',
        'tread_center' => 10,
    ]))->toThrow(UniqueConstraintViolationException::class);
});
