@props([
    'position',
    'tire',
    'input_name' => sprintf('starting_tread_%s', $position->snake())
])

<div {{ $attributes->class(['justify-self-center bg-gray-300 border border-gray-500 p-2 w-44 h-40']) }}>
    <div class="flex flex-col items-center">
        <div class="text-center self-center font-semibold uppercase tracking-tight text-sm text-gray-800 border-b border-gray-500">
            {{ $position->label() }}
            <x-phosphor-tire-duotone class="w-8 h-8 inline self-center text-blue-600"/>
        </div>
        <div class="m-2 flex flex-col items-center">

            <div class="text-center self-center font-bold tracking-tight text-sm text-gray-800">
                {{ $tire->label }} - {{ $tire->tin }}
            </div>

            <div class="mt-1 flex flex-col items-center">
                <x-input-label for="{{ $input_name }}" :value="__('Tread Depth')"/>
                <div class="relative w-24">
                    <x-text-input wire:model="{{ $input_name }}" id="{{ $input_name }}" class="text-right block mt-1 w-full pe-12" type="text" name="{{ $input_name }}" required/>
                    <div class="absolute inset-y-0 end-0 mt-1 flex items-center pointer-events-none z-20 pe-4">
                        <span class="text-gray-400 text-sm font-bold">/32"</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
