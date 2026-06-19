<?php

use App\Enums\TirePosition;
use App\Enums\TireStatus;
use App\Models\Placement;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\Vehicle;
use App\Services\WearReportService;
use Livewire\Livewire;

// ---------------------------------------------------------------------------
// wearByPosition
// ---------------------------------------------------------------------------

it('returns wear by position in canonical order', function () {
    [, $vehicle] = vehicleWithHistory();

    $report = app(WearReportService::class)->wearByPosition($vehicle);

    expect($report)->toHaveCount(5)
        ->and($report[0]['position'])->toBe(TirePosition::FrontLeft)
        ->and($report[4]['position'])->toBe(TirePosition::Spare);
});

it('produces correct wear rates for controlled factory data', function () {
    // vehicleWithHistory: FR wears 4/32" twice over 5k mi each = 0.8/1k avg. SP wears 0.
    [, $vehicle] = vehicleWithHistory();

    $byPos = app(WearReportService::class)
        ->wearByPosition($vehicle)
        ->keyBy(fn ($r) => $r['position']->value);

    expect(round($byPos['FR']['avg_wear_per_1000mi'], 2))->toBe(0.80)
        ->and($byPos['SP']['avg_wear_per_1000mi'])->toBe(0.0);
});

it('ranks Front Right as the fastest-wearing position', function () {
    [, $vehicle] = vehicleWithHistory();

    $fastest = app(WearReportService::class)
        ->wearByPosition($vehicle)
        ->sortByDesc('avg_wear_per_1000mi')
        ->first();

    expect($fastest['position'])->toBe(TirePosition::FrontRight);
});

it('records near-zero wear for the spare', function () {
    [, $vehicle] = vehicleWithHistory();

    $spare = app(WearReportService::class)
        ->wearByPosition($vehicle)
        ->firstWhere('position', TirePosition::Spare);

    expect($spare['avg_wear_per_1000mi'])->toBe(0.0);
});

it('returns null avg_wear when no intervals exist for a position', function () {
    $vehicle = Vehicle::factory()->create();

    $report = app(WearReportService::class)->wearByPosition($vehicle);

    foreach ($report as $row) {
        expect($row['avg_wear_per_1000mi'])->toBeNull();
    }
});

it('normalizes wear rate correctly to per-1000-miles', function () {
    $vehicle = Vehicle::factory()->create(['starting_odometer' => 0]);
    $tire = Tire::factory()->create(['vehicle_id' => $vehicle->id]);

    // Two real rotations produce one interval. Wear attributed to $curr->from_position.
    $rot1 = Rotation::factory()->create(['vehicle_id' => $vehicle->id, 'odometer' => 5000]);
    Placement::factory()->create([
        'rotation_id' => $rot1->id,
        'tire_id' => $tire->id,
        'from_position' => 'FL',
        'to_position' => 'FR',
        'tread_center' => 10.0,
    ]);

    $rot2 = Rotation::factory()->create(['vehicle_id' => $vehicle->id, 'odometer' => 10000]);
    Placement::factory()->create([
        'rotation_id' => $rot2->id,
        'tire_id' => $tire->id,
        'from_position' => 'FR',
        'to_position' => 'FL',
        'tread_center' => 8.0,
    ]);

    $byPos = app(WearReportService::class)
        ->wearByPosition($vehicle)
        ->keyBy(fn ($r) => $r['position']->value);

    // Interval: rot1→rot2, wore 2/32" over 5000mi = 0.4/1k, attributed to FR
    expect(round($byPos['FR']['avg_wear_per_1000mi'], 2))->toBe(0.40);
    expect($byPos['FL']['avg_wear_per_1000mi'])->toBeNull();
});

// ---------------------------------------------------------------------------
// wearByTire
// ---------------------------------------------------------------------------

