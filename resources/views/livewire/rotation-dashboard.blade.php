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
                        Last Rotated on {{ $this->currentRotation->rotated_on->format('M j, Y') }}
                        @ {{ number_format($this->currentRotation->odometer) }}
                        miles.
                    </div>
                    <div class="grid grid-cols-3 grid-rows-3 gap-4">
                        <div class="justify-self-center">
                            <x-tire-position-details
                                :position="TirePosition::FrontLeft"
                                :currentRotation="$this->currentRotation->tiresByPosition(TirePosition::FrontLeft)->first()"
                                :positionHistory="$this->positionHistory(TirePosition::FrontLeft)"
                                :tireHistory="$this->tireHistory($this->currentRotation->tiresByPosition(TirePosition::FrontRight)->first())"/>
                        </div>
                        <div class="row-span-2 justify-self-center">
                            <x-img.car-top-view class="w-64"/>
                        </div>
                        <div class="justify-self-center">
                            <x-tire-position-details
                                :position="TirePosition::FrontRight"
                                :currentRotation="$this->currentRotation->tiresByPosition(TirePosition::FrontRight)->first()"
                                :positionHistory="$this->positionHistory(TirePosition::FrontRight)"
                                :tireHistory="$this->tireHistory($this->currentRotation->tiresByPosition(TirePosition::FrontRight)->first())"/>
                        </div>
                        <div class="justify-self-center">
                            <x-tire-position-details
                                :position="TirePosition::RearLeft"
                                :currentRotation="$this->currentRotation->tiresByPosition(TirePosition::RearLeft)->first()"
                                :positionHistory="$this->positionHistory(TirePosition::RearLeft)"
                                :tireHistory="$this->tireHistory($this->currentRotation->tiresByPosition(TirePosition::FrontRight)->first())"/>
                        </div>
                        <div class="justify-self-center">
                            <x-tire-position-details
                                :position="TirePosition::RearRight"
                                :currentRotation="$this->currentRotation->tiresByPosition(TirePosition::RearRight)->first()"
                                :positionHistory="$this->positionHistory(TirePosition::RearRight)"
                                :tireHistory="$this->tireHistory($this->currentRotation->tiresByPosition(TirePosition::FrontRight)->first())"/>
                        </div>
                        <div class="col-start-2 justify-self-center">
                            <x-tire-position-details
                                :position="TirePosition::Spare"
                                :currentRotation="$this->currentRotation->tiresByPosition(TirePosition::Spare)->first()"
                                :positionHistory="$this->positionHistory(TirePosition::Spare)"
                                :tireHistory="$this->tireHistory($this->currentRotation->tiresByPosition(TirePosition::FrontRight)->first())"/>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
