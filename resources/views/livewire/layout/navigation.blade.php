<?php

use App\Livewire\Actions\Logout;
use Livewire\Component;

new class extends Component {
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="bg-white border-b border-ink-100 shadow-tm-sm">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-14">

            {{-- Logo + nav links --}}
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" wire:navigate class="shrink-0">
                    <x-treadmark.logo />
                </a>

                <div class="hidden sm:flex items-center gap-1">
                    @php
                        $linkBase = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-control text-sm font-medium transition-colors duration-150';
                        $linkActive = 'bg-ink-50 text-ink-900';
                        $linkInactive = 'text-ink-500 hover:text-ink-900 hover:bg-ink-50';
                    @endphp

                    <a href="{{ route('dashboard') }}" wire:navigate
                       class="{{ $linkBase }} {{ request()->routeIs('dashboard') ? $linkActive : $linkInactive }}">
                        <x-treadmark.icon name="gauge" class="w-4 h-4" />
                        Dashboard
                    </a>

                    <a href="{{ route('rotations.prepare') }}" wire:navigate
                       class="{{ $linkBase }} {{ request()->routeIs('rotations.*') ? $linkActive : $linkInactive }}">
                        <x-treadmark.icon name="arrows-clockwise" class="w-4 h-4" />
                        Rotate
                    </a>

                    <a href="{{ route('tires.index') }}" wire:navigate
                       class="{{ $linkBase }} {{ request()->routeIs(['tires.index', 'tires.show']) ? $linkActive : $linkInactive }}">
                        <x-treadmark.icon name="tire" class="w-4 h-4" />
                        Tires
                    </a>

                    {{-- Reports dropdown --}}
                    <div x-data="{ reportsOpen: false }" class="relative">
                        <button @click="reportsOpen = !reportsOpen" @click.outside="reportsOpen = false"
                                class="{{ $linkBase }} {{ request()->routeIs('reports.*') ? $linkActive : $linkInactive }}">
                            <x-treadmark.icon name="chart-bar" class="w-4 h-4" />
                            Reports
                            <x-treadmark.icon name="caret-down" class="w-3 h-3" />
                        </button>
                        <div x-show="reportsOpen" x-transition x-cloak
                             class="absolute top-full left-0 mt-1 w-44 bg-white border border-ink-100 rounded-card shadow-tm-md z-50">
                            <a href="{{ route('reports.by-position') }}" wire:navigate
                               class="flex items-center gap-2 px-4 py-2.5 text-sm text-ink-700 hover:bg-ink-50 first:rounded-t-card {{ request()->routeIs('reports.by-position') ? 'font-semibold text-ink-900' : '' }}">
                                By Position
                            </a>
                            <a href="{{ route('reports.by-tire') }}" wire:navigate
                               class="flex items-center gap-2 px-4 py-2.5 text-sm text-ink-700 hover:bg-ink-50 last:rounded-b-card {{ request()->routeIs('reports.by-tire') ? 'font-semibold text-ink-900' : '' }}">
                                By Tire
                            </a>
                        </div>
                    </div>

                    <a href="{{ route('vehicles.index') }}" wire:navigate
                       class="{{ $linkBase }} {{ request()->routeIs(['vehicles.index', 'vehicles.create']) ? $linkActive : $linkInactive }}">
                        <x-treadmark.icon name="jeep" class="w-4 h-4" />
                        Vehicles
                    </a>
                </div>
            </div>

            {{-- Right side: vehicle chip + user menu --}}
            <div class="hidden sm:flex items-center gap-3">
                {{-- Selected vehicle chip --}}
                @if(session('vehicle'))
                    <a href="{{ route('vehicles.index') }}" wire:navigate
                       class="inline-flex items-center gap-1.5 text-sm font-medium text-ink-700 bg-ink-50 border border-ink-100 px-3 py-1.5 rounded-pill hover:bg-ink-100 transition-colors">
                        <x-treadmark.icon name="jeep" class="w-4 h-4 text-ink-400" />
                        <span x-data="{{ json_encode(['nickname' => session('vehicle')?->nickname]) }}"
                              x-text="nickname"
                              x-on:new-vehicle-selected.window="nickname = $event.detail.nickname"></span>
                    </a>
                @endif

                {{-- User dropdown --}}
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-control text-sm font-medium text-ink-500 hover:text-ink-900 hover:bg-ink-50 transition-colors border border-transparent hover:border-ink-100">
                            <x-treadmark.icon name="user" class="w-4 h-4" />
                            <span x-data="{{ json_encode(['first_name' => auth()->user()->first_name]) }}"
                                  x-text="first_name"
                                  x-on:profile-updated.window="first_name = $event.detail.first_name"></span>
                            <x-treadmark.icon name="caret-down" class="w-3 h-3" />
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            Profile
                        </x-dropdown-link>
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>Log Out</x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- Mobile hamburger --}}
            <div class="flex items-center sm:hidden">
                <button @click="open = !open"
                        class="p-2 rounded-control text-ink-400 hover:text-ink-700 hover:bg-ink-50 transition-colors">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open}" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div :class="{'block': open, 'hidden': !open}" class="hidden sm:hidden border-t border-ink-100 bg-white">
        <div class="pt-2 pb-3 space-y-1 px-3">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                Dashboard
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('rotations.prepare')" :active="request()->routeIs('rotations.*')" wire:navigate>
                Rotate
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('tires.index')" :active="request()->routeIs(['tires.index', 'tires.show'])" wire:navigate>
                Tires
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('reports.by-position')" :active="request()->routeIs('reports.by-position')" wire:navigate>
                Report: By Position
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('reports.by-tire')" :active="request()->routeIs('reports.by-tire')" wire:navigate>
                Report: By Tire
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('vehicles.index')" :active="request()->routeIs(['vehicles.index', 'vehicles.create'])" wire:navigate>
                Vehicles
            </x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-3 border-t border-ink-100 px-3">
            <div class="px-3 mb-3">
                <div class="text-sm font-semibold text-ink-900"
                     x-data="{{ json_encode(['first_name' => auth()->user()->first_name]) }}"
                     x-text="first_name"
                     x-on:profile-updated.window="first_name = $event.detail.first_name"></div>
                <div class="text-xs text-ink-400">{{ auth()->user()->email }}</div>
            </div>
            @if(session('vehicle'))
                <div class="px-3 mb-2 text-xs font-medium text-ink-400 uppercase tracking-caps">Vehicle</div>
                <x-responsive-nav-link :href="route('vehicles.index')" wire:navigate>
                    <span x-data="{{ json_encode(['nickname' => session('vehicle')?->nickname]) }}"
                          x-text="nickname"
                          x-on:new-vehicle-selected.window="nickname = $event.detail.nickname"></span>
                </x-responsive-nav-link>
            @endif
            <x-responsive-nav-link :href="route('profile')" wire:navigate>Profile</x-responsive-nav-link>
            <button wire:click="logout" class="w-full text-start">
                <x-responsive-nav-link>Log Out</x-responsive-nav-link>
            </button>
        </div>
    </div>
</nav>
