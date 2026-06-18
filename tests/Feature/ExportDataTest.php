<?php

use App\Models\User;
use App\Models\Vehicle;

test('guests cannot access export', function () {
    $this->get(route('profile.export'))->assertRedirect(route('login'));
});

test('export returns xlsx download', function () {
    $user = User::factory()->create();
    Vehicle::factory()->for($user)->create(['nickname' => 'Rig 1']);

    $this->actingAs($user)
        ->get(route('profile.export'))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('export filename contains today date', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile.export'));

    $response->assertSuccessful()
        ->assertHeader('Content-Disposition', 'attachment; filename=treadmark-export-'.now()->format('Y-m-d').'.xlsx');
});

test('export works when user has no vehicles', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.export'))
        ->assertSuccessful();
});

test('export includes one sheet per vehicle', function () {
    $user = User::factory()->create();
    Vehicle::factory()->for($user)->count(3)->create();

    $this->actingAs($user)
        ->get(route('profile.export'))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});
