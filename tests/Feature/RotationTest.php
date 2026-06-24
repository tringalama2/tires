<?php

use App\Enums\TirePosition;
use App\Enums\TireStatus;
use App\Models\Placement;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\Vehicle;
use App\Services\RotationService;
use App\Services\WearReportService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Build a minimal vehicle + 5-tire setup rotation, return the vehicle.
 */
function vehicleWithSetup(): Vehicle
{
    $vehicle = Vehicle::factory()->create(['starting_odometer' => 1000]);
    $setupRotation = Rotation::factory()->create(['vehicle_id' => $vehicle->id, 'odometer' => 1000, 'is_setup' => true]);

    $positions = ['FL', 'FR', 'RL', 'RR', 'SP'];
    foreach ($positions as $pos) {
        $tire = Tire::factory()->create(['vehicle_id' => $vehicle->id]);
        Placement::factory()->create([
            'rotation_id' => $setupRotation->id,
            'tire_id' => $tire->id,
            'from_position' => null,
            'to_position' => $pos,
            'tread_center' => 10.0,
        ]);
    }

    return $vehicle;
}

/**
 * Build a valid placements payload (identity rotation: every tire stays in place).
 */
function identityPlacements(Vehicle $vehicle, float $tread = 8.0): array
{
    $service = app(RotationService::class);
    $stubs = $service->startNext($vehicle);

    return array_map(fn ($stub) => [
        'tire_id' => $stub['tire']->id,
        'from_position' => $stub['from_position']->value,
        'to_position' => $stub['from_position']->value,
        'tread_center' => $tread,
        'tread_inner' => null,
        'tread_outer' => null,
        'note' => null,
    ], $stubs);
}

// ---------------------------------------------------------------------------
// RotationService::startNext
// ---------------------------------------------------------------------------

it('startNext returns stubs ordered by canonical position', function () {
    $vehicle = vehicleWithSetup();

    $stubs = app(RotationService::class)->startNext($vehicle);

    expect($stubs)->toHaveCount(5)
        ->and($stubs[0]['from_position'])->toBe(TirePosition::FrontLeft)
        ->and($stubs[1]['from_position'])->toBe(TirePosition::FrontRight)
        ->and($stubs[2]['from_position'])->toBe(TirePosition::RearLeft)
        ->and($stubs[3]['from_position'])->toBe(TirePosition::RearRight)
        ->and($stubs[4]['from_position'])->toBe(TirePosition::Spare);
});

it('startNext pre-fills correct tires at their current positions', function () {
    // vehicleWithHistory: after rot2 — T1@FL, T2@RR, T3@FR, T4@RL, T5@SP
    [, $vehicle] = vehicleWithHistory();

    $byPos = collect(app(RotationService::class)->startNext($vehicle))
        ->keyBy(fn ($s) => $s['from_position']->value);

    expect($byPos['FL']['tire']->label)->toBe('T1')
        ->and($byPos['FR']['tire']->label)->toBe('T3')
        ->and($byPos['RL']['tire']->label)->toBe('T4')
        ->and($byPos['RR']['tire']->label)->toBe('T2')
        ->and($byPos['SP']['tire']->label)->toBe('T5');
});

it('startNext includes last tread center as hint', function () {
    // vehicleWithHistory: T1@FL last tread = 8
    [, $vehicle] = vehicleWithHistory();

    $byPos = collect(app(RotationService::class)->startNext($vehicle))
        ->keyBy(fn ($s) => $s['from_position']->value);

    expect($byPos['FL']['last_tread_center'])->toBe(8.0);
});

it('startNext excludes retired tires', function () {
    $vehicle = vehicleWithSetup();
    $tire = $vehicle->activeTires()->first();
    $tire->update(['status' => TireStatus::Retired]);

    $stubs = app(RotationService::class)->startNext($vehicle);

    $tireIds = array_column($stubs, 'tire');
    expect(collect($tireIds)->pluck('id')->contains($tire->id))->toBeFalse();
});

// ---------------------------------------------------------------------------
// RotationService::validatePermutation
// ---------------------------------------------------------------------------

it('accepts a valid permutation', function () {
    expect(app(RotationService::class)
        ->validatePermutation(['FL', 'FR', 'RL', 'RR', 'SP'], ['FR', 'RL', 'RR', 'SP', 'FL'])
    )->toBeTrue();
});

it('rejects duplicate to_positions', function () {
    expect(app(RotationService::class)
        ->validatePermutation(['FL', 'FR', 'RL', 'RR', 'SP'], ['FR', 'FR', 'RL', 'RR', 'SP'])
    )->toBeFalse();
});

it('rejects missing positions', function () {
    expect(app(RotationService::class)
        ->validatePermutation(['FL', 'FR', 'RL', 'RR', 'SP'], ['FL', 'FR', 'RL', 'RR'])
    )->toBeFalse();
});

// ---------------------------------------------------------------------------
// RotationService::save — new rotation
// ---------------------------------------------------------------------------

it('save creates a rotation and 5 placements', function () {
    $vehicle = vehicleWithSetup();
    $placements = identityPlacements($vehicle);
    $before = Rotation::where('is_setup', false)->count();

    $rotation = app(RotationService::class)->save([
        'rotated_on' => '2026-12-01',
        'odometer' => 5000,
        'note' => null,
        'rotation_id' => null,
        'placements' => $placements,
    ], $vehicle);

    expect(Rotation::where('is_setup', false)->count())->toBe($before + 1)
        ->and($rotation->placements()->count())->toBe(5)
        ->and($rotation->is_setup)->toBeFalse();
});

