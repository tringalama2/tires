<?php

use App\Enums\TirePosition;
use App\Models\Placement;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\Vehicle;
use App\Services\WearReportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// wearByPosition
// ---------------------------------------------------------------------------

it('returns wear by position in canonical order', function () {
    $this->seed(DatabaseSeeder::class);

    $report = app(WearReportService::class)->wearByPosition(Vehicle::first());

    expect($report)->toHaveCount(5)
        ->and($report[0]['position'])->toBe(TirePosition::FrontLeft)
        ->and($report[4]['position'])->toBe(TirePosition::Spare);
});

it('produces known-good wear rates by position', function () {
    $this->seed(DatabaseSeeder::class);

    $byPos = app(WearReportService::class)
        ->wearByPosition(Vehicle::first())
        ->keyBy(fn ($r) => $r['position']->value);

    expect(round($byPos['FR']['avg_wear_per_1000mi'], 2))->toBe(0.32)
        ->and(round($byPos['RL']['avg_wear_per_1000mi'], 2))->toBe(0.26)
        ->and(round($byPos['RR']['avg_wear_per_1000mi'], 2))->toBe(0.13)
        ->and(round($byPos['FL']['avg_wear_per_1000mi'], 2))->toBe(0.12)
        ->and(round($byPos['SP']['avg_wear_per_1000mi'], 2))->toBe(0.08);
});

it('ranks Front Right as the fastest-wearing position', function () {
    $this->seed(DatabaseSeeder::class);

    $fastest = app(WearReportService::class)
        ->wearByPosition(Vehicle::first())
        ->sortByDesc('avg_wear_per_1000mi')
        ->first();

    expect($fastest['position'])->toBe(TirePosition::FrontRight);
});

it('records near-zero wear for the spare', function () {
    $this->seed(DatabaseSeeder::class);

    $spare = app(WearReportService::class)
        ->wearByPosition(Vehicle::first())
        ->firstWhere('position', TirePosition::Spare);

    expect($spare['avg_wear_per_1000mi'])->toBeLessThan(0.15);
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

    // buildIntervals only considers non-setup placements, so we need two real rotations
    // for an interval to exist. Wear is attributed to $curr->from_position.
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

    // One interval: rot1→rot2, wear = 10−8 = 2 over 5000mi = 0.4/1000mi, attributed to FR
    expect(round($byPos['FR']['avg_wear_per_1000mi'], 2))->toBe(0.40);
    expect($byPos['FL']['avg_wear_per_1000mi'])->toBeNull(); // no interval at FL yet
});

// ---------------------------------------------------------------------------
// wearByTire
// ---------------------------------------------------------------------------

it('returns one row per tire with correct current position and latest tread', function () {
    $this->seed(DatabaseSeeder::class);

    $report = app(WearReportService::class)
        ->wearByTire(Vehicle::first())
        ->keyBy(fn ($r) => $r['tire']->label);

    expect($report['T1']['current_position'])->toBe(TirePosition::FrontRight)
        ->and($report['T1']['latest_tread_center'])->toBe(7.0)
        ->and($report['T2']['current_position'])->toBe(TirePosition::Spare)
        ->and($report['T2']['latest_tread_center'])->toBe(6.0);
});

it('returns correct note counts per tire', function () {
    $this->seed(DatabaseSeeder::class);

    $report = app(WearReportService::class)
        ->wearByTire(Vehicle::first())
        ->keyBy(fn ($r) => $r['tire']->label);

    expect(count($report['T1']['notes']))->toBe(1)
        ->and(count($report['T2']['notes']))->toBe(2)
        ->and(count($report['T4']['notes']))->toBe(0);
});

it('includes projected_miles for tires with sufficient data', function () {
    $this->seed(DatabaseSeeder::class);

    $report = app(WearReportService::class)
        ->wearByTire(Vehicle::first())
        ->keyBy(fn ($r) => $r['tire']->label);

    expect($report['T1'])->toHaveKey('projected_miles')
        ->and($report['T1']['projected_miles'])->toBeGreaterThan(0);
});

it('exposes latest_is_cupped from the most recent placement', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();
    $t1 = $vehicle->tires()->where('label', 'T1')->first();
    $latestPlacement = $t1->placements()
        ->join('rotations', 'rotations.id', '=', 'placements.rotation_id')
        ->where('rotations.is_setup', false)
        ->orderByDesc('rotations.odometer')
        ->first(['placements.*']);

    $latestPlacement->update(['is_cupped' => true]);

    $report = app(WearReportService::class)
        ->wearByTire($vehicle)
        ->keyBy(fn ($r) => $r['tire']->label);

    expect($report['T1']['latest_is_cupped'])->toBeTrue()
        ->and($report['T2']['latest_is_cupped'])->toBeFalse();
});

// ---------------------------------------------------------------------------
// projectedReplacementMileage
// ---------------------------------------------------------------------------

it('returns null for a tire with fewer than 2 intervals', function () {
    $this->seed(DatabaseSeeder::class);

    $freshTire = Tire::factory()->create(['vehicle_id' => Vehicle::first()->id]);

    expect(app(WearReportService::class)->projectedReplacementMileage($freshTire))->toBeNull();
});

it('returns a positive projection for tires with sufficient data', function () {
    $this->seed(DatabaseSeeder::class);

    $t1 = Vehicle::first()->tires()->where('label', 'T1')->firstOrFail();

    expect(app(WearReportService::class)->projectedReplacementMileage($t1))->toBeGreaterThan(0);
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
