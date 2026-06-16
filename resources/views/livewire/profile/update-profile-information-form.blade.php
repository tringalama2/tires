<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->first_name = Auth::user()->first_name;
        $this->last_name = Auth::user()->last_name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->first_name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-ink-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-ink-500">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        <x-treadmark.input
            wire:model="first_name"
            id="first_name"
            name="first_name"
            type="text"
            label="First Name"
            required
            autofocus
            autocomplete="first_name"
            :error="$errors->first('first_name')"
        />

        <x-treadmark.input
            wire:model="last_name"
            id="last_name"
            name="last_name"
            type="text"
            label="Last Name"
            required
            autocomplete="last_name"
            :error="$errors->first('last_name')"
        />

        <div>
            <x-treadmark.input
                wire:model="email"
                id="email"
                name="email"
                type="email"
                label="Email"
                required
                autocomplete="username"
                :error="$errors->first('email')"
            />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="text-sm text-ink-700">
                        {{ __('Your email address is unverified.') }}

                        <button wire:click.prevent="sendVerification" class="underline text-sm text-ink-500 hover:text-ink-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blaze-500/40">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <x-treadmark.alert tone="success" class="mt-2">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </x-treadmark.alert>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-treadmark.button type="submit">{{ __('Save') }}</x-treadmark.button>

            <x-treadmark.flash on="profile-updated">{{ __('Saved.') }}</x-treadmark.flash>
        </div>
    </form>
</section>
