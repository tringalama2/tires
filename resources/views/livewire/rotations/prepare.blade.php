<?php

use App\Actions\PredictCurrentOdometer;
use App\Actions\SelectVehicle;
use App\Models\Tire;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use App\Enums\TirePosition;
use function Livewire\Volt\{layout, mount, rules, state};

state([
    'vehicle_id',
    'vehicle',
    'vehicle_tire_count',
    'starting_odometer',
    'rotated_on' => fn() => Carbon::today()->toDateString(),
    sprintf('starting_tread_%s', TirePosition::FrontLeft->snake()),
    sprintf('starting_tread_%s', TirePosition::FrontRight->snake()),
    sprintf('starting_tread_%s', TirePosition::RearLeft->snake()),
    sprintf('starting_tread_%s', TirePosition::RearRight->snake()),
    sprintf('starting_tread_%s', TirePosition::Spare->snake()),
    'frontLeftTire',
    'frontRightTire',
    'rearLeftTire',
    'rearRightTire',
    'spareTire',
]);

layout('layouts.app');


rules([
    'starting_odometer' => ['required', 'integer', 'between:1,16777215'],
    'rotated_on' => ['required', 'date'],
    sprintf('starting_tread_%s', TirePosition::FrontLeft->snake()) => ['required', 'integer', 'between:0,255'],
    sprintf('starting_tread_%s', TirePosition::FrontRight->snake()) => ['required', 'integer', 'between:0,255'],
    sprintf('starting_tread_%s', TirePosition::RearLeft->snake()) => ['required', 'integer', 'between:0,255'],
    sprintf('starting_tread_%s', TirePosition::RearRight->snake()) => ['required', 'integer', 'between:0,255'],
    sprintf('starting_tread_%s', TirePosition::Spare->snake()) => ['required', 'integer', 'between:0,255'],
]);

$next = function () {
    $validated = $this->validate();

    session(['rotation.starting_odometer' => $validated['starting_odometer']]);
    session(['rotation.rotated_on' => $validated['rotated_on']]);
    session([
        sprintf('rotation.starting_tread_%s',
            TirePosition::FrontLeft->snake()) => $validated[sprintf('starting_tread_%s',
            TirePosition::FrontLeft->snake())]
    ]);
    session([
        sprintf('rotation.starting_tread_%s',
            TirePosition::FrontRight->snake()) => $validated[sprintf('starting_tread_%s',
            TirePosition::FrontRight->snake())]
    ]);
    session([
        sprintf('rotation.starting_tread_%s',
            TirePosition::RearLeft->snake()) => $validated[sprintf('starting_tread_%s',
            TirePosition::RearLeft->snake())]
    ]);
    session([
        sprintf('rotation.starting_tread_%s',
            TirePosition::RearRight->snake()) => $validated[sprintf('starting_tread_%s',
            TirePosition::RearRight->snake())]
    ]);
    session([
        sprintf('rotation.starting_tread_%s', TirePosition::Spare->snake()) => $validated[sprintf('starting_tread_%s',
            TirePosition::Spare->snake())]
    ]);

    $this->redirect(route('rotations.update'), navigate: true);
};

mount(function (SelectVehicle $selectVehicle, PredictCurrentOdometer $predictCurrentOdometer) {
    if (isset($this->vehicle_id)) {
        $this->vehicle = Vehicle::findOrFail($this->vehicle_id);
        $selectVehicle($this->vehicle);
    } else {
        $this->vehicle = session('vehicle');
    }

    $this->vehicle_tire_count = $this->vehicle->tire_count;

    $this->starting_odometer = $predictCurrentOdometer($this->vehicle);

    $this->frontLeftTire = Tire::installed()->where('vehicle_id',
        $this->vehicle->id)->currentRotationByPosition(TirePosition::FrontLeft)->first();
    $this->frontRightTire = Tire::installed()->where('vehicle_id',
        $this->vehicle->id)->currentRotationByPosition(TirePosition::FrontRight)->first();
    $this->rearLeftTire = Tire::installed()->where('vehicle_id',
        $this->vehicle->id)->currentRotationByPosition(TirePosition::RearLeft)->first();
    $this->rearRightTire = Tire::installed()->where('vehicle_id',
        $this->vehicle->id)->currentRotationByPosition(TirePosition::RearRight)->first();
    $this->spareTire = Tire::installed()->where('vehicle_id',
        $this->vehicle->id)->currentRotationByPosition(TirePosition::Spare)->first();
});

?>


<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Rotate') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                <div class="max-w-xl">

                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between">
                            <div class="text-blue-600 text-3xl mb-4">
                                {{ $vehicle->nickname }}
                            </div>
                        </div>
                        @if ($errors->any())
                            <div class="text-red-600">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div>
                            <div>
                                <form wire:submit="next">
                                    <div class="flex flex-col sm:flex-row">
                                        <!-- Rotation Date -->
                                        <div class="mt-4 basis-1/2 sm:pe-8">
                                            <x-input-label for="rotated_on" :value="__('Rotation Date')"/>
                                            <x-text-input wire:model="rotated_on" id="rotated_on" class="block mt-1 w-full" type="date" name="rotated_on" autofocus required/>
                                        </div>

                                        <!-- Starting Odometer -->
                                        <div class="mt-4 basis-1/2">
                                            <x-input-label for="starting_odometer" :value="__('Odometer')"/>
                                            <x-text-input wire:model="starting_odometer" id="starting_odometer" class="block mt-1 w-full" type="number" name="starting_odometer" required/>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 sm:grid-cols-3 sm:grid-rows-3 gap-4 mt-4">
                                        <x-rotate::tread-input :position="TirePosition::FrontLeft"
                                                               :tire="$frontLeftTire"/>
                                        <div class="hidden sm:block row-span-2 justify-self-center">
                                            <x-img.car-top-view class="w-56"/>
                                        </div>
                                        <x-rotate::tread-input :position="TirePosition::FrontRight"
                                                               :tire="$frontRightTire"/>
                                        <x-rotate::tread-input :position="TirePosition::RearLeft"
                                                               :tire="$rearLeftTire"/>
                                        <x-rotate::tread-input :position="TirePosition::RearRight"
                                                               :tire="$rearRightTire"/>
                                        @if($vehicle_tire_count == 5)
                                            <x-rotate::tread-input :position="TirePosition::Spare"
                                                                   :tire="$spareTire" class="col-start-2"/>
                                        @endif
                                    </div>

                                    <div class="flex items-center justify-end mt-4">
                                        <x-primary-button class="ms-4">
                                            {{ __('Next') }}
                                        </x-primary-button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</div>
