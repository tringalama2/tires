<?php

use App\Enums\TireStatus;
use App\Enums\TirePosition;
use App\Models\Rotation;
use App\Models\RotationTire;
use App\Models\Tire;
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
    public function currentRotation(): Rotation
    {
        return Rotation::query()
            ->with([
                'tires' => function (Builder $query) {
                    $query->where('status', TireStatus::Installed);
                }
            ])
            ->where('user_id', auth()->id())
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

    #[Computed]
    public function tireHistory(Tire $tire): Collection
    {
        return RotationTire::select([
            'rotated_on', 'odometer', 'position',
        ])
            ->join('rotations', 'rotation_tire.rotation_id', '=', 'rotations.id')
            ->join('tires', 'rotation_tire.tire_id', '=', 'tires.id')
            ->where('tires.user_id', auth()->id())
            ->where('tires.id', $tire->id)
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


                        <div class="grid grid-cols-3 grid-rows-3 gap-4">
                            <div class="justify-self-center">
                                <x-tire-position-details
                                    :position="TirePosition::FrontLeft"
                                    :currentRotation="$this->currentRotation->tiresByPosition(TirePosition::FrontLeft)->first()"
                                    :positionHistory="$this->positionHistory(TirePosition::FrontLeft)"
                                    :tireHistory="$this->tireHistory($this->currentRotation->tiresByPosition(TirePosition::FrontRight)->first())"/>
                            </div>
                            <div class="row-span-2 justify-self-center">
                                <x-ui.img.car-top-view class="w-64"/>
                            </div>
                            <div class="justify-self-center">
                                <x-tire-position-details
                                    :position="TirePosition::FrontRight"
                                    :currentRotation="$this->currentRotation->tiresByPosition(TirePosition::FrontRight)->first()"
                                    :positionHistory="$this->positionHistory(TirePosition::FrontRight)"
                                    :tireHistory="$this->tireHistory($this->currentRotation->tiresByPosition(TirePosition::FrontRight)->first())"/>
                            </div>
                            <div class="justify-self-center">
                                <x-tire-position-details
                                    :position="TirePosition::RearLeft"
                                    :currentRotation="$this->currentRotation->tiresByPosition(TirePosition::RearLeft)->first()"
                                    :positionHistory="$this->positionHistory(TirePosition::RearLeft)"
                                    :tireHistory="$this->tireHistory($this->currentRotation->tiresByPosition(TirePosition::FrontRight)->first())"/>
                            </div>
                            <div class="justify-self-center">
                                <x-tire-position-details
                                    :position="TirePosition::RearRight"
                                    :currentRotation="$this->currentRotation->tiresByPosition(TirePosition::RearRight)->first()"
                                    :positionHistory="$this->positionHistory(TirePosition::RearRight)"
                                    :tireHistory="$this->tireHistory($this->currentRotation->tiresByPosition(TirePosition::FrontRight)->first())"/>
                            </div>
                            <div class="col-start-2 justify-self-center">
                                <x-tire-position-details
                                    :position="TirePosition::Spare"
                                    :currentRotation="$this->currentRotation->tiresByPosition(TirePosition::Spare)->first()"
                                    :positionHistory="$this->positionHistory(TirePosition::Spare)"
                                    :tireHistory="$this->tireHistory($this->currentRotation->tiresByPosition(TirePosition::FrontRight)->first())"/>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    @endvolt
</x-layouts.app>
