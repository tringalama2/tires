<?php

use App\Models\Placement;
use App\Models\Tire;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('enforces unique(rotation_id, tire_id) on placements', function () {
    $this->seed(DatabaseSeeder::class);

    $placement = Placement::first();

    expect(fn () => Placement::create([
        'rotation_id' => $placement->rotation_id,
        'tire_id' => $placement->tire_id,
        'from_position' => 'FL',
        'to_position' => 'RR',
        'tread_center' => 10,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

it('enforces unique(rotation_id, to_position) on placements', function () {
    $this->seed(DatabaseSeeder::class);

    $placement = Placement::with('rotation.placements')->first();
    $rotation = $placement->rotation;

    $usedToPosition = $placement->to_position->value;
    $unusedTire = Tire::whereNotIn('id', $rotation->placements->pluck('tire_id'))->first();

    if (! $unusedTire) {
        $this->markTestSkipped('All tires already placed in this rotation.');
    }

    expect(fn () => Placement::create([
        'rotation_id' => $rotation->id,
        'tire_id' => $unusedTire->id,
        'from_position' => 'SP',
        'to_position' => $usedToPosition,
        'tread_center' => 10,
    ]))->toThrow(UniqueConstraintViolationException::class);
});
