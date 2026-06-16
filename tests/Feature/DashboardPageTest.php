<?php

use App\Models\Placement;
use App\Models\Rotation;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('renders last rotation date, odometer, and days elapsed', function () {
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

it('shows replacement alert for a tire within 10,000 miles of the 2/32" limit', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();
    session(['vehicle' => $vehicle]);

    $latestRot = $vehicle->rotations()->where('is_setup', false)->orderByDesc('odometer')->first();
    $t2 = $vehicle->tires()->where('label', 'T2')->first();

    // Force very high wear rate by adding a rotation with a tiny odometer delta
    $newRotation = Rotation::create([
        'vehicle_id' => $vehicle->id,
        'rotated_on' => now()->toDateString(),
        'odometer' => $latestRot->odometer + 100,
        'is_setup' => false,
    ]);

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

it('does not show replacement alert when all tires are far from the limit', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();
    session(['vehicle' => $vehicle]);

    // Seed data: T2 latest = 6/32" at ~0.08/1000mi → ≈50k miles remaining, well above 10k
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText('Tires nearing replacement');
});

it('redirects to vehicle setup when tires are not fully configured', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['user_id' => $user->id, 'tire_count' => 5]);
    session(['vehicle' => $vehicle]);

    // No tires added yet → ActiveVehicleTiresMiddleware fires
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('vehicles.setuptires.index', $vehicle));
});

it('redirects to vehicle creation when no vehicle exists', function () {
    $user = User::factory()->create();

    // No vehicle in session or DB → FirstVehicleMiddleware fires
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('vehicles.create'));
});

it('cannot access another user\'s dashboard via vehicle_id URL param', function () {
    $this->seed(DatabaseSeeder::class);

    $attacker = User::first();
    $victim = User::factory()->create();
    $victimVehicle = Vehicle::factory()->for($victim)->create(['tire_count' => 0]);

    // Attacker uses their own seeded vehicle in session so middleware passes
    session(['vehicle' => Vehicle::where('user_id', $attacker->id)->first()]);

    $this->actingAs($attacker)
        ->get(route('dashboard', ['vehicle_id' => $victimVehicle->id]))
        ->assertNotFound();
});

it('restores vehicle from DB into session when session is cleared', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::first();
    $vehicle = Vehicle::first();
    // Use DB directly since last_selected_at is not in $fillable
    DB::table('vehicles')
        ->where('id', $vehicle->id)
        ->update(['last_selected_at' => now(), 'user_id' => $user->id]);

    // Visit without a vehicle in session — middleware should load from DB
    $this->actingAs($user)
        ->withSession([]) // clear session
        ->get(route('dashboard'))
        ->assertOk();
});
