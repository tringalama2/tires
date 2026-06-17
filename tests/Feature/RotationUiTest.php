<?php

use App\Models\Rotation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\RotationService;
use Livewire\Livewire;

beforeEach(function () {
    [$this->user, $this->vehicle] = vehicleWithHistory();
    session(['vehicle' => $this->vehicle]);
});

/**
 * Build the session placements payload keyed by from_position value.
 */
function sessionPlacements(object $vehicle, float $tread = 7.0): array
{
    $stubs = app(RotationService::class)->startNext($vehicle);
    $placements = [];
    foreach ($stubs as $stub) {
        $pos = $stub['from_position']->value;
        $placements[$pos] = [
            'tire_id' => $stub['tire']->id,
            'tire_label' => $stub['tire']->label,
            'from_position' => $pos,
            'from_position_label' => $stub['from_position']->label(),
            'tread_center' => $tread,
            'tread_inner' => null,
            'tread_outer' => null,
            'note' => null,
            'to_position' => $pos,
            'tire_flags' => ['has_cracking' => false, 'has_bulge' => false, 'has_cupping' => false, 'has_puncture_repair' => false],
            'is_feathering' => false,
            'is_cupped' => false,
        ];
    }

    return $placements;
}

function withRotationSession(object $vehicle, int $odometer = 65000): void
{
    session([
        'rotation.rotated_on' => '2026-12-01',
        'rotation.odometer' => $odometer,
        'rotation.note' => null,
        'rotation.placements' => sessionPlacements($vehicle),
        'rotation.rotation_id' => null,
    ]);
}

// ---------------------------------------------------------------------------
// Prepare page (new rotation)
// ---------------------------------------------------------------------------

it('renders the prepare page for a new rotation', function () {
    $this->actingAs($this->user)
        ->get(route('rotations.prepare'))
        ->assertOk()
        ->assertSeeText('New Rotation');
});

it('shows all 5 tire positions on the prepare page', function () {
    $this->actingAs($this->user)
        ->get(route('rotations.prepare'))
        ->assertOk()
        ->assertSee('FL')
        ->assertSee('FR')
        ->assertSee('RL')
        ->assertSee('RR')
        ->assertSee('SP');
});

it('shows the last known tread as a hint on the prepare page', function () {
    // vehicleWithHistory: T3@FR(12), T1@FL(8) — both treads appear as hints
    $this->actingAs($this->user)
        ->get(route('rotations.prepare'))
        ->assertOk()
        ->assertSeeText('12') // T3@FR hint
        ->assertSeeText('8'); // T1@FL hint
});

it('shows the last odometer as a placeholder after a wire interaction', function () {
    // vehicleWithHistory ends at odometer 60,000
    Livewire::actingAs($this->user)
        ->test('rotations.prepare')
        ->set('rotation_note', 'test')
        ->assertSee('60,000');
});

it('shows the vehicle nickname after a wire interaction', function () {
    Livewire::actingAs($this->user)
        ->test('rotations.prepare')
        ->set('rotation_note', 'test')
        ->assertSee($this->vehicle->nickname);
});

it('shows the vehicle nickname in edit mode after a wire interaction', function () {
    $rotation = $this->vehicle->rotations()->where('is_setup', false)->orderByDesc('odometer')->first();

    Livewire::actingAs($this->user)
        ->test('rotations.prepare', ['edit_rotation_id' => $rotation->id])
        ->set('rotation_note', 'test')
        ->assertSee($this->vehicle->nickname);
});

// ---------------------------------------------------------------------------
// Update page (assign to_positions + save)
// ---------------------------------------------------------------------------

it('redirects to prepare when update is accessed without session data', function () {
    session()->forget(['rotation.odometer']);

    $this->actingAs($this->user)
        ->get(route('rotations.update'))
        ->assertRedirect(route('rotations.prepare'));
});

it('renders the update page when session data is present', function () {
    withRotationSession($this->vehicle);

    $this->actingAs($this->user)
        ->get(route('rotations.update'))
        ->assertOk()
        ->assertSeeText('Step 2');
});

it('saves a new rotation and redirects to the dashboard', function () {
    withRotationSession($this->vehicle);

    Livewire::actingAs($this->user)
        ->test('rotations.update')
        ->call('save')
        ->assertRedirect(route('dashboard'));

    expect(
        Rotation::where('vehicle_id', $this->vehicle->id)->where('odometer', 65000)->exists()
    )->toBeTrue();
});

it('shows a validation error when to_positions are not a valid permutation', function () {
    $placements = sessionPlacements($this->vehicle);

    session([
        'rotation.rotated_on' => '2026-12-01',
        'rotation.odometer' => 65000,
        'rotation.note' => null,
        'rotation.placements' => $placements,
        'rotation.rotation_id' => null,
    ]);

    $badPositions = array_fill_keys(array_keys($placements), 'FL');

    Livewire::actingAs($this->user)
        ->test('rotations.update')
        ->set('toPositions', $badPositions)
        ->call('save')
        ->assertSet('validationError', 'Each tire must be assigned exactly one position, and all positions must be filled.');
});

// ---------------------------------------------------------------------------
// Edit mode
// ---------------------------------------------------------------------------

it('renders the edit page with existing rotation data', function () {
    $rotation = $this->vehicle->rotations()->where('is_setup', false)->orderByDesc('odometer')->first();

    $this->actingAs($this->user)
        ->get(route('rotations.edit', ['edit_rotation_id' => $rotation->id]))
        ->assertOk()
        ->assertSeeText('Edit Rotation');
});

// ---------------------------------------------------------------------------
// Auth
// ---------------------------------------------------------------------------

it('redirects unauthenticated users away from prepare', function () {
    $this->get(route('rotations.prepare'))->assertRedirect(route('login'));
});

// ---------------------------------------------------------------------------
// Cross-user access
// ---------------------------------------------------------------------------

it('cannot access rotation prepare for another user\'s vehicle via vehicle_id', function () {
    $other = User::factory()->create();
    $otherVehicle = Vehicle::factory()->for($other)->create(['tire_count' => 0]);
    session(['vehicle' => $this->vehicle]);

    $this->actingAs($this->user)
        ->get(route('rotations.prepare', ['vehicle_id' => $otherVehicle->id]))
        ->assertNotFound();
});

it('cannot edit a rotation belonging to another user\'s vehicle', function () {
    $other = User::factory()->create();
    $otherVehicle = Vehicle::factory()->for($other)->create(['tire_count' => 0]);
    $rotation = Rotation::factory()->create(['vehicle_id' => $otherVehicle->id]);
    session(['vehicle' => $this->vehicle]);

    $this->actingAs($this->user)
        ->get(route('rotations.edit', ['edit_rotation_id' => $rotation->id]))
        ->assertNotFound();
});
