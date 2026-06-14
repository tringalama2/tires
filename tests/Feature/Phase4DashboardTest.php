<?php

use App\Enums\TireStatus;
use App\Models\Placement;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Dashboard
// ---------------------------------------------------------------------------

it('renders the dashboard with last rotation date and days elapsed', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();
    session(['vehicle' => $vehicle]);

    $latest = $vehicle->rotations()->where('is_setup', false)->orderByDesc('odometer')->first();
    $expectedDays = (int) Carbon::parse($latest->rotated_on)->diffInDays(Carbon::today());

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText($latest->rotated_on->format('M j, Y'))
        ->assertSeeText(number_format($latest->odometer))
        ->assertSeeText($expectedDays.' day');
});

it('does not show the car-top-view position diagram on the dashboard', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();
    session(['vehicle' => $vehicle]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText('tire-position-details');
});

it('shows replacement alerts for tires within 10,000 miles of 2/32"', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();
    session(['vehicle' => $vehicle]);

    // T2 has latest tread 6/32" — force very high avg wear so projection < 10k miles
    // We do this by adding a rotation where T2 goes from 6 to 2/32" with small odometer delta
    $setupRotation = $vehicle->rotations()->where('is_setup', true)->first();
    $latestRot = $vehicle->rotations()->where('is_setup', false)->orderByDesc('odometer')->first();
    $t2 = $vehicle->tires()->where('label', 'T2')->first();

    // Create a new rotation with very small delta to push wear rate sky-high
    $newRotation = Rotation::create([
        'vehicle_id' => $vehicle->id,
        'rotated_on' => now()->toDateString(),
        'odometer' => $latestRot->odometer + 100, // tiny delta
        'is_setup' => false,
    ]);

    // Keep all tire positions the same (identity rotation) except T2 goes to 3/32"
    foreach ($latestRot->placements as $p) {
        Placement::create([
            'rotation_id' => $newRotation->id,
            'tire_id' => $p->tire_id,
            'from_position' => $p->to_position->value,
            'to_position' => $p->to_position->value,
            'tread_center' => $p->tire_id === $t2->id ? 3.0 : $p->tread_center,
        ]);
    }

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('Tires nearing replacement')
        ->assertSeeText('T2');
});

it('shows no replacement alert when all tires are far from limit', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();
    session(['vehicle' => $vehicle]);

    // Seed data has moderate wear rates and treads 6–12/32" — none should be within 10k miles
    // given only a few data points and low wear. Just verify no alert shown.
    $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();

    // The seed data's T2 (latest 6/32") has ~0.08 wear/1000mi from SP.
    // Miles remaining ≈ (6-2)/0.08*1000 = 50,000 — well above 10k threshold.
    $response->assertDontSeeText('Tires nearing replacement');
});

// ---------------------------------------------------------------------------
// Tires list page
// ---------------------------------------------------------------------------

it('renders the tires list page', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();
    session(['vehicle' => $vehicle]);

    $this->actingAs($user)
        ->get(route('tires.index'))
        ->assertOk()
        ->assertSeeText('Tires')
        ->assertSeeText('T1')
        ->assertSeeText('T2');
});

it('shows current position and latest tread on tires list', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();
    session(['vehicle' => $vehicle]);

    $this->actingAs($user)
        ->get(route('tires.index'))
        ->assertOk()
        ->assertSeeText('Front Right') // T1 current position
        ->assertSeeText('7/32"');      // T1 latest tread
});

it('can add a new tire via the tires list page', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();
    session(['vehicle' => $vehicle]);

    $beforeCount = $vehicle->tires()->count();

    Livewire::actingAs($user)
        ->test('tires.index', ['vehicle_id' => $vehicle->id])
        ->call('openAddForm')
        ->set('label', 'T6')
        ->set('brand', 'Michelin')
        ->call('addTire')
        ->assertHasNoErrors();

    expect($vehicle->fresh()->tires()->count())->toBe($beforeCount + 1);
    expect($vehicle->tires()->where('label', 'T6')->exists())->toBeTrue();
});

it('requires a label when adding a tire', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();
    session(['vehicle' => $vehicle]);

    Livewire::actingAs($user)
        ->test('tires.index', ['vehicle_id' => $vehicle->id])
        ->call('openAddForm')
        ->set('label', '')
        ->call('addTire')
        ->assertHasErrors(['label']);
});

it('can toggle a tire status to retired', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();
    $tire = $vehicle->tires()->where('label', 'T1')->first();
    session(['vehicle' => $vehicle]);

    expect($tire->status)->toBe(TireStatus::Active);

    Livewire::actingAs($user)
        ->test('tires.index', ['vehicle_id' => $vehicle->id])
        ->call('toggleStatus', $tire->id);

    expect($tire->fresh()->status)->toBe(TireStatus::Retired);
});
