<div>
    <h2 class="font-bold text-xl flex flex-nowrap align-center">
        <span class="self-center">
            {{ $position->label() }}
        </span>

        <!-- Details Popover -->
        <div x-data="{ showPopover: false }">
            <button
                @click="showPopover = !showPopover"
                class="ml-2 hover:bg-gray-200 font-bold p-1 rounded transition-colors duration-300">
                <x-phosphor-list-magnifying-glass class="w-6"/>
            </button>

            <div x-show="showPopover"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 class="@if($position->side() == 'Right') -translate-x-full @endif font-normal text-sm z-10 absolute bg-gray-100 border shadow-md mt-1 px-4rounded">

                <h3 class="text-lg font-extralight px-3">{{ $position->label() }} Position History</h3>
                <table>
                    <thead>
                    <tr class="font-bold text-xs bg-gray-200 border-y border-gray-600">
                        <td class="px-3">Date</td>
                        <td class="px-3">Odometer</td>
                        <td class="px-3">Tire</td>
                        <td class="px-3">Starting Depth (1/32")
                        </td>
                        <td class="px-3">Ending Depth (1/32")
                        </td>
                        <td class="px-3">Wear (1/32")</td>
                        <td class="px-3">Miles per 1/32"</td>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($positionHistory as $rotationTire)
                        <tr class="odd:bg-white">
                            <td class="px-3 text-nowrap">{{ date_from_db_format($rotationTire->rotated_on)->format('M \'y') }}</td>
                            <td class="px-3 text-nowrap">{{ number_format($rotationTire->odometer) }}</td>
                            <td class="px-3 text-nowrap">{{ $rotationTire->label }}
                                <small class="text-sm text-gray-500">( {{ $rotationTire->tin }}
                                                                     ) </small></td>
                            <td class="px-3 text-nowrap">{{ $rotationTire->tread }}</td>
                            <td class="px-3 text-nowrap">{{ $rotationTire->ending_tread }}</td>
                            <td class="px-3 text-nowrap">{{ treadDiff($rotationTire->tread, $rotationTire->ending_tread) }}</td>
                            <td class="px-3 text-nowrap">{{ milesPerOne32ndLoss($rotationTire->tread, $rotationTire->ending_tread, $rotationTire->odometer, $rotationTire->ending_odometer) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </h2>

    <div>
        <div class="text-gray-700 flex flex-nowrap">
            <div>Current Tire: {{ $currentRotation->label }}
                <small class="text-sm text-gray-500">( {{ $currentRotation->tin }}
                                                     )</small></div>
            <div x-data="{ showPopover: false }">
                <button
                    @mouseover="showPopover = true"
                    @mouseover.away="showPopover = false"
                    class="ml-2 hover:bg-gray-200 font-bold p-1 rounded transition-colors duration-300">
                    <x-phosphor-list-magnifying-glass class="w-6"/>
                </button>

                <div x-show="showPopover"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="-translate-x-1/2 font-normal text-sm z-10 absolute bg-gray-100 border shadow-md mt-1 px-4rounded">

                    <h3 class="text-lg font-extralight px-3">Tire <i class="italic">{{ $currentRotation->label }}</i>
                                                             History</h3>
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
                                <td class="px-3 text-nowrap">{{ date_from_db_format($history->rotated_on)->format('M \'y') }}</td>
                                <td class="px-3 text-nowrap">{{ number_format($history->odometer) }}</td>
                                <td class="px-3 text-nowrap">{{ $history->position->label() }}</tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="text-gray-700">
            Tread Depth: {{ $currentRotation->tireDetails->tread }}
            <span class="text-xs text-gray-500">/32"</span>
        </div>
    </div>

</div>
