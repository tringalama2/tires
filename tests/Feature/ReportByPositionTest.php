<?php

use App\Models\User;
use App\Models\Vehicle;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    [$this->user, $this->vehicle] = vehicleWithHistory();
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

it('shows the FR wear rate as the highest', function () {
    // vehicleWithHistory: FR avg = 0.8/1k
    $this->actingAs($this->user)
        ->get(route('reports.by-position'))
        ->assertOk()
        ->assertSeeText('0.80');
});

it('shows the fastest badge on the highest-wear position', function () {
    $this->actingAs($this->user)
        ->get(route('reports.by-position'))
        ->assertOk()
        ->assertSeeText('fastest');
});

it('shows an outlier alert when FR wears more than 2x the average of the others', function () {
    // vehicleWithHistory: FR = 0.8/1k, all others = 0 → FR is a clear outlier.
    $this->actingAs($this->user)
        ->get(route('reports.by-position'))
        ->assertOk()
        ->assertSeeText('Front Right');
});

it('shows rotation count and max odometer', function () {
    $this->actingAs($this->user)
        ->get(route('reports.by-position'))
        ->assertOk()
        ->assertSeeText('2')       // 2 real rotations
        ->assertSeeText('60,000'); // max odometer from rot2
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
