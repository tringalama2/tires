<?php

use App\Models\Placement;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\WearReportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// WearByPosition page
// ---------------------------------------------------------------------------

it('renders the by-position report page', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();

    session(['vehicle' => $vehicle]);

    $this->actingAs($user)
        ->get(route('reports.by-position'))
        ->assertOk()
        ->assertSeeText('Wear by Position')
        ->assertSeeText('Front Right')
        ->assertSeeText('Spare');
});

it('shows known-good FR wear rate on the by-position page', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();
    session(['vehicle' => $vehicle]);

    $this->actingAs($user)
        ->get(route('reports.by-position'))
        ->assertOk()
        ->assertSeeText('0.32');
});

it('shows the fastest badge on the by-position page', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();
    session(['vehicle' => $vehicle]);

    $this->actingAs($user)
        ->get(route('reports.by-position'))
        ->assertOk()
        ->assertSeeText('fastest');
});

// ---------------------------------------------------------------------------
// WearByTire page
// ---------------------------------------------------------------------------

it('renders the by-tire report page', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();
    session(['vehicle' => $vehicle]);

    $this->actingAs($user)
        ->get(route('reports.by-tire'))
        ->assertOk()
        ->assertSeeText('Wear by Tire')
        ->assertSeeText('T1')
        ->assertSeeText('T2');
});

it('shows current positions on the by-tire page', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();
    session(['vehicle' => $vehicle]);

    $this->actingAs($user)
        ->get(route('reports.by-tire'))
        ->assertOk()
        ->assertSee('FR')   // T1's position
        ->assertSee('SP');  // T2's position
});

it('shows latest tread values on the by-tire page', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();
    session(['vehicle' => $vehicle]);

    $this->actingAs($user)
        ->get(route('reports.by-tire'))
        ->assertOk()
        ->assertSeeText('7/32"')   // T1 latest
        ->assertSeeText('6/32"');  // T2 latest
});

it('triggers scalloping flag when is_cupped is true', function () {
    $service = app(WearReportService::class);
    $placement = new Placement(['is_cupped' => true]);

    expect($service->scalpingFlag($placement))->toBeTrue();
});

it('does not flag scalloping when is_cupped is false', function () {
    $service = app(WearReportService::class);
    $placement = new Placement(['is_cupped' => false]);

    expect($service->scalpingFlag($placement))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Tire detail page
// ---------------------------------------------------------------------------

it('renders the tire detail page with rotation history', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();
    $tire = $vehicle->tires()->where('label', 'T1')->firstOrFail();
    session(['vehicle' => $vehicle]);

    $this->actingAs($user)
        ->get(route('tires.show', $tire))
        ->assertOk()
        ->assertSeeText('T1')
        ->assertSeeText('Rotation History');
});

it('shows projected replacement on tire detail page for tires with enough data', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();
    $tire = $vehicle->tires()->where('label', 'T1')->firstOrFail();
    session(['vehicle' => $vehicle]);

    $this->actingAs($user)
        ->get(route('tires.show', $tire))
        ->assertOk()
        ->assertSeeText('Projected replacement');
});

it('wearByTire includes projected_miles in output', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();
    $report = app(WearReportService::class)->wearByTire($vehicle)->keyBy(fn ($r) => $r['tire']->label);

    // T1 has 3 intervals, should produce a projection
    expect($report['T1'])->toHaveKey('projected_miles')
        ->and($report['T1']['projected_miles'])->toBeGreaterThan(0);

    // T4 also has intervals
    expect($report['T4']['projected_miles'])->toBeGreaterThan(0);
});
