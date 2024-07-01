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
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    #[Computed]
    public function currentRotation(): Rotation
    {
        return Rotation::query()
            ->with([
                'tires' => function (Builder $query) {
                    $query->where('status', TireStatus::Installed);
                }
            ])
            ->where('vehicle_id', session('vehicle')->id)
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
            ->where('tires.vehicle_id', session('vehicle')->id)
            ->where('rotations.vehicle_id', session('vehicle')->id)
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
            ->where('tires.vehicle_id', session('vehicle')->id)
            ->where('tires.id', $tire->id)
            ->orderBy('odometer', 'desc')
            ->get();
    }
}
?>

<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Dashboard') }}
    </h2>
</x-slot>

<div class="py-12">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">

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
                        <x-img.car-top-view class="w-64"/>
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
