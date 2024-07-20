<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {

};
?>

<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Setup Tires
    </h2>
</x-slot>


<div class="py-12">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

            <!-- Session Status -->
            <x-auth-session-status class="m-4" :status="session('status')"/>

            <div class="p-6 text-gray-900">

                <div class="grid grid-cols-3 grid-rows-3 gap-4">
                    <div class="justify-self-center">
                        <a href="{{ route('tires.create') }}">Add Tire</a>
                    </div>
                    <div class="row-span-2 justify-self-center">
                        <x-img.car-top-view class="w-64"/>
                    </div>
                    <div class="justify-self-center">
                        Add Tire
                    </div>
                    <div class="justify-self-center">
                        Add Tire
                    </div>
                    <div class="justify-self-center">
                        Add Tire
                    </div>
                    <div class="col-start-2 justify-self-center">
                        Add Tire
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
