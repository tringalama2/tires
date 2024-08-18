<?php

use App\Actions\SelectVehicle;
use App\Enums\TirePosition;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {

    public ?int $vehicle_id;
    public int $vehicle_tire_count;
    public $vehicle;
    public $frontLeftTire;
    public $frontRightTire;
    public $rearLeftTire;
    public $rearRightTire;
    public $spareTire;

    public function mount(SelectVehicle $selectVehicle): void
    {
        if (isset($this->vehicle_id)) {
            $this->vehicle = Vehicle::findOrFail($this->vehicle_id);
            $selectVehicle($this->vehicle);
        } else {
            $this->vehicle = session('vehicle');
        }

        $this->vehicle_tire_count = $this->vehicle->tire_count;

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
    }

    public function rotate(
        $frontLeftPositionTireId,
        $frontRightPositionTireId,
        $rearLeftPositionTireId,
        $rearRightPositionTireId,
        $sparePositionTireId
    ): bool {
        return true;
    }

} ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Rotate') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between">
                        <div class="text-blue-600 text-3xl mb-4">
                            {{ $vehicle->nickname }}
                        </div>
                        <div>
                            <x-primary-button
                                x-data="rotation"
                                @click="complete">Complete Rotation
                            </x-primary-button>
                        </div>
                    </div>
                    <div drag-root class="grid grid-cols-4 grid-rows-3 gap-4">
                        <div drag-position="{{ TirePosition::FrontLeft->camel() }}" class="justify-self-center bg-gray-300 border border-gray-500 p-2 w-44 h-40">
                            <x-rotate::tire-draggable
                                :position="TirePosition::FrontLeft"
                                :tire="$frontLeftTire"
                                color="text-blue-600"
                            />
                        </div>
                        <div class="row-span-2 justify-self-center">
                            <x-img.car-top-view class="w-64"/>
                        </div>
                        <div drag-position="{{ TirePosition::FrontRight->camel() }}" class="justify-self-center bg-gray-300 border border-gray-500 p-2 w-44 h-40">
                            <x-rotate::tire-draggable
                                :position="TirePosition::FrontRight"
                                :tire="$this->frontRightTire"
                                color="text-indigo-600"
                            />
                        </div>
                        <div drag-garage class="row-span-3 justify-self-center bg-gray-300 border border-gray-500 w-64 h-144">

                        </div>
                        <div drag-position="{{ TirePosition::RearLeft->camel() }}" class="justify-self-center bg-gray-300 border border-gray-500 p-2 w-44 h-40 flex flex-col">
                            <x-rotate::tire-draggable
                                :position="TirePosition::RearLeft"
                                :tire="$rearLeftTire"
                                color="text-cyan-600"
                            />

                        </div>
                        <div drag-position="{{ TirePosition::RearRight->camel() }}" class="justify-self-center bg-gray-300 border border-gray-500 p-2 w-44 h-40 flex flex-col">
                            <x-rotate::tire-draggable
                                :position="TirePosition::RearRight"
                                :tire="$rearRightTire"
                                color="text-green-600"
                            />
                        </div>
                        @if($vehicle_tire_count == 5)
                            <div drag-position="{{ TirePosition::Spare->camel() }}" class="col-start-2 justify-self-center bg-gray-300 border border-gray-500 p-2 w-44 h-40 flex flex-col">
                                <x-rotate::tire-draggable
                                    :position="TirePosition::Spare"
                                    :tire="$spareTire"
                                    color="text-lime-600"
                                />
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    let dragRoot = document.querySelector('[drag-root]')
    let garage = dragRoot.querySelector('[drag-garage]')
    let frontLeftPosition = dragRoot.querySelector('[drag-position=frontLeft]');
    let frontRightPosition = dragRoot.querySelector('[drag-position=frontRight]');
    let rearLeftPosition = dragRoot.querySelector('[drag-position=rearLeft]');
    let rearRightPosition = dragRoot.querySelector('[drag-position=rearRight]');
    let sparePosition = dragRoot.querySelector('[drag-position=spare]');

    let validate = function (garage, ...positions) {
        let emptyGarage = garage.querySelectorAll('[drag-tire]').length === 0
        let positionsFilled = true

        for (let position of positions) {
            if (position.querySelectorAll('[drag-tire]').length !== 1) {
                positionsFilled = false
                break
            }
        }

        return emptyGarage && positionsFilled
    }

    dragRoot.querySelectorAll('[drag-tire]').forEach(el => {
        el.addEventListener('dragstart', e => {
            e.target.setAttribute('dragging', true)
            // console.log('start drag - tire')
        })
    })

    dragRoot.querySelectorAll('[drag-position]').forEach(position => {
        position.addEventListener('dragstart', e => {
            // console.log('start drag - position')
        })

        position.addEventListener('drop', function (e) {
            e.preventDefault();
            this.classList.replace('bg-blue-300', 'bg-gray-300')
            let draggingTire = dragRoot.querySelector('[dragging]')
            let currentTire = this.querySelector('[drag-tire]')
            if (currentTire != null) {
                garage.appendChild(currentTire)
            }
            this.appendChild(draggingTire)


            //let sparePosition = dragRoot.querySelector('[drag - position = spare]')
            //let sparePositionTireId = sparePosition.querySelector('[drag - tire]').getAttribute('drag - tire')
            console.log()


            //$wire.rotate(sparePositionTireId)

        })

        position.addEventListener('dragenter', function (e) {
            this.classList.replace('bg-gray-300', 'bg-blue-300')
            // console.log('dragenter drag - position', this)
            e.preventDefault()
        })

        position.addEventListener('dragover', function (e) {
            this.classList.replace('bg-gray-300', 'bg-blue-300')
            e.preventDefault()
        })

        position.addEventListener('dragleave', function (e) {
            this.classList.replace('bg-blue-300', 'bg-gray-300')
            // console.log('leave drag - position', this)
        })

        position.addEventListener('dragend', e => {
            e.target.removeAttribute('dragging')
            // console.log('end drag - position')
        })
    })


    garage.addEventListener('dragstart', e => {
        //console.log('start drag - garage')
    })

    // As a reminder, arrow functions do not have their own 'this' context.
    garage.addEventListener('drop', function (e) {
        e.preventDefault();
        this.classList.replace('bg-blue-300', 'bg-gray-300')
        let draggingTire = dragRoot.querySelector('[dragging]')
        this.appendChild(draggingTire)
        // console.log('drop drag - garage', this)
    })

    garage.addEventListener('dragenter', function (e) {
        this.classList.replace('bg-gray-300', 'bg-blue-300')
        // console.log('dragenter drag - garage', this)
        e.preventDefault()
    })

    garage.addEventListener('dragover', function (e) {
        this.classList.replace('bg-gray-300', 'bg-blue-300')
        // console.log('dragover', this)
        e.preventDefault()
    })

    garage.addEventListener('dragleave', function (e) {
        this.classList.replace('bg-blue-300', 'bg-gray-300')
        // console.log('leave drag - garage', this)
    })

    garage.addEventListener('dragend', e => {
        e.target.removeAttribute('dragging')
        // console.log('end drag - garage')
    })

    Alpine.data('rotation', () => ({
        valid: true,

        async complete() {
            if (validate(
                garage,
                frontLeftPosition,
                frontRightPosition,
                rearLeftPosition,
                rearRightPosition,
                sparePosition
            )) {
                console.log('valid')
                let response = await $wire.rotate(
                    frontLeftPosition.getAttribute('drag-tire'),
                    frontRightPosition.getAttribute('drag-tire'),
                    rearLeftPosition.getAttribute('drag-tire'),
                    rearRightPosition.getAttribute('drag-tire'),
                    sparePosition.getAttribute('drag-tire')
                )
                console.log(await response)
            }
        }
    }))
</script>
@endscript
