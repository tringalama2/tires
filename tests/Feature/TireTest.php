<?php

use App\Models\Rotation;
use App\Models\Tire;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    [$this->user, $this->vehicle, $this->tires] = vehicleWithHistory();
    session(['vehicle' => $this->vehicle]);
});

// ---------------------------------------------------------------------------
// Tires list page
// ---------------------------------------------------------------------------

it('renders the tires list page with labels', function () {
    $this->actingAs($this->user)
        ->get(route('tires.index'))
        ->assertOk()
        ->assertSeeText('T1')
        ->assertSeeText('T2');
});

it('shows current positions and latest tread on the list', function () {
    // vehicleWithHistory: after rot2 — T3@FR(12), T1@FL(8)
    $this->actingAs($this->user)
        ->get(route('tires.index'))
        ->assertOk()
        ->assertSeeText('Front Right') // T3
        ->assertSeeText('12/32"');      // T3 latest tread
});

// ---------------------------------------------------------------------------
// Tires list — read-only roster (no add/retire actions on this page)
// ---------------------------------------------------------------------------

it('shows a View link for each tire', function () {
    $this->actingAs($this->user)
        ->get(route('tires.index'))
        ->assertOk()
        ->assertSeeText('View');
});

it('does not show Add Tire or Retire buttons', function () {
    $this->actingAs($this->user)
        ->get(route('tires.index'))
        ->assertOk()
        ->assertDontSeeText('Add Tire')
        ->assertDontSeeText('Retire');
});

// ---------------------------------------------------------------------------
// Tire detail page
// ---------------------------------------------------------------------------

it('renders the tire detail page with rotation history', function () {
    $tire = $this->tires['T1'];

    $this->actingAs($this->user)
        ->get(route('tires.show', $tire))
        ->assertOk()
        ->assertSeeText('T1')
        ->assertSeeText('Rotation History');
});

it('shows projected replacement on the detail page for tires with enough data', function () {
    // Build a tire with 3 rotations (2 intervals) so a projection is available.
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
        ->get(route('tires.show', $tire))
        ->assertOk()
        ->assertSeeText('Projected replacement');
});

// ---------------------------------------------------------------------------
// Condition badges
// ---------------------------------------------------------------------------

it('shows condition badges when flags are set on the tire', function () {
    $tire = $this->tires['T1'];
    $tire->update(['has_cracking' => true, 'has_bulge' => true]);

    $this->actingAs($this->user)
        ->get(route('tires.show', $tire))
        ->assertOk()
        ->assertSee('Cracking')
        ->assertSee('Bulge');
});

it('does not show condition badges when all flags are false', function () {
    $tire = $this->tires['T1'];
    $tire->update([
        'has_cracking' => false,
        'has_bulge' => false,
        'has_cupping' => false,
        'has_puncture_repair' => false,
    ]);

    $this->actingAs($this->user)
        ->get(route('tires.show', $tire))
        ->assertOk()
        ->assertDontSee('Cracking')
        ->assertDontSee('Bulge');
});

// ---------------------------------------------------------------------------
// Cross-user access
// ---------------------------------------------------------------------------

it('returns 404 when viewing another user\'s tire', function () {
    $other = User::factory()->create();
    $otherVehicle = Vehicle::factory()->for($other)->create(['tire_count' => 0]);
    $tire = Tire::factory()->create(['vehicle_id' => $otherVehicle->id]);

    $this->actingAs($this->user)
        ->get(route('tires.show', $tire))
        ->assertNotFound();
});

it('cannot tamper vehicle_id on tires.index via Livewire property update', function () {
    $other = User::factory()->create();
    $otherVehicle = Vehicle::factory()->for($other)->create(['tire_count' => 0]);

    $component = Livewire::actingAs($this->user)
        ->test('tires.index', ['vehicle_id' => $this->vehicle->id]);

    expect(fn () => $component->set('vehicle_id', $otherVehicle->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

// ---------------------------------------------------------------------------
// Mass assignment regression — only declared $fillable attributes may be set via fill()
// ---------------------------------------------------------------------------

it('cannot mass-assign the tire id', function () {
    expect(fn () => Tire::create([
        'id' => 99999,
        'vehicle_id' => $this->vehicle->id,
        'label' => 'Hijacked',
    ]))->toThrow(MassAssignmentException::class);
});
