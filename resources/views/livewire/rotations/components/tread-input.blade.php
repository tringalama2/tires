@props([
    'position',
])

<div {{ $attributes->class(['justify-self-center bg-gray-300 border border-gray-500 p-2 w-44 h-40']) }}>
    <div class="flex flex-col items-center">
        <div class="text-center self-center font-semibold uppercase tracking-tight text-sm text-gray-800 border-b border-gray-500">
            {{ $position->label() }}
            <x-phosphor-tire-duotone class="w-8 h-8 inline self-center text-blue-600"/>
        </div>
        <div class="m-2 flex flex-col items-center">

            <div class="text-center self-center font-bold tracking-tight text-sm text-gray-800">
                Tire label
                {{--                                                        {{ $frontLeftTire->label }} - {{ $frontLeftTire->tin }}--}}
            </div>

            <div class="mt-1 flex flex-col items-center">
                <x-input-label for="starting_tread" :value="__('Tread Depth')"/>
                <div class="relative w-24">
                    <x-text-input wire:model="starting_tread" id="starting_tread" class="text-right block mt-1 w-full pe-12" type="text" name="starting_tread" required/>
                    <div class="absolute inset-y-0 end-0 mt-1 flex items-center pointer-events-none z-20 pe-4">
                        <span class="text-gray-400 text-sm font-bold">/32"</span>
                    </div>
                </div>
                <x-forms.input-error for="starting_tread" class="mt-2"/>
            </div>
        </div>
    </div>
</div>
