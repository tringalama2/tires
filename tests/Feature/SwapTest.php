<?php

use App\Enums\TirePosition;
use App\Enums\TireStatus;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\RotationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helper: vehicle with setup rotation + one real rotation
// ---------------------------------------------------------------------------

function swapTestVehicle(): array
{
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create([
        'user_id' => $user->id,
        'tire_count' => 5,
        'starting_odometer' => 50000,
    ]);

    $positions = [
        TirePosition::FrontLeft,
        TirePosition::FrontRight,
        TirePosition::RearLeft,
        TirePosition::RearRight,
        TirePosition::Spare,
    ];

    $tires = [];
    $setupRotation = Rotation::factory()->setup()->for($vehicle)->create(['odometer' => 50000]);

    foreach ($positions as $i => $pos) {
        $tire = Tire::factory()->for($vehicle)->create(['label' => 'T'.($i + 1)]);
        $setupRotation->placements()->create([
            'tire_id' => $tire->id,
            'from_position' => null,
            'to_position' => $pos,
            'tread_center' => 15,
        ]);
        $tires[$pos->value] = $tire;
    }

    // One real rotation: FL↔RL, FR↔RR, SP stays.
    $rotation = Rotation::factory()->for($vehicle)->create(['odometer' => 55000, 'rotated_on' => '2026-01-01']);
    $rotation->placements()->create(['tire_id' => $tires['FL']->id, 'from_position' => TirePosition::FrontLeft,  'to_position' => TirePosition::RearLeft,   'tread_center' => 13]);
    $rotation->placements()->create(['tire_id' => $tires['FR']->id, 'from_position' => TirePosition::FrontRight, 'to_position' => TirePosition::RearRight,  'tread_center' => 13]);
    $rotation->placements()->create(['tire_id' => $tires['RL']->id, 'from_position' => TirePosition::RearLeft,   'to_position' => TirePosition::FrontLeft,  'tread_center' => 12]);
    $rotation->placements()->create(['tire_id' => $tires['RR']->id, 'from_position' => TirePosition::RearRight,  'to_position' => TirePosition::FrontRight, 'tread_center' => 12]);
    $rotation->placements()->create(['tire_id' => $tires['SP']->id, 'from_position' => TirePosition::Spare,      'to_position' => TirePosition::Spare,      'tread_center' => 14]);

    return [$user, $vehicle, $tires];
}

// ---------------------------------------------------------------------------
// RotationService::saveSwap
// ---------------------------------------------------------------------------

it('creates a swap rotation with is_swap = true', function () {
    [, $vehicle, $tires] = swapTestVehicle();

    app(RotationService::class)->saveSwap([
        'rotated_on' => '2026-07-01',
        'odometer' => 60000,
        'swaps' => [[
            'retiring_tire_id' => $tires['RL']->id,   // RL tire is now at FL
            'retiring_tread' => 4.0,
            'replacement_label' => 'T6',
            'replacement_brand' => 'BF Goodrich',
            'replacement_model' => 'KO2',
            'replacement_tread' => 15.0,
        ]],
    ], $vehicle);

    $swap = Rotation::where('vehicle_id', $vehicle->id)->where('is_swap', true)->first();
    expect($swap)->not->toBeNull()
        ->and($swap->odometer)->toBe(60000);
});

it('marks the retiring tire as Retired', function () {
    [, $vehicle, $tires] = swapTestVehicle();
    $retiring = $tires['RL'];

    app(RotationService::class)->saveSwap([
        'rotated_on' => '2026-07-01',
        'odometer' => 60000,
        'swaps' => [['retiring_tire_id' => $retiring->id, 'retiring_tread' => null, 'replacement_label' => 'T6', 'replacement_tread' => 15.0]],
    ], $vehicle);

    expect($retiring->fresh()->status)->toBe(TireStatus::Retired);
});

