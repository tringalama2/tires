<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <form wire:submit="register" class="space-y-5">
        <x-treadmark.input
            wire:model="first_name"
            id="first_name"
            type="text"
            label="First Name"
            name="first_name"
            required
            autofocus
            autocomplete="first_name"
            :error="$errors->first('first_name')"
        />

        <x-treadmark.input
            wire:model="last_name"
            id="last_name"
            type="text"
            label="Last Name"
            name="last_name"
            required
            autocomplete="last_name"
            :error="$errors->first('last_name')"
        />

        <x-treadmark.input
            wire:model="email"
            id="email"
            type="email"
            label="Email"
            name="email"
            required
            autocomplete="username"
            :error="$errors->first('email')"
        />

        <x-treadmark.input
            wire:model="password"
            id="password"
            type="password"
            label="Password"
            name="password"
            required
            autocomplete="new-password"
            :error="$errors->first('password')"
        />

        <x-treadmark.input
            wire:model="password_confirmation"
            id="password_confirmation"
            type="password"
            label="Confirm Password"
            name="password_confirmation"
            required
            autocomplete="new-password"
            :error="$errors->first('password_confirmation')"
        />

        <div class="flex items-center justify-end gap-4">
            <a class="text-sm text-steel-500 hover:text-steel-400 transition-colors" href="{{ route('login') }}" wire:navigate>
                {{ __('Already registered?') }}
            </a>

            <x-treadmark.button type="submit">
                {{ __('Register') }}
            </x-treadmark.button>
        </div>
    </form>
</div>
