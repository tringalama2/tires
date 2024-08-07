<?php

use App\Models\User;
use App\Models\Vehicle;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

it('must select a vehicle before proceeding')->todo();

it('cannot update vehicle tire count')->todo();

it('cannot update other user\'s vehicle')->todo();


it('cannot have null session selected vehicle')->todo();

it('can create a new vehicles', function () {
    $this->actingAs($this->user)->get(route('vehicles.create'))->assertStatus(200);

    $response = $this->actingAs($this->user)->post(route('vehicles.store',
        Vehicle::factory()->make()->toArray()
    ))->assertRedirectToRoute('vehicles.index');
});

it('can see the Add a Vehicle Link', function () {
    // one vehicle must exist to vie the vehicles.index page
    Vehicle::factory()->for($this->user)->create();

    $this->actingAs($this->user)->get(route('vehicles.index'))->assertSee('Add a Vehicle');
});

it('cannot create more than the max number of vehicles', function () {
    Vehicle::factory()->for($this->user)->count(Vehicle::MAX_VEHICLES_PER_USER)->create();

    $this->actingAs($this->user)->get(route('vehicles.create'))->assertForbidden();
    $this->actingAs($this->user)->post(route('vehicles.store',
        Vehicle::factory()->make()->toArray()
    ))->assertForbidden();
});

it('must default to last selected vehicle')->todo();

it('adds the newly added vehicle to session', function () {
    $response = $this->actingAs($this->user)->post(route('vehicles.store',
        Vehicle::factory()->make()->toArray()
    ));

    $vehicle = Vehicle::where('user_id', $this->user->id)->sole();

    $this->assertTrue($vehicle->is(session('vehicle')));
});

