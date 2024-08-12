<?php

use App\Actions\SelectVehicle;
use App\Enums\TirePosition;
use App\Models\Vehicle;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {

    public ?int $vehicle_id;
    public int $vehicle_tire_count;
    protected Vehicle $vehicle;

    public function mount(SelectVehicle $selectVehicle): void
    {
        if (isset($this->vehicle_id)) {
            $this->vehicle = Vehicle::findOrFail($this->vehicle_id);
            $selectVehicle($this->vehicle);
        } else {
            $this->vehicle = session('vehicle');
        }

        $this->vehicle_tire_count = $this->vehicle->tire_count;
    }

    public function rotate()
    {
        //
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
                    <div class="text-blue-600 text-3xl mb-4">
                        Vehicle name
                    </div>
                    <div drag-root class="grid grid-cols-4 grid-rows-3 gap-4">
                        <div drag-position class="justify-self-center bg-gray-300 border border-gray-500 p-2 w-44 h-40">
                            <x-rotate::tire
                                :position="TirePosition::FrontLeft"
                                tire="Tire 1"
                                color="text-blue-600"
                            />
                        </div>
                        <div class="row-span-2 justify-self-center">
                            <x-img.car-top-view class="w-64"/>
                        </div>
                        <div drag-position class="justify-self-center bg-gray-300 border border-gray-500 p-2 w-44 h-40">
                            <x-rotate::tire
                                :position="TirePosition::FrontRight"
                                tire="Tire 2"
                                color="text-indigo-600"
                            />
                        </div>
                        <div drag-garage class="row-span-3 justify-self-center bg-gray-300 border border-gray-500 w-64 h-128">

                        </div>
                        <div drag-position class="justify-self-center bg-gray-300 border border-gray-500 p-2 w-44 h-40 flex flex-col">
                            <x-rotate::tire
                                :position="TirePosition::RearLeft"
                                tire="Tire 3"
                                color="text-cyan-600"
                            />

                        </div>
                        <div drag-position class="justify-self-center bg-gray-300 border border-gray-500 p-2 w-44 h-40 flex flex-col">
                            <x-rotate::tire
                                :position="TirePosition::RearRight"
                                tire="Tire 4"
                                color="text-green-600"
                            />
                        </div>
                        @if($vehicle_tire_count == 5)
                            <div drag-position class="col-start-2 justify-self-center bg-gray-300 border border-gray-500 p-2 w-44 h-40 flex flex-col">
                                <x-rotate::tire
                                    :position="TirePosition::Spare"
                                    tire="Tire 5"
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

@push('endScripts')
    <script>
        let dragRoot = document.querySelector('[drag-root]')
        let garage = dragRoot.querySelector('[drag-garage]')

        dragRoot.querySelectorAll('[drag-tire]').forEach(el => {
            el.addEventListener('dragstart', e => {
                e.target.setAttribute('dragging', true)
                // console.log('start drag-tire')
            })
        })

        dragRoot.querySelectorAll('[drag-position]').forEach(position => {
            position.addEventListener('dragstart', e => {
                // console.log('start drag-position')
            })

            position.addEventListener('drop', function (e) {
                e.preventDefault();
                this.classList.remove('bg-blue-300')
                let draggingTire = dragRoot.querySelector('[dragging]')
                let currentTire = this.querySelector('[drag-tire]')
                if (currentTire != null) {
                    garage.appendChild(currentTire)
                }
                this.appendChild(draggingTire)

                // console.log('drop drag-position', this)
            })

            position.addEventListener('dragenter', function (e) {
                this.classList.add('bg-blue-300')
                // console.log('dragenter drag-position', this)
                e.preventDefault()
            })

            position.addEventListener('dragover', function (e) {
                this.classList.add('bg-blue-300')
                e.preventDefault()
            })

            position.addEventListener('dragleave', function (e) {
                this.classList.remove('bg-blue-300')
                // console.log('leave drag-position', this)
            })

            position.addEventListener('dragend', e => {
                e.target.removeAttribute('dragging')
                // console.log('end drag-position')
            })
        })


        garage.addEventListener('dragstart', e => {
            //console.log('start drag-garage')
        })

        // As a reminder, arrow functions do not have their own 'this' context.
        garage.addEventListener('drop', function (e) {
            e.preventDefault();
            this.classList.remove('bg-blue-300')
            let draggingTire = dragRoot.querySelector('[dragging]')
            this.appendChild(draggingTire)
            // console.log('drop drag-garage', this)
        })

        garage.addEventListener('dragenter', function (e) {
            this.classList.add('bg-blue-300')
            // console.log('dragenter drag-garage', this)
            e.preventDefault()
        })

        garage.addEventListener('dragover', function (e) {
            this.classList.add('bg-blue-300')
            // console.log('dragover', this)
            e.preventDefault()
        })

        garage.addEventListener('dragleave', function (e) {
            this.classList.remove('bg-blue-300')
            // console.log('leave drag-garage', this)
        })

        garage.addEventListener('dragend', e => {
            e.target.removeAttribute('dragging')
            // console.log('end drag-garage')
        })
    </script>
@endpush
