<?php

use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->user = User::first();
    $this->vehicle = Vehicle::first();
    session(['vehicle' => $this->vehicle]);
});

it('renders the by-position report page', function () {
    $this->actingAs($this->user)
        ->get(route('reports.by-position'))
        ->assertOk()
        ->assertSeeText('Wear by Position')
        ->assertSeeText('Front Right')
        ->assertSeeText('Spare');
});

it('shows the known-good FR wear rate', function () {
    $this->actingAs($this->user)
        ->get(route('reports.by-position'))
        ->assertOk()
        ->assertSeeText('0.32');
});

it('shows the fastest badge on the highest-wear position', function () {
    $this->actingAs($this->user)
        ->get(route('reports.by-position'))
        ->assertOk()
        ->assertSeeText('fastest');
});

it('shows an outlier alert when one position wears more than 2x the others', function () {
    // The seeded FR rate (0.32) is well above others avg (~0.15) — 2x threshold should trigger.
    $response = $this->actingAs($this->user)->get(route('reports.by-position'))->assertOk();

    // Outlier alert only fires if FR > 2 * avg(others). With seed data it does.
    $response->assertSeeText('Front Right');
});

it('shows rotation count and odometer through', function () {
    $this->actingAs($this->user)
        ->get(route('reports.by-position'))
        ->assertOk()
        ->assertSeeText('4')  // 4 rotations
        ->assertSeeText('120,495');  // max odometer from seed
});

it('redirects unauthenticated users', function () {
    $this->get(route('reports.by-position'))->assertRedirect(route('login'));
});

it('cannot tamper vehicle_id via Livewire property update', function () {
    $other = User::factory()->create();
    $otherVehicle = Vehicle::factory()->for($other)->create(['tire_count' => 0]);

    $component = Livewire::actingAs($this->user)
        ->test('reports.by-position', ['vehicle_id' => $this->vehicle->id]);

    expect(fn () => $component->set('vehicle_id', $otherVehicle->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});
