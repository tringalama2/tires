<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <x-treadmark.button
        variant="danger"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Delete Account') }}</x-treadmark.button>

    <x-treadmark.modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6">

            <h2 class="text-lg font-medium text-ink-900">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="mt-1 text-sm text-ink-500">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-6">
                <x-treadmark.input
                    wire:model="password"
                    id="password"
                    name="password"
                    type="password"
                    label="Password"
                    placeholder="{{ __('Password') }}"
                    :error="$errors->first('password')"
                />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-treadmark.button variant="secondary" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-treadmark.button>

                <x-treadmark.button variant="danger" type="submit">
                    {{ __('Delete Account') }}
                </x-treadmark.button>
            </div>
        </form>
    </x-treadmark.modal>
</section>
