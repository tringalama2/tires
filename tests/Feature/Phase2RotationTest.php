<?php

use App\Enums\TirePosition;
use App\Models\Rotation;
use App\Models\Vehicle;
use App\Services\RotationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// RotationService::startNext (Rule E auto-seed)
// ---------------------------------------------------------------------------

it('startNext returns stubs ordered by canonical position', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();
    $stubs = app(RotationService::class)->startNext($vehicle);

    expect($stubs)->toHaveCount(5);
    expect($stubs[0]['from_position'])->toBe(TirePosition::FrontLeft);
    expect($stubs[1]['from_position'])->toBe(TirePosition::FrontRight);
    expect($stubs[2]['from_position'])->toBe(TirePosition::RearLeft);
    expect($stubs[3]['from_position'])->toBe(TirePosition::RearRight);
    expect($stubs[4]['from_position'])->toBe(TirePosition::Spare);
});

it('startNext pre-fills correct tires at their current positions', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();
    $stubs = app(RotationService::class)->startNext($vehicle);

    $byPos = collect($stubs)->keyBy(fn ($s) => $s['from_position']->value);

    // Known-good from seed data: T5→FL, T1→FR, T3→RL, T4→RR, T2→SP
    expect($byPos['FL']['tire']->label)->toBe('T5')
        ->and($byPos['FR']['tire']->label)->toBe('T1')
        ->and($byPos['RL']['tire']->label)->toBe('T3')
        ->and($byPos['RR']['tire']->label)->toBe('T4')
        ->and($byPos['SP']['tire']->label)->toBe('T2');
});

it('startNext includes last tread center as hint', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();
    $stubs = app(RotationService::class)->startNext($vehicle);

    $byPos = collect($stubs)->keyBy(fn ($s) => $s['from_position']->value);

    // T5 at FL, latest tread = 9
    expect($byPos['FL']['last_tread_center'])->toBe(9.0);
});

// ---------------------------------------------------------------------------
// RotationService::validatePermutation
// ---------------------------------------------------------------------------

it('accepts a valid permutation', function () {
    $service = app(RotationService::class);

    expect($service->validatePermutation(['FL', 'FR', 'RL', 'RR', 'SP'], ['FR', 'RL', 'RR', 'SP', 'FL']))->toBeTrue();
});

it('rejects duplicate to_positions', function () {
    $service = app(RotationService::class);

    expect($service->validatePermutation(['FL', 'FR', 'RL', 'RR', 'SP'], ['FR', 'FR', 'RL', 'RR', 'SP']))->toBeFalse();
});

it('rejects missing positions', function () {
    $service = app(RotationService::class);

    expect($service->validatePermutation(['FL', 'FR', 'RL', 'RR', 'SP'], ['FL', 'FR', 'RL', 'RR']))->toBeFalse();
});

// ---------------------------------------------------------------------------
// RotationService::save — new rotation
// ---------------------------------------------------------------------------

it('save creates a rotation and 5 placements', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();
    $stubs = app(RotationService::class)->startNext($vehicle);

    $placements = [];
    $toPositions = ['FL' => 'RL', 'FR' => 'FL', 'RL' => 'SP', 'RR' => 'FR', 'SP' => 'RR'];

    foreach ($stubs as $stub) {
        $from = $stub['from_position']->value;
        $placements[] = [
            'tire_id' => $stub['tire']->id,
            'from_position' => $from,
            'to_position' => $toPositions[$from],
            'tread_center' => 8.0,
        ];
    }

    $beforeCount = Rotation::where('is_setup', false)->count();

    $rotation = app(RotationService::class)->save([
        'rotated_on' => '2026-12-01',
        'odometer' => 125000,
        'note' => null,
        'rotation_id' => null,
        'placements' => $placements,
    ], $vehicle);

    expect(Rotation::where('is_setup', false)->count())->toBe($beforeCount + 1)
        ->and($rotation->placements()->count())->toBe(5)
        ->and($rotation->is_setup)->toBeFalse();
});

it('save rejects invalid permutation', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();
    $stubs = app(RotationService::class)->startNext($vehicle);

    $placements = [];
    foreach ($stubs as $stub) {
        $from = $stub['from_position']->value;
        $placements[] = [
            'tire_id' => $stub['tire']->id,
            'from_position' => $from,
            'to_position' => 'FL', // all going to FL — invalid
            'tread_center' => 8.0,
        ];
    }

    expect(fn () => app(RotationService::class)->save([
        'rotated_on' => '2026-12-01',
        'odometer' => 125000,
        'note' => null,
        'rotation_id' => null,
        'placements' => $placements,
    ], $vehicle))->toThrow(ValidationException::class);
});

it('save rejects odometer not greater than previous', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();
    $stubs = app(RotationService::class)->startNext($vehicle);

    $placements = [];
    $toPositions = ['FL' => 'RL', 'FR' => 'FL', 'RL' => 'SP', 'RR' => 'FR', 'SP' => 'RR'];
    foreach ($stubs as $stub) {
        $from = $stub['from_position']->value;
        $placements[] = [
            'tire_id' => $stub['tire']->id,
            'from_position' => $from,
            'to_position' => $toPositions[$from],
            'tread_center' => 8.0,
        ];
    }

    expect(fn () => app(RotationService::class)->save([
        'rotated_on' => '2026-12-01',
        'odometer' => 100000, // less than max (120495)
        'note' => null,
        'rotation_id' => null,
        'placements' => $placements,
    ], $vehicle))->toThrow(ValidationException::class);
});

// ---------------------------------------------------------------------------
// RotationService::save — edit existing rotation
// ---------------------------------------------------------------------------

it('save edits an existing rotation and replaces its placements', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();
    $rotation = $vehicle->rotations()->where('is_setup', false)->orderByDesc('odometer')->first();
    $originalId = $rotation->id;
    $originalCount = Rotation::where('is_setup', false)->count();

    $stubs = $rotation->placements->map(fn ($p) => [
        'tire_id' => $p->tire_id,
        'from_position' => $p->from_position->value,
        'to_position' => $p->to_position->value,
        'tread_center' => 7.5,
    ])->values()->all();

    app(RotationService::class)->save([
        'rotated_on' => $rotation->rotated_on->toDateString(),
        'odometer' => $rotation->odometer,
        'note' => 'edited',
        'rotation_id' => $originalId,
        'placements' => $stubs,
    ], $vehicle);

    expect(Rotation::where('is_setup', false)->count())->toBe($originalCount);

    $updated = Rotation::find($originalId);
    expect($updated->note)->toBe('edited')
        ->and($updated->placements()->count())->toBe(5);
});
