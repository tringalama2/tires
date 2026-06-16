<?php

use App\Models\Placement;
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

it('renders the by-tire report page', function () {
    $this->actingAs($this->user)
        ->get(route('reports.by-tire'))
        ->assertOk()
        ->assertSeeText('Wear by Tire')
        ->assertSeeText('T1')
        ->assertSeeText('T2');
});

it('shows current positions on the by-tire page', function () {
    $this->actingAs($this->user)
        ->get(route('reports.by-tire'))
        ->assertOk()
        ->assertSee('FR')  // T1's current position
        ->assertSee('SP'); // T2's current position
});

it('shows latest tread values', function () {
    $this->actingAs($this->user)
        ->get(route('reports.by-tire'))
        ->assertOk()
        ->assertSeeText('7/32"')  // T1
        ->assertSeeText('6/32"'); // T2
});

it('shows projected replacement mileage for tires with enough data', function () {
    $this->actingAs($this->user)
        ->get(route('reports.by-tire'))
        ->assertOk()
        ->assertSee('≈'); // projected miles marker
});

it('shows the scallop warning when the latest placement is_cupped', function () {
    $t1 = $this->vehicle->tires()->where('label', 'T1')->firstOrFail();
    $latestPlacement = $t1->placements()
        ->join('rotations', 'rotations.id', '=', 'placements.rotation_id')
        ->where('rotations.is_setup', false)
        ->orderByDesc('rotations.odometer')
        ->first(['placements.*']);

    // Also need inner/outer so the scallop component appears in the template
    $latestPlacement->update([
        'is_cupped' => true,
        'tread_inner' => 5.0,
        'tread_outer' => 9.0,
    ]);

    $this->actingAs($this->user)
        ->get(route('reports.by-tire'))
        ->assertOk()
        ->assertSee('Uneven wear warning'); // aria-label on scallop-warning component
});

it('does not show the scallop warning when no placement is cupped', function () {
    Placement::query()->update(['is_cupped' => false]);

    $this->actingAs($this->user)
        ->get(route('reports.by-tire'))
        ->assertOk()
        ->assertDontSee('Uneven wear warning');
});

it('redirects unauthenticated users', function () {
    $this->get(route('reports.by-tire'))->assertRedirect(route('login'));
});

it('cannot tamper vehicle_id via Livewire property update', function () {
    $other = User::factory()->create();
    $otherVehicle = Vehicle::factory()->for($other)->create(['tire_count' => 0]);

    $component = Livewire::actingAs($this->user)
        ->test('reports.by-tire', ['vehicle_id' => $this->vehicle->id]);

    expect(fn () => $component->set('vehicle_id', $otherVehicle->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});