it('creates a retiring placement with to_position null at the correct from_position', function () {
    [, $vehicle, $tires] = swapTestVehicle();
    $retiring = $tires['RL']; // after real rotation: RL tire moved to FL

    app(RotationService::class)->saveSwap([
        'rotated_on' => '2026-07-01',
        'odometer' => 60000,
        'swaps' => [['retiring_tire_id' => $retiring->id, 'retiring_tread' => 4.5, 'replacement_label' => 'T6', 'replacement_tread' => 15.0]],
    ], $vehicle);

    $swap = Rotation::where('is_swap', true)->first();
    $placement = $swap->placements()->where('tire_id', $retiring->id)->first();

    expect($placement->from_position)->toBe(TirePosition::FrontLeft)
        ->and($placement->to_position)->toBeNull()
        ->and((float) $placement->tread_center)->toBe(4.5);
});

it('creates the replacement tire placed at the vacated position', function () {
    [, $vehicle, $tires] = swapTestVehicle();
    $retiring = $tires['RL']; // currently at FL

    app(RotationService::class)->saveSwap([
        'rotated_on' => '2026-07-01',
        'odometer' => 60000,
        'swaps' => [['retiring_tire_id' => $retiring->id, 'retiring_tread' => null, 'replacement_label' => 'T6', 'replacement_brand' => 'BF Goodrich', 'replacement_model' => 'KO2', 'replacement_tread' => 15.0]],
    ], $vehicle);

    $newTire = $vehicle->tires()->where('label', 'T6')->first();
    expect($newTire)->not->toBeNull()
        ->and($newTire->status)->toBe(TireStatus::Active)
        ->and($newTire->brand)->toBe('BF Goodrich');

    $placement = Rotation::where('is_swap', true)->first()->placements()->where('tire_id', $newTire->id)->first();
    expect($placement->from_position)->toBeNull()
        ->and($placement->to_position)->toBe(TirePosition::FrontLeft)
        ->and((float) $placement->tread_center)->toBe(15.0);
});

it('handles a multi-tire swap in one rotation with four placements', function () {
    [, $vehicle, $tires] = swapTestVehicle();

    app(RotationService::class)->saveSwap([
        'rotated_on' => '2026-07-01',
        'odometer' => 60000,
        'swaps' => [
            ['retiring_tire_id' => $tires['RL']->id, 'retiring_tread' => null, 'replacement_label' => 'T6', 'replacement_tread' => 15.0],
            ['retiring_tire_id' => $tires['RR']->id, 'retiring_tread' => null, 'replacement_label' => 'T7', 'replacement_tread' => 15.0],
        ],
    ], $vehicle);

    expect(Rotation::where('is_swap', true)->count())->toBe(1)
        ->and(Rotation::where('is_swap', true)->first()->placements()->count())->toBe(4);
});

it('is atomic — rolls back everything on error', function () {
    [, $vehicle] = swapTestVehicle();
    $countBefore = $vehicle->tires()->count();

    expect(fn () => app(RotationService::class)->saveSwap([
        'rotated_on' => '2026-07-01',
        'odometer' => 60000,
        'swaps' => [['retiring_tire_id' => 'non-existent-uuid', 'retiring_tread' => null, 'replacement_label' => 'T6', 'replacement_tread' => 15.0]],
    ], $vehicle))->toThrow(ModelNotFoundException::class);

    expect($vehicle->tires()->count())->toBe($countBefore)
        ->and(Rotation::where('is_swap', true)->count())->toBe(0);
});

it('throws ValidationException when odometer is below the last rotation', function () {
    [, $vehicle, $tires] = swapTestVehicle(); // last rotation at 55000

    expect(fn () => app(RotationService::class)->saveSwap([
        'rotated_on' => '2026-07-01',
        'odometer' => 40000,
        'swaps' => [['retiring_tire_id' => $tires['RL']->id, 'retiring_tread' => null, 'replacement_label' => 'T6', 'replacement_tread' => 15.0]],
    ], $vehicle))->toThrow(ValidationException::class);
});

// ---------------------------------------------------------------------------
// startNext regression — retired tires must not appear as stubs
// ---------------------------------------------------------------------------

it('startNext excludes retired tires', function () {
    [, $vehicle, $tires] = swapTestVehicle();
    $tires['RL']->update(['status' => TireStatus::Retired]);

    $stubs = app(RotationService::class)->startNext($vehicle);

    $tireIds = array_column(array_column($stubs, 'tire'), 'id');
    expect($tireIds)->not->toContain($tires['RL']->id)
        ->and(count($stubs))->toBe(4);
});

