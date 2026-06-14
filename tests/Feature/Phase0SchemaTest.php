<?php

use App\Enums\TirePosition;
use App\Enums\TireStatus;
use App\Models\Placement;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Seeder: counts and known-good state
// ---------------------------------------------------------------------------

it('seeds the correct counts', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Tire::count())->toBe(5)
        ->and(Rotation::where('is_setup', false)->count())->toBe(4)
        ->and(Placement::count())->toBe(20);
});

it('seeds known-good current positions', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();

    $currentPosition = function (string $label) use ($vehicle): TirePosition {
        $tire = $vehicle->tires()->where('label', $label)->firstOrFail();

        return $tire->placements()
            ->join('rotations', 'rotations.id', 'placements.rotation_id')
            ->where('rotations.is_setup', false)
            ->orderByDesc('rotations.odometer')
            ->value('to_position');
    };

    expect($currentPosition('T1'))->toBe(TirePosition::FrontRight)
        ->and($currentPosition('T2'))->toBe(TirePosition::Spare)
        ->and($currentPosition('T3'))->toBe(TirePosition::RearLeft)
        ->and($currentPosition('T4'))->toBe(TirePosition::RearRight)
        ->and($currentPosition('T5'))->toBe(TirePosition::FrontLeft);
});

it('seeds known-good latest center tread', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();

    $latestTread = function (string $label) use ($vehicle): float {
        $tire = $vehicle->tires()->where('label', $label)->firstOrFail();

        return (float) $tire->placements()
            ->join('rotations', 'rotations.id', 'placements.rotation_id')
            ->where('rotations.is_setup', false)
            ->orderByDesc('rotations.odometer')
            ->value('tread_center');
    };

    expect($latestTread('T1'))->toBe(7.0)
        ->and($latestTread('T2'))->toBe(6.0)
        ->and($latestTread('T3'))->toBe(12.0)
        ->and($latestTread('T4'))->toBe(10.0)
        ->and($latestTread('T5'))->toBe(9.0);
});

it('seeds the correct placement note counts per tire', function () {
    $this->seed(DatabaseSeeder::class);

    $vehicle = Vehicle::first();

    $noteCount = fn (string $label) => $vehicle->tires()
        ->where('label', $label)
        ->firstOrFail()
        ->placements()
        ->whereNotNull('note')
        ->count();

    expect($noteCount('T1'))->toBe(1)
        ->and($noteCount('T2'))->toBe(2)
        ->and($noteCount('T3'))->toBe(1)
        ->and($noteCount('T4'))->toBe(0)
        ->and($noteCount('T5'))->toBe(1);
});

// ---------------------------------------------------------------------------
// Schema constraints
// ---------------------------------------------------------------------------

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

// ---------------------------------------------------------------------------
// TirePosition enum
// ---------------------------------------------------------------------------

it('has string-backed TirePosition values', function () {
    expect(TirePosition::FrontLeft->value)->toBe('FL')
        ->and(TirePosition::FrontRight->value)->toBe('FR')
        ->and(TirePosition::RearLeft->value)->toBe('RL')
        ->and(TirePosition::RearRight->value)->toBe('RR')
        ->and(TirePosition::Spare->value)->toBe('SP');
});

it('returns positions in canonical order', function () {
    $order = TirePosition::order();

    expect($order)->toHaveCount(5)
        ->and($order[0])->toBe(TirePosition::FrontLeft)
        ->and($order[4])->toBe(TirePosition::Spare);
});

// ---------------------------------------------------------------------------
// TireSetupController: is_setup rotation + placement creation
// ---------------------------------------------------------------------------

it('creates an is_setup rotation and placement when a tire is added during setup', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['user_id' => $user->id, 'starting_odometer' => 50000]);

    $this->actingAs($user)
        ->post(route('vehicles.setuptires.store', ['vehicle' => $vehicle, 'tirePosition' => 'FL']), [
            'label' => 'T1',
            'starting_tread' => 12,
        ]);

    expect(Rotation::where('vehicle_id', $vehicle->id)->where('is_setup', true)->count())->toBe(1);

    $setupRotation = Rotation::where('vehicle_id', $vehicle->id)->where('is_setup', true)->first();

    expect($setupRotation->placements()->count())->toBe(1)
        ->and($setupRotation->placements()->first()->to_position)->toBe(TirePosition::FrontLeft)
        ->and($setupRotation->placements()->first()->from_position)->toBeNull()
        ->and((float) $setupRotation->placements()->first()->tread_center)->toBe(12.0);
});

it('reuses the same is_setup rotation for subsequent tires', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['user_id' => $user->id, 'starting_odometer' => 50000]);

    $this->actingAs($user)
        ->post(route('vehicles.setuptires.store', ['vehicle' => $vehicle, 'tirePosition' => 'FL']), [
            'label' => 'T1', 'starting_tread' => 12,
        ]);

    $this->actingAs($user)
        ->post(route('vehicles.setuptires.store', ['vehicle' => $vehicle, 'tirePosition' => 'FR']), [
            'label' => 'T2', 'starting_tread' => 11,
        ]);

    expect(Rotation::where('vehicle_id', $vehicle->id)->where('is_setup', true)->count())->toBe(1)
        ->and(Placement::whereHas(
            'rotation',
            fn ($q) => $q->where('vehicle_id', $vehicle->id)->where('is_setup', true)
        )->count())->toBe(2);
});

it('blocks adding a tire to a position already occupied in setup', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['user_id' => $user->id, 'starting_odometer' => 50000]);

    $this->actingAs($user)
        ->post(route('vehicles.setuptires.store', ['vehicle' => $vehicle, 'tirePosition' => 'FL']), [
            'label' => 'T1', 'starting_tread' => 12,
        ]);

    $response = $this->actingAs($user)
        ->get(route('vehicles.setuptires.create', ['vehicle' => $vehicle, 'tirePosition' => 'FL']));

    $response->assertRedirect(route('vehicles.setuptires.index', $vehicle));
});

// ---------------------------------------------------------------------------
// TireStatus
// ---------------------------------------------------------------------------

it('has simplified TireStatus with Active and Retired only', function () {
    expect(TireStatus::cases())->toHaveCount(2)
        ->and(TireStatus::Active->value)->toBe(1)
        ->and(TireStatus::Retired->value)->toBe(2);
});
