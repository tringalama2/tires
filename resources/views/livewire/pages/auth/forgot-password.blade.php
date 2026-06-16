<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

<div>
    <div class="mb-4 text-sm text-ink-400">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    @if (session('status'))
        <x-treadmark.alert tone="success" class="mb-4">{{ session('status') }}</x-treadmark.alert>
    @endif

    <form wire:submit="sendPasswordResetLink" class="space-y-5">
        <x-treadmark.input
            wire:model="email"
            id="email"
            type="email"
            label="Email"
            name="email"
            required
            autofocus
            :error="$errors->first('email')"
        />

        <div class="flex items-center justify-end">
            <x-treadmark.button type="submit">
                {{ __('Email Password Reset Link') }}
            </x-treadmark.button>
        </div>
    </form>
</div>
