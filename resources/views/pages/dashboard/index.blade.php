<?php

use App\Enums\TireStatus;
use App\Enums\TirePosition;
use App\Models\Rotation;
use App\Models\RotationTire;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

use function Laravel\Folio\middleware;
use function Laravel\Folio\name;
use function Livewire\Volt\{computed};

name('dashboard');
middleware(['auth', 'verified']);

new class extends Component {
    #[Computed]
    public function lastRotation(): Model
    {
        return Rotation::query()->select('odometer', 'rotated_on')->where('user_id', auth()->id())->latest()->first();
    }

    #[Computed]
    public function lastOdometer(): int
    {
        return $this->lastRotation->odometer;
    }

    #[Computed]
    public function lastRotatedOn(): Carbon
    {
        return $this->lastRotation->rotated_on;
    }

    #[Computed]
    public function rotationTires(): Collection
    {
        return RotationTire::select([
            'tin', 'tread', 'position', 'rotated_on', 'odometer', 'rotation_id', 'tire_id', 'label',
            DB::raw('lag(tread) over (partition by position order by odometer) as prior_tread'),
            DB::raw('lag(tread) over (partition by position order by odometer)-tread as tread_diff'),
            DB::raw('(odometer - lag(odometer) over (partition by position order by odometer)) / (lag(tread) over (partition by position order by odometer)-tread) as milesPerOne32ndloss'),
            DB::raw('lag(tin) over (partition by position order by odometer) as prior_tire'),
            DB::raw('lag(tread, 2) over (partition by position order by odometer) as prior_tread_2'),
            DB::raw('lag(tin, 2) over (partition by position order by odometer) as prior_tire_2'),
            DB::raw('lag(tread, 2) over (partition by position order by odometer)-tread as tread_diff_2'),
            DB::raw('odometer - lag(odometer) over (partition by position order by odometer) as miles_since_prior_rotation'),
        ])
            ->join('rotations', 'rotation_tire.rotation_id', '=', 'rotations.id')
            ->join('tires', 'rotation_tire.tire_id', '=', 'tires.id')
            ->where('tires.status', TireStatus::Installed)
            ->where('tires.user_id', auth()->id())
            ->where('rotations.user_id', auth()->id())
            ->orderBy('position', 'asc')
            ->orderBy('rotated_on', 'desc')
            ->get();
    }

    #[Computed]
    public function currentRotation(): Rotation
    {
        return Rotation::query()
            ->with([
                'tires' => function (Builder $query) {
                    $query->where('status', TireStatus::Installed);
                }
            ])
            ->latest('rotated_on')
            ->first();
    }


    #[Computed]
    public function positionHistory(TirePosition $position): Collection
    {
        return RotationTire::select([
            'rotated_on', 'odometer',
            'tin', 'label', 'tread', 'position',
            DB::raw('lag(tread) over (partition by position order by odometer desc) as ending_tread'),
            DB::raw('lag(odometer) over (partition by position order by odometer desc) as ending_odometer'),
        ])
            ->join('rotations', 'rotation_tire.rotation_id', '=', 'rotations.id')
            ->join('tires', 'rotation_tire.tire_id', '=', 'tires.id')
            ->where('tires.status', TireStatus::Installed)
            ->where('rotation_tire.position', $position)
            ->where('tires.user_id', auth()->id())
            ->where('rotations.user_id', auth()->id())
            ->orderBy('odometer', 'desc')
            ->get();
    }
};
?>

