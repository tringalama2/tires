@use(App\Enums\TirePosition)
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Setup Tires
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">

                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')"/>

                <div class="p-6 text-gray-900">

                    <div class="grid grid-cols-3 grid-rows-3 gap-4">
                        <div class="w-full justify-items-center">
                            <x-tire-view-or-add :tirePosition="TirePosition::FrontLeft" :tire="$frontLeftTire" :vehicle="$vehicle"/>
                        </div>
                        <div class="row-span-2 w-full flex justify-center items-center">
                            <x-img.car-top-view class="w-64  fill-gray-800"/>
                        </div>
                        <div class="w-full justify-items-center">
                            <x-tire-view-or-add :tirePosition="TirePosition::FrontRight" :tire="$frontRightTire" :vehicle="$vehicle"/>
                        </div>
                        <div class="w-full justify-self-center">
                            <x-tire-view-or-add :tirePosition="TirePosition::RearLeft" :tire="$rearLeftTire" :vehicle="$vehicle"/>
                        </div>
                        <div class="w-full justify-self-center">
                            <x-tire-view-or-add :tirePosition="TirePosition::RearRight" :tire="$rearRightTire" :vehicle="$vehicle"/>
                        </div>
                        @if($vehicle->tire_count == 5)
                            <div class="col-start-2 w-full justify-self-center">
                                <x-tire-view-or-add :tirePosition="TirePosition::Spare" :tire="$spareTire" :vehicle="$vehicle"/>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>

</x-app-layout>
