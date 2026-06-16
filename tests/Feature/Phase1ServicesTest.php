<?php

use App\Enums\TirePosition;
use App\Models\Placement;
use App\Models\Tire;
use App\Models\Vehicle;
use App\Services\TireService;
use App\Services\WearReportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// TireService::currentPosition (Rule A)
// ---------------------------------------------------------------------------

it('returns the correct current position for each tire', function () {
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

// ---------------------------------------------------------------------------
// WearReportService::wearByPosition (Rule C)
// ---------------------------------------------------------------------------

it('returns wear by position in canonical order', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();
    $report = app(WearReportService::class)->wearByPosition($vehicle);

    expect($report)->toHaveCount(5);
    expect($report[0]['position'])->toBe(TirePosition::FrontLeft);
    expect($report[4]['position'])->toBe(TirePosition::Spare);
});

it('produces known-good wear rates by position', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();
    $report = app(WearReportService::class)->wearByPosition($vehicle);

    $byPos = $report->keyBy(fn ($r) => $r['position']->value);

    // Known-good from seed-data.md: FR 0.32, RL 0.26, RR 0.13, FL 0.12, SP 0.08
    expect(round($byPos['FR']['avg_wear_per_1000mi'], 2))->toBe(0.32)
        ->and(round($byPos['RL']['avg_wear_per_1000mi'], 2))->toBe(0.26)
        ->and(round($byPos['RR']['avg_wear_per_1000mi'], 2))->toBe(0.13)
        ->and(round($byPos['FL']['avg_wear_per_1000mi'], 2))->toBe(0.12)
        ->and(round($byPos['SP']['avg_wear_per_1000mi'], 2))->toBe(0.08);
});

it('ranks Front Right as the fastest-wearing position', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();
    $report = app(WearReportService::class)->wearByPosition($vehicle);

    $fastest = $report->sortByDesc('avg_wear_per_1000mi')->first();

    expect($fastest['position'])->toBe(TirePosition::FrontRight);
});

it('has spare with near-zero wear', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();
    $report = app(WearReportService::class)->wearByPosition($vehicle);

    $spare = $report->firstWhere('position', TirePosition::Spare);

    expect($spare['avg_wear_per_1000mi'])->toBeLessThan(0.15);
});

// ---------------------------------------------------------------------------
// WearReportService::wearByTire (Rule D)
// ---------------------------------------------------------------------------

it('returns one row per tire with correct current position and latest tread', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();
    $report = app(WearReportService::class)->wearByTire($vehicle)->keyBy(fn ($r) => $r['tire']->label);

    expect($report['T1']['current_position'])->toBe(TirePosition::FrontRight)
        ->and($report['T1']['latest_tread_center'])->toBe(7.0)
        ->and($report['T2']['current_position'])->toBe(TirePosition::Spare)
        ->and($report['T2']['latest_tread_center'])->toBe(6.0)
        ->and($report['T3']['current_position'])->toBe(TirePosition::RearLeft)
        ->and($report['T3']['latest_tread_center'])->toBe(12.0)
        ->and($report['T4']['current_position'])->toBe(TirePosition::RearRight)
        ->and($report['T4']['latest_tread_center'])->toBe(10.0)
        ->and($report['T5']['current_position'])->toBe(TirePosition::FrontLeft)
        ->and($report['T5']['latest_tread_center'])->toBe(9.0);
});

it('returns correct note counts per tire', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();
    $report = app(WearReportService::class)->wearByTire($vehicle)->keyBy(fn ($r) => $r['tire']->label);

    expect(count($report['T1']['notes']))->toBe(1)
        ->and(count($report['T2']['notes']))->toBe(2)
        ->and(count($report['T3']['notes']))->toBe(1)
        ->and(count($report['T4']['notes']))->toBe(0)
        ->and(count($report['T5']['notes']))->toBe(1);
});

// ---------------------------------------------------------------------------
// WearReportService::projectedReplacementMileage
// ---------------------------------------------------------------------------

it('returns null for a tire with fewer than 2 intervals', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();
    $freshTire = Tire::factory()->create(['vehicle_id' => $vehicle->id, 'label' => 'T6']);

    $result = app(WearReportService::class)->projectedReplacementMileage($freshTire);

    expect($result)->toBeNull();
});

it('returns a positive projection for tires with sufficient data', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();
    $t1 = $vehicle->tires()->where('label', 'T1')->firstOrFail();

    $result = app(WearReportService::class)->projectedReplacementMileage($t1);

    expect($result)->toBeGreaterThan(0);
});

// ---------------------------------------------------------------------------
// WearReportService::scalpingFlag
// ---------------------------------------------------------------------------

it('flags scalloping when is_cupped is true', function () {
    $service = app(WearReportService::class);
    $placement = new Placement(['is_cupped' => true]);

    expect($service->scalpingFlag($placement))->toBeTrue();
});

it('does not flag scalloping when is_cupped is false', function () {
    $service = app(WearReportService::class);
    $placement = new Placement(['is_cupped' => false]);

    expect($service->scalpingFlag($placement))->toBeFalse();
});
