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
use Livewire\Volt\Component;

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
            <x-auth-session-status class="mb-4" :status="session('status')"/>

            <div class="max-w-xl">
                <header>
                    <h2 class="text-lg font-medium text-gray-900">
                        Select a vehicle or add a new one.
                    </h2>
                </header>
            </div>
        </div>

        @foreach($vehicles as $vehicle)
            <div class="p-4 sm:p-8 bg-white shadow hover:shadow-blue-600/50 sm:rounded-lg">

                    <div class="flex justify-between">


                        <div class="max-w-xl flex flex-row gap-x-8">
                            <a wire:click="changeVehicle({{$vehicle->id}})" class="cursor-pointer">
                                <div class="@if($vehicle->is(session('vehicle'))) text-blue-600 @else text-gray-300 @endif">
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
                                        {{ $vehicle->loadCount('installedTires')->installed_tires_count }}
                                    </span>
                                    <span>Tires Installed</span>
                                </div>
                                @if ($vehicle->loadCount('installedTires')->installed_tires_count < $vehicle->tire_count)
                                    <div>
                                        <a href="{{ route('vehicles.setuptires.index', $vehicle) }}"
                                        class="btn-dark-red text-xs ms-4">Finish Setup</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-col md:flex-row gap-x-2">
                            <div>
                                <a href="{{ route('dashboard', $vehicle) }}">
                                    <x-phosphor-tire-duotone class="w-8 h-8 inline text-blue-600"/>
                                    Dashboard
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('vehicles.edit', $vehicle) }}">
                                    <x-phosphor-note-pencil-duotone class="w-8 h-8 inline text-blue-600"/>
                                    Edit
                                </a>
                            </div>
                        </div>
                    </div>
            </div>
        @endforeach

        @can('create', Vehicle::class)
        <div class="p-4 sm:p-8 bg-white shadow hover:shadow-blue-600/50 sm:rounded-lg">
            <a href="{{ route('vehicles.create') }}">
                <div class="flex justify-between">
                    <div class="max-w-xl text-blue-600 flex gap-x-4">
                        <div>
                            <x-phosphor-jeep-duotone class="w-8 h-8 inline"/>
                        </div>
                        <div>
                            Add a Vehicle
                        </div>
                    </div>
                    <div>
                        <x-phosphor-file-plus-duotone class="w-8 h-8 inline text-blue-600"/>
                    </div>
                </div>
            </a>
        </div>
        @endif
    </div>
</div>