it('returns one row per tire with correct current position and latest tread', function () {
    // vehicleWithHistory: after rot2 — T1@FL(8), T2@RR(8), T3@FR(12), T4@RL(12), T5@SP(12)
    [, $vehicle] = vehicleWithHistory();

    $report = app(WearReportService::class)
        ->wearByTire($vehicle)
        ->keyBy(fn ($r) => $r['tire']->label);

    expect($report['T1']['current_position'])->toBe(TirePosition::FrontLeft)
        ->and($report['T1']['latest_tread_center'])->toBe(8.0)
        ->and($report['T3']['current_position'])->toBe(TirePosition::FrontRight)
        ->and($report['T3']['latest_tread_center'])->toBe(12.0);
});

it('returns correct note counts per tire', function () {
    $vehicle = Vehicle::factory()->create(['tire_count' => 2, 'starting_odometer' => 1000]);
    $tireWithNote = Tire::factory()->for($vehicle)->create();
    $tireNoNote = Tire::factory()->for($vehicle)->create();

    $setup = Rotation::factory()->setup()->for($vehicle)->create(['odometer' => 1000]);
    $setup->placements()->create(['tire_id' => $tireWithNote->id, 'from_position' => null, 'to_position' => 'FL', 'tread_center' => 12]);
    $setup->placements()->create(['tire_id' => $tireNoNote->id, 'from_position' => null, 'to_position' => 'FR', 'tread_center' => 12]);

    $rot = Rotation::factory()->for($vehicle)->create(['odometer' => 5000]);
    $rot->placements()->create(['tire_id' => $tireWithNote->id, 'from_position' => 'FL', 'to_position' => 'FR', 'tread_center' => 11, 'note' => 'slight wear']);
    $rot->placements()->create(['tire_id' => $tireNoNote->id, 'from_position' => 'FR', 'to_position' => 'FL', 'tread_center' => 12, 'note' => null]);

    $report = app(WearReportService::class)->wearByTire($vehicle)->keyBy(fn ($r) => $r['tire']->id);

    expect(count($report[$tireWithNote->id]['notes']))->toBe(1)
        ->and(count($report[$tireNoNote->id]['notes']))->toBe(0);
});

it('includes projected_miles for tires with at least two wear intervals', function () {
    // Need three real rotations so a tire has 2 intervals.
    $vehicle = Vehicle::factory()->create(['starting_odometer' => 0]);
    $tire = Tire::factory()->for($vehicle)->create();

    $setup = Rotation::factory()->setup()->for($vehicle)->create(['odometer' => 0]);
    $setup->placements()->create(['tire_id' => $tire->id, 'from_position' => null, 'to_position' => 'FR', 'tread_center' => 12]);

    $rot1 = Rotation::factory()->for($vehicle)->create(['odometer' => 5000]);
    $rot1->placements()->create(['tire_id' => $tire->id, 'from_position' => 'FR', 'to_position' => 'FR', 'tread_center' => 10]);

    $rot2 = Rotation::factory()->for($vehicle)->create(['odometer' => 10000]);
    $rot2->placements()->create(['tire_id' => $tire->id, 'from_position' => 'FR', 'to_position' => 'FR', 'tread_center' => 8]);

    $rot3 = Rotation::factory()->for($vehicle)->create(['odometer' => 15000]);
    $rot3->placements()->create(['tire_id' => $tire->id, 'from_position' => 'FR', 'to_position' => 'FR', 'tread_center' => 6]);

    $report = app(WearReportService::class)->wearByTire($vehicle)->first();

    expect($report)->toHaveKey('projected_miles')
        ->and($report['projected_miles'])->toBeGreaterThan(0);
});

it('exposes latest_is_cupped from the most recent placement', function () {
    [, $vehicle, $tires] = vehicleWithHistory();

    $latestPlacement = $tires['T1']->placements()
        ->join('rotations', 'rotations.id', '=', 'placements.rotation_id')
        ->where('rotations.is_setup', false)
        ->orderByDesc('rotations.odometer')
        ->first(['placements.*']);

    $latestPlacement->update(['is_cupped' => true]);

    $report = app(WearReportService::class)
        ->wearByTire($vehicle)
        ->keyBy(fn ($r) => $r['tire']->label);

    expect($report['T1']['latest_is_cupped'])->toBeTrue()
        ->and($report['T3']['latest_is_cupped'])->toBeFalse();
});

