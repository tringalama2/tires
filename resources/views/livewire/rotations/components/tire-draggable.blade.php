@use(App\Models\Rotation)
@use(Illuminate\Support\Facades\DB)
@props([
    'position',
    'tire',
    'color' => 'text-blue-600'
])

@php
    $tireHistory = Rotation::query()->select([
                'rotated_on', 'starting_odometer',
                'tin', 'label', 'starting_tread', 'starting_position',
                DB::raw('lag(starting_tread) over (partition by tires.id order by starting_odometer desc) as ending_tread'),
                DB::raw('lag(starting_odometer) over (partition by tires.id order by starting_odometer desc) as ending_odometer'),
            ])
                ->join('tires', 'rotations.tire_id', '=', 'tires.id')
                ->where('tires.id', $tire->id)
                ->limit(5)
                ->get();
@endphp

<div class="flex flex-col">
    <div class="text-center self-center font-semibold uppercase tracking-tight text-sm text-gray-800 border-b border-gray-500">
        {{ $position->label() }}
    </div>
    <div drag-tire="{{ $tire->id }}" draggable="true" class="m-2 flex flex-col">
        <x-phosphor-tire-duotone class="w-16 h-16 inline self-center {{ $color }} cursor-grab"/>
        <div class="text-center self-center font-semibold tracking-tight text-xs text-gray-800 flex flex-col">
            <div>
                {{ $tire->label }} - {{ $tire->tin }}

                <div x-data="{ showPopover: false }" class="inline">
                    <button
                        x-transition
                        @mouseover="showPopover = true"
                        @mouseleave="showPopover = false"
                        class="ml-2 hover:bg-gray-300 font-bold p-0 rounded transition-colors duration-300">
                        <x-phosphor-list-magnifying-glass class="w-4"/>
                    </button>

                    <div x-show="showPopover"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 transform scale-100"
                         x-transition:leave-end="opacity-0 transform scale-95"
                         class="-translate-x-1/2 font-normal text-sm z-10 absolute bg-gray-100 border shadow-md mt-1 px-4rounded">

                        <div class="flex justify-between">
                            <h3 class="text-lg font-extralight px-3">Tire History (last 5):</h3>
                        </div>

                        <table>
                            <thead>
                            <tr class="font-bold text-xs bg-gray-200 border-y border-gray-600">
                                <td class="px-3">Date</td>
                                <td class="px-3">Odometer</td>
                                <td class="px-3">Position</td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($tireHistory as $history)
                                <tr class="odd:bg-white">
                                    <td class="px-3 text-nowrap">{{ $history->rotated_on->format('M \'y') }}</td>
                                    <td class="px-3 text-nowrap">{{ Illuminate\Support\Number::format($history->starting_odometer) }}</td>
                                    <td class="px-3 text-nowrap">{{ $history->starting_position->label() }}
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div>
                (From {{ $position->label() }})
            </div>
        </div>
    </div>
</div>
