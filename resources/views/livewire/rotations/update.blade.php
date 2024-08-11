<?php

use App\Enums\TirePosition;
use App\Models\Vehicle;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public int $vehicle_tire_count;
    protected Vehicle $vehicle;

    public function mount(): void
    {
        $this->vehicle = session('vehicle');
        $this->vehicle_tire_count = session('vehicle')->tire_count;
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
                        <div drag-position class="justify-self-center bg-gray-300 border border-gray-500 p-2 w-32 h-40 flex flex-col">
                            <div class="self-center font-semibold uppercase tracking-tight text-sm text-gray-800 border-b border-gray-500">
                                {{ TirePosition::FrontLeft->label() }}
                            </div>
                            <div drag-tire draggable="true" class="m-2 flex flex-col">
                                <x-phosphor-tire-duotone
                                    class="w-24 h-24 inline text-blue-600 self-center"
                                    id="tire1"/>
                                <div class="self-center font-semibold uppercase tracking-tight text-xs text-gray-800">
                                    Tire 1
                                </div>
                            </div>
                        </div>
                        <div class="row-span-2 justify-self-center">
                            <x-img.car-top-view class="w-64"/>
                        </div>
                        <div drag-position class="justify-self-center bg-gray-300 border border-gray-500 p-2 w-32 h-40 flex flex-col">
                            <div class="self-center font-semibold uppercase tracking-tight text-sm text-gray-800 border-b border-gray-500">
                                {{ TirePosition::FrontRight->label() }}
                            </div>
                            <div drag-tire draggable="true" class="m-2 flex flex-col">
                                <x-phosphor-tire-duotone
                                    class="w-24 h-24 inline text-blue-600 self-center"
                                    id="tire2"/>
                                <div class="self-center font-semibold uppercase tracking-tight text-xs text-gray-800">
                                    Tire 2
                                </div>
                            </div>
                        </div>
                        <div drag-garage class="row-span-3 justify-self-center bg-gray-300 border border-gray-500 w-64 h-96">

                        </div>
                        <div drag-position class="justify-self-center bg-gray-300 border border-gray-500 p-2 w-32 h-40 flex flex-col">
                            <div class="self-center font-semibold uppercase tracking-tight text-sm text-gray-800 border-b border-gray-500">
                                {{ TirePosition::RearLeft->label() }}
                            </div>
                            <div drag-tire draggable="true" class="m-2 flex flex-col">
                                <x-phosphor-tire-duotone
                                    class="w-24 h-24 inline text-blue-600 self-center"
                                    id="tire3"/>
                                <div class="self-center font-semibold uppercase tracking-tight text-xs text-gray-800">
                                    Tire 3
                                </div>
                            </div>
                        </div>
                        <div drag-position class="justify-self-center bg-gray-300 border border-gray-500 p-2 w-32 h-40 flex flex-col">
                            <div class="self-center font-semibold uppercase tracking-tight text-sm text-gray-800 border-b border-gray-500">
                                {{ TirePosition::RearRight->label() }}
                            </div>
                            <div drag-tire draggable="true" class="m-2 flex flex-col">
                                <x-phosphor-tire-duotone
                                    class="w-24 h-24 inline text-blue-600 self-center"
                                    id="tire4"/>
                                <div class="self-center font-semibold uppercase tracking-tight text-xs text-gray-800">
                                    Tire 4
                                </div>
                            </div>
                        </div>
                        @if($vehicle_tire_count == 5)
                            <div drag-position class="justify-self-center bg-gray-300 border border-gray-500 p-2 w-32 h-40 flex flex-col">
                                <div class="self-center font-semibold uppercase tracking-tight text-sm text-gray-800 border-b border-gray-500">
                                    {{ TirePosition::Spare->label() }}
                                </div>
                                <div drag-tire draggable="true" class="m-2 flex flex-col">
                                    <x-phosphor-tire-duotone
                                        class="w-24 h-24 inline text-blue-600 self-center"
                                        id="tire5"/>
                                    <div class="self-center font-semibold uppercase tracking-tight text-xs text-gray-800">
                                        Tire 5
                                    </div>
                                </div>
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

        dragRoot.querySelectorAll('[drag-tire]').forEach(el => {
            el.addEventListener('dragstart', e => {
                e.target.setAttribute('dragging', true)
                // console.log('start drag-tire')
            })
        })

        dragRoot.querySelectorAll('[drag-position]').forEach(el => {
            el.addEventListener('dragstart', e => {
                // console.log('start drag-position')
            })

            el.addEventListener('drop', function (e) {
                e.preventDefault();
                this.classList.remove('bg-blue-300')
                let draggingEl = dragRoot.querySelector('[dragging]')
                this.appendChild(draggingEl)
                // console.log('drop drag-position', this)
            })

            el.addEventListener('dragenter', function (e) {
                this.classList.add('bg-blue-300')
                // console.log('dragenter drag-position', this)
                e.preventDefault()
            })

            el.addEventListener('dragover', function (e) {
                this.classList.add('bg-blue-300')
                e.preventDefault()
            })

            el.addEventListener('dragleave', function (e) {
                this.classList.remove('bg-blue-300')
                // console.log('leave drag-position', this)
            })

            el.addEventListener('dragend', e => {
                e.target.removeAttribute('dragging')
                // console.log('end drag-position')
            })
        })

        dragRoot.querySelectorAll('[drag-garage]').forEach(el => {
            el.addEventListener('dragstart', e => {
                //console.log('start drag-garage')
            })

            // As a reminder, arrow functions do not have their own 'this' context.
            el.addEventListener('drop', function (e) {
                e.preventDefault();
                this.classList.remove('bg-blue-300')
                let draggingEl = dragRoot.querySelector('[dragging]')
                this.appendChild(draggingEl)
                // console.log('drop drag-garage', this)
            })

            el.addEventListener('dragenter', function (e) {
                this.classList.add('bg-blue-300')
                // console.log('dragenter drag-garage', this)
                e.preventDefault()
            })

            el.addEventListener('dragover', function (e) {
                this.classList.add('bg-blue-300')
                // console.log('dragover', this)
                e.preventDefault()
            })

            el.addEventListener('dragleave', function (e) {
                this.classList.remove('bg-blue-300')
                // console.log('leave drag-garage', this)
            })

            el.addEventListener('dragend', e => {
                e.target.removeAttribute('dragging')
                // console.log('end drag-garage')
            })
        })
    </script>
@endpush