// ---------------------------------------------------------------------------
// Livewire component
// ---------------------------------------------------------------------------

it('renders the swap page with all active tires', function () {
    [$user, $vehicle] = swapTestVehicle();
    session(['vehicle' => $vehicle]);

    Livewire::actingAs($user)
        ->test('rotations.swap', ['vehicle_id' => $vehicle->id])
        ->assertSee('T1')
        ->assertSee('T2')
        ->assertSee('Retire');
});

it('advances to review step with valid entry', function () {
    [$user, $vehicle, $tires] = swapTestVehicle();
    $retiring = $tires['RL'];
    session(['vehicle' => $vehicle]);

    Livewire::actingAs($user)
        ->test('rotations.swap', ['vehicle_id' => $vehicle->id])
        ->set('rotated_on', '2026-07-01')
        ->set('odometer', 60000)
        ->set("swaps.{$retiring->id}.retiring", true)
        ->set("swaps.{$retiring->id}.replacement_label", 'T6')
        ->set("swaps.{$retiring->id}.replacement_tread", '15')
        ->call('toReview')
        ->assertSet('step', 'review')
        ->assertSee('T6')
        ->assertSee('Confirm swap');
});

it('stays on entry and errors when no tire is selected', function () {
    [$user, $vehicle] = swapTestVehicle();
    session(['vehicle' => $vehicle]);

    Livewire::actingAs($user)
        ->test('rotations.swap', ['vehicle_id' => $vehicle->id])
        ->set('rotated_on', '2026-07-01')
        ->set('odometer', 60000)
        ->call('toReview')
        ->assertSet('step', 'entry')
        ->assertSet('validationError', 'Select at least one tire to retire.');
});

it('errors when replacement label is missing', function () {
    [$user, $vehicle, $tires] = swapTestVehicle();
    $retiring = $tires['RL'];
    session(['vehicle' => $vehicle]);

    Livewire::actingAs($user)
        ->test('rotations.swap', ['vehicle_id' => $vehicle->id])
        ->set('rotated_on', '2026-07-01')
        ->set('odometer', 60000)
        ->set("swaps.{$retiring->id}.retiring", true)
        ->set("swaps.{$retiring->id}.replacement_tread", '15')
        ->call('toReview')
        ->assertSet('step', 'entry')
        ->assertNotSet('validationError', null);
});

