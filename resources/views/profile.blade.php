<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold uppercase tracking-wider text-lg text-ink-900">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-6">

            {{-- Profile Information --}}
            <div class="bg-white border border-ink-100 rounded-card shadow-tm-sm p-6">
                <livewire:profile.update-profile-information-form />
            </div>

            {{-- Update Password --}}
            <div class="bg-white border border-ink-100 rounded-card shadow-tm-sm p-6">
                <livewire:profile.update-password-form />
            </div>

            {{-- Export Data --}}
            <div class="bg-white border border-ink-100 rounded-card shadow-tm-sm p-6">
                <header>
                    <h2 class="text-lg font-medium text-ink-900">{{ __('Export My Data') }}</h2>
                    <p class="mt-1 text-sm text-ink-500">
                        {{ __('Download a spreadsheet of all your rotation history. One worksheet per vehicle, named by nickname.') }}
                    </p>
                </header>
                <div class="mt-6">
                    <a href="{{ route('profile.export') }}"
                       class="inline-flex items-center gap-2 font-display font-semibold uppercase tracking-wider2 text-[15px] px-[18px] py-[10px] rounded-control transition-colors duration-150 bg-transparent text-ink-900 border border-ink-200 hover:bg-ink-50 hover:border-ink-300">
                        <x-treadmark.icon name="download-simple" class="w-4 h-4" />
                        {{ __('Download .xlsx') }}
                    </a>
                </div>
            </div>

            {{-- Delete Account --}}
            <div class="bg-white border border-rust-100 rounded-card shadow-tm-sm p-6">
                <livewire:profile.delete-user-form />
            </div>

        </div>
    </div>
</x-app-layout>
