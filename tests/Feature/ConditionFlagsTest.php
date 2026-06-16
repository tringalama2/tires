<?php

use App\Models\Placement;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\RotationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Placement::isCenterWear / isEdgeWear
// ---------------------------------------------------------------------------

it('detects center wear when center is 2+ below avg(inner, outer)', function () {
    $p = new Placement(['tread_center' => 4.0, 'tread_inner' => 7.0, 'tread_outer' => 7.0]);

    expect($p->isCenterWear())->toBeTrue();
    expect($p->isEdgeWear())->toBeFalse();
});

it('detects edge wear when center is 2+ above avg(inner, outer)', function () {
    $p = new Placement(['tread_center' => 8.0, 'tread_inner' => 5.0, 'tread_outer' => 5.0]);

    expect($p->isEdgeWear())->toBeTrue();
    expect($p->isCenterWear())->toBeFalse();
});

it('does not flag center wear when difference is less than 2', function () {
    $p = new Placement(['tread_center' => 5.5, 'tread_inner' => 7.0, 'tread_outer' => 7.0]);

    expect($p->isCenterWear())->toBeFalse();
});

it('returns false for center/edge wear when inner or outer is null', function () {
    $p = new Placement(['tread_center' => 4.0, 'tread_inner' => null, 'tread_outer' => 7.0]);

    expect($p->isCenterWear())->toBeFalse();
    expect($p->isEdgeWear())->toBeFalse();
});

// ---------------------------------------------------------------------------
// RotationService::save() — tire flags + placement flags
// ---------------------------------------------------------------------------

it('persists tire condition flags and placement wear flags via save()', function () {
    $vehicle = Vehicle::factory()->create();
    $setupRotation = Rotation::factory()->create(['vehicle_id' => $vehicle->id, 'odometer' => 1000, 'is_setup' => true]);

    $tires = [];
    $positions = ['FL', 'FR', 'RL', 'RR', 'SP'];
    foreach ($positions as $pos) {
        $tire = Tire::factory()->create(['vehicle_id' => $vehicle->id]);
        Placement::factory()->create([
            'rotation_id' => $setupRotation->id,
            'tire_id' => $tire->id,
            'from_position' => $pos,
            'to_position' => $pos,
            'tread_center' => 10.0,
        ]);
        $tires[$pos] = $tire;
    }

    $placements = [];
    foreach ($positions as $pos) {
        $placements[] = [
            'tire_id' => $tires[$pos]->id,
            'from_position' => $pos,
            'to_position' => $pos,
            'tread_center' => 8.0,
            'tread_inner' => null,
            'tread_outer' => null,
            'note' => null,
            'tire_flags' => ['has_cracking' => true, 'has_bulge' => false, 'has_cupping' => false, 'has_puncture_repair' => false],
            'is_feathering' => true,
            'is_cupped' => false,
        ];
    }

    $service = app(RotationService::class);
    $service->save([
        'rotated_on' => '2026-06-15',
        'odometer' => 2000,
        'note' => null,
        'rotation_id' => null,
        'placements' => $placements,
    ], $vehicle);

    $flTire = $tires['FL']->fresh();
    expect($flTire->has_cracking)->toBeTrue();
    expect($flTire->has_bulge)->toBeFalse();

    $placement = Placement::where('tire_id', $tires['FL']->id)
        ->whereHas('rotation', fn ($q) => $q->where('odometer', 2000))
        ->first();
    expect($placement->is_feathering)->toBeTrue();
    expect($placement->is_cupped)->toBeFalse();
});

it('save() works without tire_flags (backwards compatible)', function () {
    $vehicle = Vehicle::factory()->create();
    $setupRotation = Rotation::factory()->create(['vehicle_id' => $vehicle->id, 'odometer' => 1000, 'is_setup' => true]);

    $positions = ['FL', 'FR', 'RL', 'RR', 'SP'];
    $tires = [];
    foreach ($positions as $pos) {
        $tire = Tire::factory()->create(['vehicle_id' => $vehicle->id]);
        Placement::factory()->create([
            'rotation_id' => $setupRotation->id,
            'tire_id' => $tire->id,
            'from_position' => $pos,
            'to_position' => $pos,
            'tread_center' => 10.0,
        ]);
        $tires[$pos] = $tire;
    }

    $placements = [];
    foreach ($positions as $pos) {
        $placements[] = [
            'tire_id' => $tires[$pos]->id,
            'from_position' => $pos,
            'to_position' => $pos,
            'tread_center' => 8.0,
            'tread_inner' => null,
            'tread_outer' => null,
            'note' => null,
        ];
    }

    $service = app(RotationService::class);
    $rotation = $service->save([
        'rotated_on' => '2026-06-15',
        'odometer' => 2000,
        'note' => null,
        'rotation_id' => null,
        'placements' => $placements,
    ], $vehicle);

    expect($rotation->placements()->count())->toBe(5);
});

// ---------------------------------------------------------------------------
// Tire show page — condition badges
// ---------------------------------------------------------------------------

it('shows condition badges on tire show page when flags are set', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::first() ?? User::factory()->create();
    $tire = Tire::first();
    $tire->update(['has_cracking' => true, 'has_bulge' => true]);

    $this->actingAs($user)
        ->get(route('tires.show', $tire))
        ->assertOk()
        ->assertSee('Cracking')
        ->assertSee('Bulge');
});

it('does not show condition badges when all flags are false', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::first() ?? User::factory()->create();
    $tire = Tire::first();
    $tire->update(['has_cracking' => false, 'has_bulge' => false, 'has_cupping' => false, 'has_puncture_repair' => false]);

    $this->actingAs($user)
        ->get(route('tires.show', $tire))
        ->assertOk()
        ->assertDontSee('Cracking')
        ->assertDontSee('Bulge');
});
