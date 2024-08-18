<?php

use App\Enums\TirePosition;
use App\Enums\TireStatus;
use App\Models\Rotation;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public string $vehicle_id;
    public TirePosition $position;

    #[Computed]
    public function positionHistory(): Collection
    {
        return Rotation::query()->select([
            'rotated_on', 'starting_odometer',
            'tin', 'label', 'starting_tread', 'starting_position',
            DB::raw('lag(starting_tread) over (partition by tires.id order by starting_odometer desc) as ending_tread'),
            DB::raw('lag(starting_odometer) over (partition by tires.id order by starting_odometer desc) as ending_odometer'),
        ])
            ->join('tires', 'rotations.tire_id', '=', 'tires.id')
            ->whereIn('tires.id', function (Builder $query) {
                $query->select(['rotations.tire_id'])
                    ->from('rotations')
                    ->join('tires', 'rotations.tire_id', '=', 'tires.id')
                    ->where('tires.vehicle_id', $this->vehicle_id)
                    ->orderByDesc('starting_odometer');
            })
            ->where('rotations.starting_position', $this->position)
            ->orderByDesc('starting_odometer')
            ->get();
    }

    #[Computed]
    public function tireHistory(): Collection
    {
        return Rotation::query()->select([
            'rotated_on', 'starting_odometer',
            'tin', 'label', 'starting_tread', 'starting_position',
            DB::raw('lag(starting_tread) over (partition by tires.id order by starting_odometer desc) as ending_tread'),
            DB::raw('lag(starting_odometer) over (partition by tires.id order by starting_odometer desc) as ending_odometer'),
        ])
            ->join('tires', 'rotations.tire_id', '=', 'tires.id')
            ->where('tires.id', function (Builder $query) {
                $query->select(['rotations.tire_id'])
                    ->from('rotations')
                    ->join('tires', 'rotations.tire_id', '=', 'tires.id')
                    ->where('tires.vehicle_id', $this->vehicle_id)
                    ->where('tires.status', TireStatus::Installed)
                    ->where('rotations.starting_position', $this->position)
                    ->orderByDesc('starting_odometer')
                    ->limit(1);
            })
            ->get();
    }
}
?>
<div>
    <h2 class="font-bold text-xl flex flex-nowrap align-center">
        <span class="self-center">
            {{ $position->label() }}
        </span>

        <!-- Position Details Popover -->
        <div x-data="{ showPopover: false }">
            <button
                x-transition
                @click="$dispatch('pop', { from: '{{ $position->camel() }}PositionHistory' });"
                @pop.window="showPopover = $event.detail.from == '{{ $position->camel() }}PositionHistory'"
                class="ml-2 hover:bg-gray-300 font-bold p-1 rounded transition-colors duration-300"
                :class="{'bg-gray-300' : showPopover}"/>
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

                <div class="flex justify-between">
                    <h3 class="text-lg font-extralight px-3">{{ $position->label() }} Position History</h3>
                    <button @click="showPopover = false">
                        <x-phosphor-x-square-duotone class="w-6 h-6 inline text-red-400"/>
                    </button>
                </div>
                <table>
                    <thead>
                    <tr class="font-bold text-xs bg-gray-200 border-y border-gray-600">
                        <td class="px-3">Date</td>
                        <td class="px-3">Starting Odometer</td>
                        <td class="px-3">Tire</td>
                        <td class="px-3">Starting Depth (1/32")
                        </td>
                        <td class="px-3">Ending Depth (1/32")
                        </td>
                        <td class="px-3">Ending Odometer</td>
                        <td class="px-3">Wear (1/32")</td>
                        <td class="px-3">Miles per 1/32"</td>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($this->positionHistory as $rotationTire)
                        <tr class="odd:bg-white">
                            <td class="px-3 text-nowrap">{{ $rotationTire->rotated_on->format('M \'y') }}</td>
                            <td class="px-3 text-nowrap">{{ Illuminate\Support\Number::format($rotationTire->starting_odometer) }}</td>
                            <td class="px-3 text-nowrap">{{ $rotationTire->label }}
                                <small class="text-sm text-gray-500">( {{ $rotationTire->tin }}
                                                                     ) </small></td>
                            <td class="px-3 text-nowrap">{{ $rotationTire->starting_tread }}</td>
                            <td class="px-3 text-nowrap">{{ $rotationTire->ending_tread }}</td>
                            <td class="px-3 text-nowrap">{{ $rotationTire->ending_odometer }}</td>
                            <td class="px-3 text-nowrap">{{ treadDiff($rotationTire->starting_tread, $rotationTire->ending_tread) }}</td>
                            <td class="px-3 text-nowrap">{{ milesPerOne32ndLoss($rotationTire->starting_tread, $rotationTire->ending_tread, $rotationTire->starting_odometer, $rotationTire->ending_odometer) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </h2>

    <div>
        <div class="text-gray-700 flex flex-nowrap">
            <div>Current Tire: {{ $this->tireHistory->first()->label }}
                <small class="text-sm text-gray-500">( {{ $this->tireHistory->first()->tin }}
                                                     )</small></div>

            <!-- Tire Details Popover -->
            <div x-data="{ showPopover: false }">
                <button
                    x-transition
                    @click="$dispatch('pop', { from: '{{ $position->camel() }}TireHistory' });"
                    @pop.window="showPopover = $event.detail.from == '{{ $position->camel() }}TireHistory'"
                    class="ml-2 hover:bg-gray-300 font-bold p-1 rounded transition-colors duration-300"
                    :class="{'bg-gray-300' : showPopover}">
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

                    <div class="flex justify-between">
                        <h3 class="text-lg font-extralight px-3">Tire History:
                            <i class="italic">{{ $this->tireHistory->first()->label }}</i>
                        </h3>
                        <button @click="showPopover = false">
                            <x-phosphor-x-square-duotone class="w-6 h-6 inline text-red-400"/>
                        </button>
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
                        @foreach($this->tireHistory as $history)
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
        <div class="text-gray-700">
            Tread Depth: {{ $this->tireHistory->first()->starting_tread }}
            <span class="text-xs text-gray-500">/32"</span>
        </div>
    </div>

</div>
