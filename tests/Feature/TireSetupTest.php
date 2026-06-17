<?php

use App\Enums\TirePosition;
use App\Enums\TireStatus;
use App\Models\Placement;
use App\Models\Rotation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates an is_setup rotation and placement when the first tire is added', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['user_id' => $user->id, 'starting_odometer' => 50000]);

    $this->actingAs($user)
        ->post(route('vehicles.setuptires.store', ['vehicle' => $vehicle, 'tirePosition' => 'FL']), [
            'label' => 'T1',
            'starting_tread' => 12,
        ]);

    $setupRotation = Rotation::where('vehicle_id', $vehicle->id)->where('is_setup', true)->first();

    expect(Rotation::where('vehicle_id', $vehicle->id)->where('is_setup', true)->count())->toBe(1)
        ->and($setupRotation->placements()->count())->toBe(1)
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

it('redirects away when position is already occupied in setup', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['user_id' => $user->id, 'starting_odometer' => 50000]);

    $this->actingAs($user)
        ->post(route('vehicles.setuptires.store', ['vehicle' => $vehicle, 'tirePosition' => 'FL']), [
            'label' => 'T1', 'starting_tread' => 12,
        ]);

    $this->actingAs($user)
        ->get(route('vehicles.setuptires.create', ['vehicle' => $vehicle, 'tirePosition' => 'FL']))
        ->assertRedirect(route('vehicles.setuptires.index', $vehicle));
});

// --- Retire-tire middleware regression ---
// Bug: ActiveVehicleTiresMiddleware counted active (non-retired) tires against
// tire_count. Retiring any tire dropped the count below tire_count and sent the
// user into an infinite redirect loop on the setup page.
// Fix: middleware must count filled setup-rotation positions, not tire status.

it('does not redirect to setup when a tire is retired but all positions are filled', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['user_id' => $user->id, 'tire_count' => 5, 'starting_odometer' => 50000]);

    // Seed all 5 positions via the setup controller so a real setup rotation exists.
    $positions = ['FL', 'FR', 'RL', 'RR', 'SP'];
    foreach ($positions as $i => $pos) {
        $this->actingAs($user)
            ->post(route('vehicles.setuptires.store', ['vehicle' => $vehicle, 'tirePosition' => $pos]), [
                'label' => 'T'.($i + 1), 'starting_tread' => 10,
            ]);
    }

    // Retire one tire.
    $vehicle->tires()->first()->update(['status' => TireStatus::Retired]);

    // Dashboard is behind activeVehicleTires — must not redirect to setup.
    $this->actingAs($user)
        ->get(route('dashboard', $vehicle))
        ->assertOk();
});

it('does redirect to setup when a position has no tire at all', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['user_id' => $user->id, 'tire_count' => 5, 'starting_odometer' => 50000]);

    // Only 4 of 5 positions filled.
    $positions = ['FL', 'FR', 'RL', 'RR'];
    foreach ($positions as $i => $pos) {
        $this->actingAs($user)
            ->post(route('vehicles.setuptires.store', ['vehicle' => $vehicle, 'tirePosition' => $pos]), [
                'label' => 'T'.($i + 1), 'starting_tread' => 10,
            ]);
    }

    $this->actingAs($user)
        ->get(route('dashboard', $vehicle))
        ->assertRedirect(route('vehicles.setuptires.index', $vehicle));
});
