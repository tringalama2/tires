<?php

namespace App\Livewire;

use App\Enums\TirePosition;
use App\Enums\TireStatus;
use App\Models\Rotation;
use App\Models\RotationTire;
use App\Models\Tire;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class RotationDashboard extends Component
{
    public function mount()
    {
        if (session('vehicle')->tires->count() === 0) {
            return redirect()->route('tires.index')->with('status', 'Let\'s setup your tires for this vehicle');
        }
    }

    #[Computed]
    public function currentRotation(): Rotation
    {
        return Rotation::query()
            ->with([
                'tires' => function (Builder $query) {
                    $query->where('status', TireStatus::Installed);
                },
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

    #[Layout('layouts.app')]
    public function render(): View
    {
        return view('livewire.rotation-dashboard');
    }
}
