<?php

use App\Actions\PredictCurrentOdometer;
use App\Actions\SelectVehicle;
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
    'starting_tread',
]);

layout('layouts.app');


rules([
    'starting_odometer' => ['required', 'integer', 'between:1,16777215'],
    'rotated_on' => ['required', 'date'],
    'starting_tread' => ['required', 'max:255'],
]);

$next = function () {
    $validated = $this->validate();

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
                        <div>
                            <div>
                                <form wire:submit="next">
                                    <div class="flex flex-col sm:flex-row">
                                        <!-- Rotation Date -->
                                        <div class="mt-4 basis-1/2 sm:pe-8">
                                            <x-input-label for="rotated_on" :value="__('Rotation Date')"/>
                                            <x-text-input wire:model="rotated_on" id="rotated_on" class="block mt-1 w-full" type="date" name="rotated_on" autofocus/>
                                            <x-forms.input-error for="rotated_on" class="mt-2"/>
                                        </div>

                                        <!-- Starting Odometer -->
                                        <div class="mt-4 basis-1/2">
                                            <x-input-label for="starting_odometer" :value="__('Odometer')"/>
                                            <x-text-input wire:model="starting_odometer" id="starting_odometer" class="block mt-1 w-full" type="number" name="starting_odometer"/>
                                            <x-forms.input-error for="starting_odometer" class="mt-2"/>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 sm:grid-cols-3 sm:grid-rows-3 gap-4 mt-4">
                                        <x-rotate::tread-input :position="TirePosition::FrontLeft"/>
                                        <div class="hidden sm:block row-span-2 justify-self-center">
                                            <x-img.car-top-view class="w-56"/>
                                        </div>
                                        <x-rotate::tread-input :position="TirePosition::FrontRight"/>
                                        <x-rotate::tread-input :position="TirePosition::RearLeft"/>
                                        <x-rotate::tread-input :position="TirePosition::RearRight"/>
                                        @if($vehicle_tire_count == 5)
                                            <x-rotate::tread-input :position="TirePosition::Spare" class="col-start-2"/>
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
