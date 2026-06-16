<?php

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

// ---------------------------------------------------------------------------
// Create
// ---------------------------------------------------------------------------

it('can render the vehicle creation page', function () {
    $this->actingAs($this->user)->get(route('vehicles.create'))->assertOk();
});

it('can create a new vehicle', function () {
    $this->actingAs($this->user)
        ->post(route('vehicles.store'), Vehicle::factory()->make()->toArray())
        ->assertRedirectToRoute('vehicles.setuptires.index', Vehicle::where('user_id', $this->user->id)->first());
});

it('stores the new vehicle in session after creation', function () {
    $this->actingAs($this->user)
        ->post(route('vehicles.store'), Vehicle::factory()->make()->toArray());

    $vehicle = Vehicle::where('user_id', $this->user->id)->sole();

    $this->assertTrue($vehicle->is(session('vehicle')));
});

it('cannot create more than the maximum vehicles per user', function () {
    Vehicle::factory()->for($this->user)->count(Vehicle::MAX_VEHICLES_PER_USER)->create();

    $this->actingAs($this->user)->get(route('vehicles.create'))->assertForbidden();
    $this->actingAs($this->user)
        ->post(route('vehicles.store'), Vehicle::factory()->make()->toArray())
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Edit / update
// ---------------------------------------------------------------------------

it('can edit its own vehicle', function () {
    $vehicle = Vehicle::factory()->for($this->user)->create();
    session(['vehicle' => $vehicle]);

    $this->actingAs($this->user)
        ->put(route('vehicles.update', $vehicle), [
            'year' => 2020,
            'make' => 'Toyota',
            'model' => '4Runner',
            'nickname' => 'Daily',
        ])
        ->assertRedirectToRoute('vehicles.index');

    expect($vehicle->fresh()->make)->toBe('Toyota');
});

it('cannot update another user\'s vehicle', function () {
    $other = Vehicle::factory()->for($this->otherUser)->create();
    session(['vehicle' => Vehicle::factory()->for($this->user)->create()]);

    $this->actingAs($this->user)
        ->put(route('vehicles.update', $other), [
            'year' => 2020,
            'make' => 'Toyota',
            'model' => '4Runner',
            'nickname' => 'Hack',
        ])
        ->assertNotFound();
});

it('cannot view the edit form for another user\'s vehicle', function () {
    $other = Vehicle::factory()->for($this->otherUser)->create();
    session(['vehicle' => Vehicle::factory()->for($this->user)->create()]);

    $this->actingAs($this->user)
        ->get(route('vehicles.edit', $other))
        ->assertNotFound();
});

it('cannot access tire setup pages for another user\'s vehicle', function () {
    // User needs their own vehicle in session so firstVehicleExists middleware passes
    $ownVehicle = Vehicle::factory()->for($this->user)->create();
    session(['vehicle' => $ownVehicle]);

    $other = Vehicle::factory()->for($this->otherUser)->create();

    $this->actingAs($this->user)
        ->get(route('vehicles.setuptires.index', $other))
        ->assertNotFound();

    $this->actingAs($this->user)
        ->get(route('vehicles.setuptires.create', ['vehicle' => $other, 'tirePosition' => 'FL']))
        ->assertNotFound();

    $this->actingAs($this->user)
        ->post(route('vehicles.setuptires.store', ['vehicle' => $other, 'tirePosition' => 'FL']), [
            'label' => 'Evil', 'starting_tread' => 10,
        ])
        ->assertNotFound();
});

it('cannot change tire_count via the update form', function () {
    $vehicle = Vehicle::factory()->for($this->user)->create(['tire_count' => 5]);
    session(['vehicle' => $vehicle]);

    $this->actingAs($this->user)
        ->put(route('vehicles.update', $vehicle), [
            'year' => $vehicle->year,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'nickname' => $vehicle->nickname,
            'tire_count' => 4, // should be silently ignored
        ]);

    expect($vehicle->fresh()->tire_count)->toBe(5);
});

// ---------------------------------------------------------------------------
// Vehicle list
// ---------------------------------------------------------------------------

it('can see the Add a Vehicle link on the index page', function () {
    Vehicle::factory()->for($this->user)->create();

    $this->actingAs($this->user)->get(route('vehicles.index'))->assertSee('Add a Vehicle');
});

// ---------------------------------------------------------------------------
// Session / middleware
// ---------------------------------------------------------------------------

it('redirects to vehicle creation when no vehicle exists in DB or session', function () {
    $this->actingAs($this->user)
        ->get(route('vehicles.index'))
        ->assertRedirect(route('vehicles.create'));
});

it('restores the last selected vehicle from DB when session is empty', function () {
    $vehicle = Vehicle::factory()->for($this->user)->create(['last_selected_at' => now()]);

    $this->actingAs($this->user)
        ->withSession([])
        ->get(route('vehicles.index'))
        ->assertOk();

    expect(session('vehicle')?->id)->toBe($vehicle->id);
});

it('defaults to the most recently selected vehicle', function () {
    $older = Vehicle::factory()->for($this->user)->create(['last_selected_at' => now()->subDay()]);
    $newer = Vehicle::factory()->for($this->user)->create(['last_selected_at' => now()]);

    $this->actingAs($this->user)
        ->withSession([])
        ->get(route('vehicles.index'))
        ->assertOk();

    expect(session('vehicle')?->id)->toBe($newer->id);
});
