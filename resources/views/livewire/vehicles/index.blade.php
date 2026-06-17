<?php

use App\Actions\SelectVehicle;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {
    public Collection $vehicles;

    protected $listeners = ['changeVehicle' => '$refresh'];

    public function changeVehicle(SelectVehicle $selectVehicle, Vehicle $vehicle): void
    {
        $this->authorize('update', $vehicle);

        $selectVehicle($vehicle);

        $this->dispatch('new-vehicle-selected', nickname: $vehicle->nickname);
    }

    public function mount()
    {
        $this->vehicles = Vehicle::where('user_id', Auth::id())->get();
    }
};
?>

<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        My Vehicles
    </h2>
</x-slot>


<div class="py-12">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="p-4 sm:px-8 bg-white shadow sm:rounded-lg">

            <!-- Session Status -->
            @if (session('status'))
        <x-treadmark.alert tone="success" class="mb-4">{{ session('status') }}</x-treadmark.alert>
    @endif

            <div class="max-w-xl">
                <header>
                    <h2 class="text-lg font-medium text-gray-900">
                        Select a vehicle or add a new one.
                    </h2>
                </header>
            </div>
        </div>

        @foreach($vehicles as $vehicle)
            <div class="p-4 sm:p-8 bg-white shadow hover:shadow-tm-md sm:rounded-lg">

                <div class="flex justify-between">


                    <div class="max-w-xl flex flex-row gap-x-8">
                        <a wire:click="changeVehicle('{{ $vehicle->getRouteKey() }}')" class="cursor-pointer">
                            <div class="@if($vehicle->is(session('vehicle'))) text-blaze-500 @else text-ink-200 @endif">
                                <x-phosphor-check-circle-duotone class="w-8 h-8 inline"/>
                            </div>
                        </a>
                        <div class="w-32">
                            <h2 class="text-xl font-bold text-gray-800">{{ $vehicle->nickname }}</h2>
                            <div class="text-gray-500 text-sm">
                                {{ $vehicle->year }} {{ $vehicle->make }} {{ $vehicle->model }}
                            </div>
                        </div>
                        <div class="w-48">
                            <div>
                                    <span class="text-lg font-bold text-gray-700 p-2 border-b-gray-300">
                                        {{ $vehicle->loadCount('activeTires')->active_tires_count }}
                                    </span>
                                <span>Active Tires</span>
                            </div>
                            @if (! $vehicle->isSetupComplete())
                                <x-treadmark.button size="sm" href="{{ route('vehicles.setuptires.index', $vehicle) }}" class="mt-1">
                                    Finish Setup
                                </x-treadmark.button>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-col md:flex-row gap-2">
                        <x-treadmark.button variant="ghost" size="sm" href="{{ route('dashboard', $vehicle) }}">
                            <x-treadmark.icon name="gauge" class="w-4 h-4" /> Dashboard
                        </x-treadmark.button>
                        <x-treadmark.button variant="ghost" size="sm" href="{{ route('rotations.prepare', $vehicle) }}">
                            <x-treadmark.icon name="arrows-clockwise" class="w-4 h-4" /> Rotate
                        </x-treadmark.button>
                        <x-treadmark.button variant="ghost" size="sm" href="{{ route('rotations.swap', $vehicle) }}">
                            <x-treadmark.icon name="wrench" class="w-4 h-4" /> Swap
                        </x-treadmark.button>
                        <x-treadmark.button variant="ghost" size="sm" href="{{ route('vehicles.edit', $vehicle) }}">
                            <x-treadmark.icon name="pencil-simple" class="w-4 h-4" /> Edit
                        </x-treadmark.button>
                    </div>
                </div>
            </div>
        @endforeach

        @can('create', Vehicle::class)
            <div class="p-4 sm:p-8 bg-white shadow hover:shadow-tm-md sm:rounded-lg">
                <a href="{{ route('vehicles.create') }}">
                    <div class="flex justify-between">
                        <div class="max-w-xl text-ink-600 flex gap-x-4">
                            <div>
                                <x-phosphor-jeep-duotone class="w-8 h-8 inline"/>
                            </div>
                            <div>
                                Add a Vehicle
                            </div>
                        </div>
                        <div>
                            <x-phosphor-file-plus-duotone class="w-8 h-8 inline text-ink-600"/>
                        </div>
                    </div>
                </a>
            </div>
        @endif
    </div>
</div>
