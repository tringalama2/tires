@use('App\Enums\TirePosition')
<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="text-blue-600 text-3xl mb-4">
                        Last Rotated on {{ $this->latestRotation->rotated_on->format('M j, Y') }}
                        @ {{ number_format($this->latestRotation->starting_odometer) }}
                        miles.
                    </div>
                    <div class="grid grid-cols-3 grid-rows-3 gap-4">
                        <div class="justify-self-center">
                            <livewire:tire-position-details
                                :vehicle_id="$vehicle_id"
                                :position="TirePosition::FrontLeft"/>
                        </div>
                        <div class="row-span-2 justify-self-center">
                            <x-img.car-top-view class="w-64"/>
                        </div>
                        <div class="justify-self-center">
                            <livewire:tire-position-details
                                :vehicle_id="$vehicle_id"
                                :position="TirePosition::FrontRight"/>
                        </div>
                        <div class="justify-self-center">
                            <livewire:tire-position-details
                                :vehicle_id="$vehicle_id"
                                :position="TirePosition::RearLeft"/>
                        </div>
                        <div class="justify-self-center">
                            <livewire:tire-position-details
                                :vehicle_id="$vehicle_id"
                                :position="TirePosition::RearRight"/>
                        </div>
                        @if($vehicle_tire_count == 5)
                        <div class="col-start-2 justify-self-center">
                            <livewire:tire-position-details
                                :vehicle_id="$vehicle_id"
                                :position="TirePosition::Spare"/>
                        </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