// ---------------------------------------------------------------------------
// projectedReplacementMileage
// ---------------------------------------------------------------------------

it('returns null for a tire with fewer than 2 intervals', function () {
    // vehicleWithHistory gives each tire only 1 interval — not enough for projection.
    [, $vehicle, $tires] = vehicleWithHistory();

    expect(app(WearReportService::class)->projectedReplacementMileage($tires['T1']))->toBeNull();
});

it('returns a positive projection for a tire with at least two intervals', function () {
    $vehicle = Vehicle::factory()->create(['starting_odometer' => 0]);
    $tire = Tire::factory()->for($vehicle)->create();

    $setup = Rotation::factory()->setup()->for($vehicle)->create(['odometer' => 0]);
    $setup->placements()->create(['tire_id' => $tire->id, 'from_position' => null, 'to_position' => 'FR', 'tread_center' => 12]);

    $rot1 = Rotation::factory()->for($vehicle)->create(['odometer' => 5000]);
    $rot1->placements()->create(['tire_id' => $tire->id, 'from_position' => 'FR', 'to_position' => 'FR', 'tread_center' => 10]);

    $rot2 = Rotation::factory()->for($vehicle)->create(['odometer' => 10000]);
    $rot2->placements()->create(['tire_id' => $tire->id, 'from_position' => 'FR', 'to_position' => 'FR', 'tread_center' => 8]);

    $rot3 = Rotation::factory()->for($vehicle)->create(['odometer' => 15000]);
    $rot3->placements()->create(['tire_id' => $tire->id, 'from_position' => 'FR', 'to_position' => 'FR', 'tread_center' => 6]);

    expect(app(WearReportService::class)->projectedReplacementMileage($tire))->toBeGreaterThan(0);
});

// ---------------------------------------------------------------------------
// scalpingFlag
// ---------------------------------------------------------------------------

it('flags scalloping when is_cupped is true', function () {
    $placement = new Placement(['is_cupped' => true]);

    expect(app(WearReportService::class)->scalpingFlag($placement))->toBeTrue();
});

it('does not flag scalloping when is_cupped is false', function () {
    $placement = new Placement(['is_cupped' => false]);

    expect(app(WearReportService::class)->scalpingFlag($placement))->toBeFalse();
});

// ---------------------------------------------------------------------------
// wearByTire — filterStatus parameter
// ---------------------------------------------------------------------------

it('wearByTire with TireStatus::Active excludes retired tires', function () {
    [, $vehicle, $tires] = vehicleWithHistory();

    $tires['T1']->update(['status' => TireStatus::Retired]);

    $labels = app(WearReportService::class)
        ->wearByTire($vehicle, TireStatus::Active)
        ->pluck('tire')
        ->pluck('label')
        ->all();

    expect($labels)->not->toContain('T1');
});

it('wearByTire with TireStatus::Retired includes only retired tires', function () {
    [, $vehicle, $tires] = vehicleWithHistory();

    $tires['T1']->update(['status' => TireStatus::Retired]);

    $labels = app(WearReportService::class)
        ->wearByTire($vehicle, TireStatus::Retired)
        ->pluck('tire')
        ->pluck('label')
        ->all();

    expect($labels)->toContain('T1')
        ->and(count($labels))->toBe(1);
});

it('wearByTire with null filterStatus returns all tires', function () {
    [, $vehicle, $tires] = vehicleWithHistory();

    $tires['T1']->update(['status' => TireStatus::Retired]);

    $count = app(WearReportService::class)->wearByTire($vehicle, null)->count();

    expect($count)->toBe($vehicle->tires()->count());
});

it('dashboard replacement alerts exclude retired tires', function () {
    [$user, $vehicle, $tires] = vehicleWithHistory();
    session(['vehicle' => $vehicle]);

    $tires['T1']->update(['status' => TireStatus::Retired]);

    Livewire::actingAs($user)
        ->test('rotation-dashboard', ['vehicle_id' => $vehicle->id])
        ->assertDontSeeHtml('T1');
});
