<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(string $token): void
    {
        $this->token = $token;

        $this->email = request()->string('email');
    }

    /**
     * Reset the password for the given user.
     */
    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status != Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        Session::flash('status', __($status));

        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<div>
    <form wire:submit="resetPassword" class="space-y-5">
        <x-treadmark.input
            wire:model="email"
            id="email"
            type="email"
            label="Email"
            name="email"
            required
            autofocus
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

        <div class="flex items-center justify-end">
            <x-treadmark.button type="submit">
                {{ __('Reset Password') }}
            </x-treadmark.button>
        </div>
    </form>
</div>
