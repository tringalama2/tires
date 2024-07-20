<?php

use App\Models\User;
use App\Models\Vehicle;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

it('user must select a vehicle before proceeding')->todo();

it('vehicle tire count cannot be updated')->todo();

it('user cannot update other user\'s vehicle')->todo();

it('can create a new vehicles', function () {
    $this->actingAs($this->user)->get(route('vehicles.create'))->assertStatus(200);

    $response = $this->actingAs($this->user)->post(route('vehicles.store',
        Vehicle::factory()->make()->toArray()
    ))->assertRedirectToRoute('vehicles.index');
});

it('cannot create more than the max number of vehicles', function () {
    Vehicle::factory()->for($this->user)->count(Vehicle::MAX_VEHICLES_PER_USER)->create();

    $this->actingAs($this->user)->get(route('vehicles.create'))->assertForbidden();
    $this->actingAs($this->user)->post(route('vehicles.store',
        Vehicle::factory()->make()->toArray()
    ))->assertForbidden();
});
