<?php

use App\Enums\TirePosition;
use App\Enums\TireStatus;
use App\Models\Placement;
use App\Models\Rotation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function setupTireAt(User $user, Vehicle $vehicle, string $position, string $label = 'T1', float $tread = 12): void
{
    Livewire::actingAs($user)
        ->test('vehicles.setuptire-create', ['vehicle' => $vehicle, 'tirePosition' => $position])
        ->set('label', $label)
        ->set('starting_tread', $tread)
        ->call('save');
}

it('creates an is_setup rotation and placement when the first tire is added', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['user_id' => $user->id, 'starting_odometer' => 50000]);

    setupTireAt($user, $vehicle, 'FL', 'T1', 12);

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

    setupTireAt($user, $vehicle, 'FL', 'T1', 12);
    setupTireAt($user, $vehicle, 'FR', 'T2', 11);

    expect(Rotation::where('vehicle_id', $vehicle->id)->where('is_setup', true)->count())->toBe(1)
        ->and(Placement::whereHas(
            'rotation',
            fn ($q) => $q->where('vehicle_id', $vehicle->id)->where('is_setup', true)
        )->count())->toBe(2);
});

it('redirects away when position is already occupied in setup', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['user_id' => $user->id, 'starting_odometer' => 50000]);

    setupTireAt($user, $vehicle, 'FL', 'T1', 12);

    Livewire::actingAs($user)
        ->test('vehicles.setuptire-create', ['vehicle' => $vehicle, 'tirePosition' => 'FL'])
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

    foreach (['FL' => 'T1', 'FR' => 'T2', 'RL' => 'T3', 'RR' => 'T4', 'SP' => 'T5'] as $pos => $label) {
        setupTireAt($user, $vehicle, $pos, $label, 10);
    }

    $vehicle->tires()->first()->update(['status' => TireStatus::Retired]);

    $this->actingAs($user)
        ->get(route('dashboard', $vehicle))
        ->assertOk();
});

it('does redirect to setup when a position has no tire at all', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['user_id' => $user->id, 'tire_count' => 5, 'starting_odometer' => 50000]);

    foreach (['FL' => 'T1', 'FR' => 'T2', 'RL' => 'T3', 'RR' => 'T4'] as $pos => $label) {
        setupTireAt($user, $vehicle, $pos, $label, 10);
    }

    $this->actingAs($user)
        ->get(route('dashboard', $vehicle))
        ->assertRedirect(route('vehicles.setuptires.index', $vehicle));
});

// ---------------------------------------------------------------------------
// IDOR regression — setuptire-create must reject another user's vehicle, and
// vehicle_id must stay #[Locked] so it can't be retargeted after mount.
// ---------------------------------------------------------------------------

it('cannot mount setuptire-create for another user\'s vehicle', function () {
    $owner = User::factory()->create();
    $vehicle = Vehicle::factory()->for($owner)->create(['starting_odometer' => 50000]);

    $attacker = User::factory()->create();

    Livewire::actingAs($attacker)
        ->test('vehicles.setuptire-create', ['vehicle' => $vehicle, 'tirePosition' => 'FL'])
        ->assertNotFound();
});

it('refuses to let setuptire-create.vehicle_id be changed after mount', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->for($user)->create(['starting_odometer' => 50000]);
    $otherVehicle = Vehicle::factory()->create(['starting_odometer' => 50000]);

    $component = Livewire::actingAs($user)
        ->test('vehicles.setuptire-create', ['vehicle' => $vehicle, 'tirePosition' => 'FL']);

    expect(fn () => $component->set('vehicle_id', $otherVehicle->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});