<x-layouts.app>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-gray-800 dark:text-gray-200">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @volt('dashboard')
    <div class="flex flex-col flex-1 items-stretch h-100">
        <div class="flex flex-col items-stretch flex-1 pb-5 mx-auto h-100 min-h-[500px] w-full">
            <div class="relative flex-1 w-full h-100">
                <div class="flex justify-between items-center w-full h-100 border border-dashed bg-gradient-to-br from-white to-zinc-50 rounded-lg border-zinc-200 max-h-[500px]">
                    <div class="flex relative flex-col p-10">
                        <div class="flex items-center pb-5 mb-5 space-x-1.5 text-lg font-bold text-gray-800 uppercase border-b border-dotted border-zinc-200">
                            <x-ui.logo class="block w-auto h-7 text-gray-800 fill-current dark:text-gray-200"/>
                            <span>Rotation Tracker</span>
                        </div>

                        <div class="text-blue-600 text-3xl mb-4">
                            Last Rotated on {{ $this->currentRotation->rotated_on->format('M j, Y') }}
                            @ {{ number_format($this->currentRotation->odometer) }}
                            miles.
                        </div>

                        <div class="grid grid-cols-3 grid-rows-4 gap-4">
                            <div class="">
                                @php($frontLTires = $this->rotationTires()->where('position', TirePosition::FrontLeft))
                                <h2 class="font-bold text-xl">{{ TirePosition::FrontLeft->label() }}</h2>
                                <div class="text-gray-700">
                                    Tire Label: {{ $frontLTires->first()->label }}
                                </div>
                                <div class="text-gray-700">
                                    Tread Depth: {{ $frontLTires->first()->tread }}<span class="text-xs text-gray-500">/32"</span>
                                </div>
                            </div>
                            <div class="row-span-2">Body</div>
                            <div class="..."><h2 class="font-bold text-xl">{{ TirePosition::FrontRight->label() }}</h2>
                            </div>
                            <div class="..."><h2 class="font-bold text-xl">{{ TirePosition::RearLeft->label() }}</h2>
                            </div>
                            <div class="..."><h2 class="font-bold text-xl">{{ TirePosition::RearRight->label() }}</h2>
                            </div>
                            <div class="col-start-2 ">
                                <h2 class="font-bold text-xl flex flex-nowrap align-center">
                                    <span class="self-center">
                                        {{ TirePosition::Spare->label() }}
                                    </span>

                                    <!-- Details Popover -->
                                    <div x-data="{ showPopover: false }">
                                        <button
                                            @click="showPopover = !showPopover"
                                            class="ml-2 text-gray-800 hover:bg-gray-200 font-bold p-1 rounded transition-colors duration-300">
                                            <x-phosphor-list-magnifying-glass class="w-6"/>
                                        </button>

                                        <div x-show="showPopover"
                                             x-transition:enter="transition ease-out duration-300"
                                             x-transition:enter-start="opacity-0 transform scale-95"
                                             x-transition:enter-end="opacity-100 transform scale-100"
                                             x-transition:leave="transition ease-in duration-200"
                                             x-transition:leave-start="opacity-100 transform scale-100"
                                             x-transition:leave-end="opacity-0 transform scale-95"
                                             class="font-normal text-sm z-10 absolute bg-gray-100 border shadow-md mt-2 px-4 py-2 rounded">

                                            <h3 class="text-lg font-extralight">Spare Tire Position History</h3>
                                            <table>
                                                <thead>
                                                <tr class="font-bold text-xs">
                                                    <td class="pe-3 text-nowrap">Odometer</td>
                                                    <td class="px-3 text-nowrap">Tire</td>
                                                    <td class="px-3 text-nowrap">Starting Depth (1/32")</td>
                                                    <td class="px-3 text-nowrap">Ending Depth (1/32")</td>
                                                    <td class="px-3 text-nowrap">Wear (1/32")</td>
                                                    <td class="ps-3 text-nowrap">Miles per 1/32" loss</td>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($this->positionHistory(TirePosition::Spare) as $rotationTire)
                                                    <tr>
                                                        <td class="pe-3 text-nowrap">{{ number_format($rotationTire->odometer) }}</td>
                                                        <td class="px-3 text-nowrap">{{ $rotationTire->label }}
                                                            <small class="text-sm text-gray-500">( {{ $rotationTire->tin }}
                                                                                                 ) </small></td>
                                                        <td class="px-3 text-nowrap">{{ $rotationTire->tread }}</td>
                                                        <td class="px-3 text-nowrap">{{ $rotationTire->ending_tread }}</td>
                                                        <td class="px-3 text-nowrap">{{ treadDiff($rotationTire->tread, $rotationTire->ending_tread) }}</td>
                                                        <td class="ps-3 text-nowrap">{{ milesPerOne32ndLoss($rotationTire->tread, $rotationTire->ending_tread, $rotationTire->odometer, $rotationTire->ending_odometer) }}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </h2>

                                <div>
                                    <div class="text-gray-700">
                                        Tire
                                        Label: {{ $this->currentRotation->tiresByPosition(TirePosition::Spare)->first()->label }}
                                    </div>
                                    <div class="text-gray-700">
                                        Tread
                                        Depth: {{ $this->currentRotation->tiresByPosition(TirePosition::Spare)->first()->tireDetails->tread }}
                                        <span class="text-xs text-gray-500">/32"</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>


        </table>
    @endvolt
</x-layouts.app>
