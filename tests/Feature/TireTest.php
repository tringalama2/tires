<?php

use App\Enums\TireStatus;
use App\Models\Tire;
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
    $this->actingAs($this->user)
        ->get(route('tires.index'))
        ->assertOk()
        ->assertSeeText('Front Right') // T1
        ->assertSeeText('7/32"');       // T1 latest tread
});

// ---------------------------------------------------------------------------
// Add tire via Livewire
// ---------------------------------------------------------------------------

it('can add a tire via the tires list component', function () {
    $before = $this->vehicle->tires()->count();

    Livewire::actingAs($this->user)
        ->test('tires.index', ['vehicle_id' => $this->vehicle->id])
        ->call('openAddForm')
        ->set('label', 'T6')
        ->set('brand', 'Michelin')
        ->call('addTire')
        ->assertHasNoErrors();

    expect($this->vehicle->fresh()->tires()->count())->toBe($before + 1)
        ->and($this->vehicle->tires()->where('label', 'T6')->exists())->toBeTrue();
});

it('requires a label when adding a tire', function () {
    Livewire::actingAs($this->user)
        ->test('tires.index', ['vehicle_id' => $this->vehicle->id])
        ->call('openAddForm')
        ->set('label', '')
        ->call('addTire')
        ->assertHasErrors(['label']);
});

// ---------------------------------------------------------------------------
// Status toggle
// ---------------------------------------------------------------------------

it('can toggle a tire to Retired', function () {
    $tire = $this->vehicle->tires()->where('label', 'T1')->first();

    Livewire::actingAs($this->user)
        ->test('tires.index', ['vehicle_id' => $this->vehicle->id])
        ->call('toggleStatus', $tire->id);

    expect($tire->fresh()->status)->toBe(TireStatus::Retired);
});

it('can toggle a Retired tire back to Active', function () {
    $tire = $this->vehicle->tires()->where('label', 'T1')->first();
    $tire->update(['status' => TireStatus::Retired]);

    Livewire::actingAs($this->user)
        ->test('tires.index', ['vehicle_id' => $this->vehicle->id])
        ->call('toggleStatus', $tire->id);

    expect($tire->fresh()->status)->toBe(TireStatus::Active);
});

// ---------------------------------------------------------------------------
// Tire detail page
// ---------------------------------------------------------------------------

it('renders the tire detail page with rotation history', function () {
    $tire = $this->vehicle->tires()->where('label', 'T1')->firstOrFail();

    $this->actingAs($this->user)
        ->get(route('tires.show', $tire))
        ->assertOk()
        ->assertSeeText('T1')
        ->assertSeeText('Rotation History');
});

it('shows projected replacement on the detail page for tires with enough data', function () {
    $tire = $this->vehicle->tires()->where('label', 'T1')->firstOrFail();

    $this->actingAs($this->user)
        ->get(route('tires.show', $tire))
        ->assertOk()
        ->assertSeeText('Projected replacement');
});

// ---------------------------------------------------------------------------
// Condition badges
// ---------------------------------------------------------------------------

it('shows condition badges when flags are set on the tire', function () {
    $tire = $this->vehicle->tires()->first();
    $tire->update(['has_cracking' => true, 'has_bulge' => true]);

    $this->actingAs($this->user)
        ->get(route('tires.show', $tire))
        ->assertOk()
        ->assertSee('Cracking')
        ->assertSee('Bulge');
});

it('does not show condition badges when all flags are false', function () {
    $tire = $this->vehicle->tires()->first();
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
