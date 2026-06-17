<?php

use App\Models\Placement;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\User;
use App\Models\Vehicle;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    [$this->user, $this->vehicle, $this->tires] = vehicleWithHistory();
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
    // vehicleWithHistory: T3@FR, T5@SP
    $this->actingAs($this->user)
        ->get(route('reports.by-tire'))
        ->assertOk()
        ->assertSee('FR') // T3's current position
        ->assertSee('SP'); // T5's current position
});

it('shows latest tread values', function () {
    // vehicleWithHistory: T3@FR tread 12, T1@FL tread 8
    $this->actingAs($this->user)
        ->get(route('reports.by-tire'))
        ->assertOk()
        ->assertSeeText('12/32"') // T3
        ->assertSeeText('8/32"');  // T1 and T2
});

it('shows projected replacement mileage for tires with enough data', function () {
    // Build a separate tire with 3 rotations (2 intervals) so projection is available.
    $vehicle = Vehicle::factory()->create(['user_id' => $this->user->id, 'tire_count' => 1, 'starting_odometer' => 0]);
    $tire = Tire::factory()->for($vehicle)->create(['label' => 'TX']);

    $setup = Rotation::factory()->setup()->for($vehicle)->create(['odometer' => 0]);
    $setup->placements()->create(['tire_id' => $tire->id, 'from_position' => null, 'to_position' => 'FR', 'tread_center' => 12]);

    $rot1 = Rotation::factory()->for($vehicle)->create(['odometer' => 5000]);
    $rot1->placements()->create(['tire_id' => $tire->id, 'from_position' => 'FR', 'to_position' => 'FR', 'tread_center' => 10]);

    $rot2 = Rotation::factory()->for($vehicle)->create(['odometer' => 10000]);
    $rot2->placements()->create(['tire_id' => $tire->id, 'from_position' => 'FR', 'to_position' => 'FR', 'tread_center' => 8]);

    $rot3 = Rotation::factory()->for($vehicle)->create(['odometer' => 15000]);
    $rot3->placements()->create(['tire_id' => $tire->id, 'from_position' => 'FR', 'to_position' => 'FR', 'tread_center' => 6]);

    session(['vehicle' => $vehicle]);

    $this->actingAs($this->user)
        ->get(route('reports.by-tire'))
        ->assertOk()
        ->assertSee('≈');
});

it('shows the scallop warning when the latest placement is_cupped', function () {
    $t1 = $this->tires['T1'];
    $latestPlacement = $t1->placements()
        ->join('rotations', 'rotations.id', '=', 'placements.rotation_id')
        ->where('rotations.is_setup', false)
        ->orderByDesc('rotations.odometer')
        ->first(['placements.*']);

    $latestPlacement->update([
        'is_cupped' => true,
        'tread_inner' => 5.0,
        'tread_outer' => 9.0,
    ]);

    $this->actingAs($this->user)
        ->get(route('reports.by-tire'))
        ->assertOk()
        ->assertSee('Uneven wear warning');
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