it('saves and redirects to dashboard on confirm', function () {
    [$user, $vehicle, $tires] = swapTestVehicle();
    $retiring = $tires['RL'];
    session(['vehicle' => $vehicle]);

    Livewire::actingAs($user)
        ->test('rotations.swap', ['vehicle_id' => $vehicle->id])
        ->set('rotated_on', '2026-07-01')
        ->set('odometer', 60000)
        ->set("swaps.{$retiring->id}.retiring", true)
        ->set("swaps.{$retiring->id}.replacement_label", 'T6')
        ->set("swaps.{$retiring->id}.replacement_tread", '15')
        ->call('toReview')
        ->call('save')
        ->assertRedirect(route('dashboard', $vehicle->id));

    expect($retiring->fresh()->status)->toBe(TireStatus::Retired)
        ->and($vehicle->tires()->where('label', 'T6')->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// Optional fields — DOT/TIN, Size, Purchase Date
// ---------------------------------------------------------------------------

it('saves replacement tire with tin, size, and purchased_on', function () {
    [, $vehicle, $tires] = swapTestVehicle();

    app(RotationService::class)->saveSwap([
        'rotated_on' => '2026-07-01',
        'odometer' => 60000,
        'swaps' => [[
            'retiring_tire_id' => $tires['RL']->id,
            'retiring_tread' => null,
            'replacement_label' => 'T6',
            'replacement_tread' => 15.0,
            'replacement_tin' => 'ABC1234567',
            'replacement_size' => '275/70R18',
            'replacement_purchased_on' => '2026-06-15',
        ]],
    ], $vehicle);

    $newTire = $vehicle->tires()->where('label', 'T6')->first();
    expect($newTire->tin)->toBe('ABC1234567')
        ->and($newTire->size)->toBe('275/70R18')
        ->and($newTire->purchased_on->toDateString())->toBe('2026-06-15');
});

it('throws ValidationException when replacement_tin exceeds 12 characters', function () {
    [, $vehicle, $tires] = swapTestVehicle();

    expect(fn () => app(RotationService::class)->saveSwap([
        'rotated_on' => '2026-07-01',
        'odometer' => 60000,
        'swaps' => [[
            'retiring_tire_id' => $tires['RL']->id,
            'retiring_tread' => null,
            'replacement_label' => 'T6',
            'replacement_tread' => 15.0,
            'replacement_tin' => 'TOOLONGTIN123',
        ]],
    ], $vehicle))->toThrow(ValidationException::class);
});

it('throws ValidationException when replacement_purchased_on is not a valid date', function () {
    [, $vehicle, $tires] = swapTestVehicle();

    expect(fn () => app(RotationService::class)->saveSwap([
        'rotated_on' => '2026-07-01',
        'odometer' => 60000,
        'swaps' => [[
            'retiring_tire_id' => $tires['RL']->id,
            'retiring_tread' => null,
            'replacement_label' => 'T6',
            'replacement_tread' => 15.0,
            'replacement_purchased_on' => 'not-a-date',
        ]],
    ], $vehicle))->toThrow(ValidationException::class);
});

it('UI errors when tin is too long', function () {
    [$user, $vehicle, $tires] = swapTestVehicle();
    $retiring = $tires['RL'];
    session(['vehicle' => $vehicle]);

    Livewire::actingAs($user)
        ->test('rotations.swap', ['vehicle_id' => $vehicle->id])
        ->set('rotated_on', '2026-07-01')
        ->set('odometer', 60000)
        ->set("swaps.{$retiring->id}.retiring", true)
        ->set("swaps.{$retiring->id}.replacement_label", 'T6')
        ->set("swaps.{$retiring->id}.replacement_tread", '15')
        ->set("swaps.{$retiring->id}.replacement_tin", 'TOOLONGTIN123')
        ->call('toReview')
        ->assertSet('step', 'entry')
        ->assertNotSet('validationError', null);
});

it('UI errors when purchase date is invalid', function () {
    [$user, $vehicle, $tires] = swapTestVehicle();
    $retiring = $tires['RL'];
    session(['vehicle' => $vehicle]);

    Livewire::actingAs($user)
        ->test('rotations.swap', ['vehicle_id' => $vehicle->id])
        ->set('rotated_on', '2026-07-01')
        ->set('odometer', 60000)
        ->set("swaps.{$retiring->id}.retiring", true)
        ->set("swaps.{$retiring->id}.replacement_label", 'T6')
        ->set("swaps.{$retiring->id}.replacement_tread", '15')
        ->set("swaps.{$retiring->id}.replacement_purchased_on", 'not-a-date')
        ->call('toReview')
        ->assertSet('step', 'entry')
        ->assertNotSet('validationError', null);
});

it('purchase date is pre-populated with today on mount', function () {
    [$user, $vehicle, $tires] = swapTestVehicle();
    session(['vehicle' => $vehicle]);

    $component = Livewire::actingAs($user)
        ->test('rotations.swap', ['vehicle_id' => $vehicle->id]);

    $tireId = array_key_first($component->get('swaps'));
    expect($component->get("swaps.{$tireId}.replacement_purchased_on"))
        ->toBe(Carbon::today()->toDateString());
});

it('back() returns to entry without losing form state', function () {
    [$user, $vehicle, $tires] = swapTestVehicle();
    $retiring = $tires['RL'];
    session(['vehicle' => $vehicle]);

    Livewire::actingAs($user)
        ->test('rotations.swap', ['vehicle_id' => $vehicle->id])
        ->set('rotated_on', '2026-07-01')
        ->set('odometer', 60000)
        ->set("swaps.{$retiring->id}.retiring", true)
        ->set("swaps.{$retiring->id}.replacement_label", 'T6')
        ->set("swaps.{$retiring->id}.replacement_tread", '15')
        ->call('toReview')
        ->call('back')
        ->assertSet('step', 'entry')
        ->assertSet("swaps.{$retiring->id}.replacement_label", 'T6');
});
