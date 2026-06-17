<?php

use App\Models\Placement;
use App\Models\Rotation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

it('renders last rotation date, odometer, and days elapsed', function () {
    [$user, $vehicle] = vehicleWithHistory();
    session(['vehicle' => $vehicle]);

    // vehicleWithHistory: last rotation at 60,000 mi on 2025-07-01
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
    [$user, $vehicle] = vehicleWithHistory();
    session(['vehicle' => $vehicle]);

    $latestRot = $vehicle->rotations()->where('is_setup', false)->orderByDesc('odometer')->first();
    $targetTire = $vehicle->tires()->first();

    // Force a high wear rate by creating a new rotation with only a 100mi gap and low tread.
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
            'tread_center' => $p->tire_id === $targetTire->id ? 3.0 : $p->tread_center,
        ]);
    }

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('Tires nearing replacement')
        ->assertSeeText($targetTire->label);
});

it('does not show replacement alert when all tires are far from the limit', function () {
    [$user, $vehicle] = vehicleWithHistory();
    session(['vehicle' => $vehicle]);

    // vehicleWithHistory: all tires have tread 8–12, minimal wear → no alert.
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSeeText('Tires nearing replacement');
});

it('redirects to vehicle setup when tires are not fully configured', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['user_id' => $user->id, 'tire_count' => 5]);
    session(['vehicle' => $vehicle]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('vehicles.setuptires.index', $vehicle));
});

it('redirects to vehicle creation when no vehicle exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('vehicles.create'));
});

it('cannot access another user\'s dashboard via vehicle_id URL param', function () {
    [$attacker, $attackerVehicle] = vehicleWithHistory();
    $victim = User::factory()->create();
    $victimVehicle = Vehicle::factory()->for($victim)->create(['tire_count' => 0]);

    session(['vehicle' => $attackerVehicle]);

    $this->actingAs($attacker)
        ->get(route('dashboard', ['vehicle_id' => $victimVehicle->id]))
        ->assertNotFound();
});

it('restores vehicle from DB into session when session is cleared', function () {
    [$user, $vehicle] = vehicleWithHistory();

    DB::table('vehicles')
        ->where('id', $vehicle->id)
        ->update(['last_selected_at' => now(), 'user_id' => $user->id]);

    $this->actingAs($user)
        ->withSession([])
        ->get(route('dashboard'))
        ->assertOk();
});
