<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    @if (session('status'))
        <x-treadmark.alert tone="success" class="mb-4">{{ session('status') }}</x-treadmark.alert>
    @endif

    <form wire:submit="login" class="space-y-5">
        <x-treadmark.input
            wire:model="form.email"
            id="email"
            type="email"
            label="Email"
            name="email"
            required
            autofocus
            autocomplete="username"
            :error="$errors->first('form.email')"
        />

        <x-treadmark.input
            wire:model="form.password"
            id="password"
            type="password"
            label="Password"
            name="password"
            required
            autocomplete="current-password"
            :error="$errors->first('form.password')"
        />

        <div class="flex items-center justify-between">
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input wire:model="form.remember"
                       id="remember"
                       type="checkbox"
                       name="remember"
                       class="rounded border-ink-600 bg-ink-700 text-blaze-500 focus:ring-blaze-500/40">
                <span class="text-sm text-ink-300">Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" wire:navigate
                   class="text-sm text-steel-500 hover:text-steel-400 transition-colors">
                    Forgot password?
                </a>
            @endif
        </div>

        <x-treadmark.button type="submit" block class="mt-2">
            <x-treadmark.icon name="arrow-right" class="w-4 h-4" />
            Log in
        </x-treadmark.button>
    </form>
</div>
