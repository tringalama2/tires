<?php

use App\Models\Placement;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\User;
use App\Models\Vehicle;
use Livewire\Livewire;

test('deleting a user cascades to vehicles, tires, rotations, and placements', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->for($user)->create();
    $tire = Tire::factory()->for($vehicle)->create();
    $rotation = Rotation::factory()->for($vehicle)->create();
    $placement = Placement::factory()
        ->for($rotation)
        ->for($tire)
        ->create();

    $user->delete();

    expect(User::find($user->id))->toBeNull()
        ->and(Vehicle::find($vehicle->id))->toBeNull()
        ->and(Tire::find($tire->id))->toBeNull()
        ->and(Rotation::find($rotation->id))->toBeNull()
        ->and(Placement::find($placement->id))->toBeNull();
});

test('delete account form requires correct password', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('profile.delete-user-form')
        ->set('password', 'wrong-password')
        ->call('deleteUser')
        ->assertHasErrors(['password']);

    expect(User::find($user->id))->not->toBeNull();
});

test('delete account form deletes user with correct password', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('profile.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    expect(User::find($user->id))->toBeNull();
});
