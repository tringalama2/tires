@props([
    'position',
    'tire',
    'input_name' => sprintf('starting_tread_%s', $position->snake())
])

<div {{ $attributes->class(['justify-self-center bg-gray-300 border border-gray-500 p-2 w-44 h-40']) }}>
    <div class="flex flex-col items-center">
        <div class="text-center self-center font-semibold uppercase tracking-tight text-sm text-gray-800 border-b border-gray-500">
            {{ $position->label() }}
            <x-phosphor-tire-duotone class="w-8 h-8 inline self-center text-ink-500"/>
        </div>
        <div class="m-2 flex flex-col items-center">

            <div class="text-center self-center font-bold tracking-tight text-sm text-gray-800">
                {{ $tire->label }} - {{ $tire->tin }}
            </div>

            <div class="mt-1 w-full">
                <x-treadmark.input
                    wire:model="{{ $input_name }}"
                    id="{{ $input_name }}"
                    type="text"
                    label="Tread Depth"
                    name="{{ $input_name }}"
                    suffix='/32"'
                    mono
                    required
                />
            </div>
        </div>
    </div>
</div>
