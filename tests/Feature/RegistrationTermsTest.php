<?php

use App\Models\User;
use Livewire\Livewire;

test('registration requires terms acceptance', function () {
    Livewire::test('pages.auth.register')
        ->set('first_name', 'Jane')
        ->set('last_name', 'Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('terms', false)
        ->call('register')
        ->assertHasErrors(['terms']);
});

test('registration records terms_accepted_at timestamp', function () {
    Livewire::test('pages.auth.register')
        ->set('first_name', 'Jane')
        ->set('last_name', 'Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('terms', true)
        ->call('register');

    $user = User::where('email', 'jane@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->terms_accepted_at)->not->toBeNull();
});