it('save rejects an invalid permutation', function () {
    $vehicle = vehicleWithSetup();
    $placements = identityPlacements($vehicle);

    foreach ($placements as &$p) {
        $p['to_position'] = 'FL';
    }

    expect(fn () => app(RotationService::class)->save([
        'rotated_on' => '2026-12-01',
        'odometer' => 5000,
        'note' => null,
        'rotation_id' => null,
        'placements' => $placements,
    ], $vehicle))->toThrow(ValidationException::class);
});

it('save rejects odometer not greater than previous rotation', function () {
    // vehicleWithHistory: last real rotation at 60,000 — so 50,000 should be rejected.
    [, $vehicle] = vehicleWithHistory();
    $placements = identityPlacements($vehicle);

    expect(fn () => app(RotationService::class)->save([
        'rotated_on' => '2026-12-01',
        'odometer' => 50000,
        'note' => null,
        'rotation_id' => null,
        'placements' => $placements,
    ], $vehicle))->toThrow(ValidationException::class);
});

it('first placement after setup has no wear interval', function () {
    $vehicle = vehicleWithSetup();
    $placements = identityPlacements($vehicle);

    app(RotationService::class)->save([
        'rotated_on' => '2026-12-01',
        'odometer' => 5000,
        'note' => null,
        'rotation_id' => null,
        'placements' => $placements,
    ], $vehicle);

    $report = app(WearReportService::class)->wearByPosition($vehicle);

    foreach ($report as $row) {
        expect($row['intervals'])->toBe(0);
    }
});

// ---------------------------------------------------------------------------
// RotationService::save — persists condition and wear flags
// ---------------------------------------------------------------------------

it('save persists tire condition flags and placement wear flags', function () {
    $vehicle = vehicleWithSetup();
    $stubs = app(RotationService::class)->startNext($vehicle);
    $flTire = collect($stubs)->firstWhere('from_position', TirePosition::FrontLeft)['tire'];

    $placements = array_map(fn ($stub) => [
        'tire_id' => $stub['tire']->id,
        'from_position' => $stub['from_position']->value,
        'to_position' => $stub['from_position']->value,
        'tread_center' => 8.0,
        'tread_inner' => null,
        'tread_outer' => null,
        'note' => null,
        'tire_flags' => [
            'has_cracking' => $stub['from_position'] === TirePosition::FrontLeft,
            'has_bulge' => false,
            'has_cupping' => false,
            'has_puncture_repair' => false,
        ],
        'is_feathering' => $stub['from_position'] === TirePosition::FrontLeft,
        'is_cupped' => false,
    ], $stubs);

    app(RotationService::class)->save([
        'rotated_on' => '2026-06-15',
        'odometer' => 5000,
        'note' => null,
        'rotation_id' => null,
        'placements' => $placements,
    ], $vehicle);

    expect($flTire->fresh()->has_cracking)->toBeTrue()
        ->and($flTire->fresh()->has_bulge)->toBeFalse();

    $placement = Placement::where('tire_id', $flTire->id)
        ->whereHas('rotation', fn ($q) => $q->where('odometer', 5000))
        ->first();

    expect($placement->is_feathering)->toBeTrue()
        ->and($placement->is_cupped)->toBeFalse();
});

it('save works without tire_flags', function () {
    $vehicle = vehicleWithSetup();
    $placements = identityPlacements($vehicle);

    $rotation = app(RotationService::class)->save([
        'rotated_on' => '2026-06-15',
        'odometer' => 5000,
        'note' => null,
        'rotation_id' => null,
        'placements' => $placements,
    ], $vehicle);

    expect($rotation->placements()->count())->toBe(5);
});

// ---------------------------------------------------------------------------
// RotationService::save — edit existing rotation
// ---------------------------------------------------------------------------

it('save edits an existing rotation and replaces its placements', function () {
    [, $vehicle] = vehicleWithHistory();

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

// ---------------------------------------------------------------------------
// IDOR regression — save() must scope rotation/tire lookups to the given vehicle
// ---------------------------------------------------------------------------

it('save refuses to edit a rotation belonging to a different vehicle', function () {
    [, $vehicleA] = vehicleWithHistory();
    [, $vehicleB] = vehicleWithHistory();

    $foreignRotation = $vehicleB->rotations()->where('is_setup', false)->orderByDesc('odometer')->first();
    $placements = identityPlacements($vehicleA);

    expect(fn () => app(RotationService::class)->save([
        'rotated_on' => '2026-12-01',
        'odometer' => 5000,
        'note' => 'hijacked',
        'rotation_id' => $foreignRotation->id,
        'placements' => $placements,
    ], $vehicleA))->toThrow(ModelNotFoundException::class);

    expect($foreignRotation->fresh()->note)->not->toBe('hijacked');
});

it('save refuses to attach a placement or flags to a tire belonging to a different vehicle', function () {
    [, $vehicleA] = vehicleWithHistory();
    [, , $tiresB] = vehicleWithHistory();
    $foreignTire = $tiresB['T1'];

    $placements = identityPlacements($vehicleA);
    $placements[0]['tire_id'] = $foreignTire->id;
    $placements[0]['tire_flags'] = ['has_cracking' => true];

    expect(fn () => app(RotationService::class)->save([
        'rotated_on' => '2026-12-01',
        'odometer' => 65000,
        'note' => null,
        'rotation_id' => null,
        'placements' => $placements,
    ], $vehicleA))->toThrow(ModelNotFoundException::class);

    expect($foreignTire->fresh()->has_cracking)->toBeFalse();
});
