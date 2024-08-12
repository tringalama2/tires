@props([
    'position',
    'tire',
    'color' => 'text-blue-600'
])

<div class="flex flex-col">
    <div class="text-center self-center font-semibold uppercase tracking-tight text-sm text-gray-800 border-b border-gray-500">
        {{ $position->label() }}
    </div>
    <div drag-tire draggable="true" class="m-2 flex flex-col">
        <x-phosphor-tire-duotone class="w-16 h-16 inline self-center {{ $color }}"/>
        <div class="text-center self-center font-semibold tracking-tight text-xs text-gray-800">
            {{ $tire }} (From {{ $position->label() }})
        </div>
    </div>
</div>
