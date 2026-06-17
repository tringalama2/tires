<?php

use App\Enums\TirePosition;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\User;
use App\Models\Vehicle;
use Livewire\Livewire;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response
        ->assertOk()
        ->assertSeeLivewire('pages.auth.login');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $component = Livewire::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password');

    $component->call('login');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $component = Livewire::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'wrong-password');

    $component->call('login');

    $component
        ->assertHasErrors()
        ->assertNoRedirect();

    $this->assertGuest();
});

test('navigation menu can be rendered', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->for($user)->create(['tire_count' => 4]);
    $tires = Tire::factory()->for($vehicle)->count(4)->create();
    $setupRotation = Rotation::factory()->setup()->for($vehicle)->create(['odometer' => $vehicle->starting_odometer]);
    $positions = [TirePosition::FrontLeft, TirePosition::FrontRight, TirePosition::RearLeft, TirePosition::RearRight];
    foreach ($tires as $i => $tire) {
        $setupRotation->placements()->create(['tire_id' => $tire->id, 'from_position' => null, 'to_position' => $positions[$i], 'tread_center' => 10]);
    }

    $response = $this->actingAs($user)
        ->withSession(['vehicle' => $vehicle])
        ->get('/dashboard');

    $response
        ->assertOk()
        ->assertSeeLivewire('layout.navigation');
});

test('users can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test('layout.navigation');

    $component->call('logout');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
});
