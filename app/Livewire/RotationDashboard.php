<?php

namespace App\Livewire;

use App\Actions\SelectVehicle;
use App\Enums\TirePosition;
use App\Enums\TireStatus;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\Vehicle;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class RotationDashboard extends Component
{
    public ?int $vehicle_id;
    public $vehicle_tire_count;
    protected Vehicle $vehicle;

    public function mount(SelectVehicle $selectVehicle)
    {
        if (isset($this->vehicle_id)) {
            $this->vehicle = Vehicle::findOrFail($this->vehicle_id);
            $selectVehicle($this->vehicle);
        } else {
            $this->vehicle = session('vehicle');
        }

        $this->vehicle_id = $this->vehicle->id;
        $this->vehicle_tire_count = session('vehicle')->tire_count;

        if ($this->vehicle->tires->count() === 0) {
            return redirect()->route('vehicles.setuptires.index', ['vehicle' => $this->vehicle])->with('status', 'Let\'s setup your tires for this vehicle');
        }
    }

    #[Computed]
    public function latestRotation(): Rotation
    {
        return Rotation::query()
            ->whereIn('tire_id', function (Builder $query) {
                $query->select(['id'])->from('tires')
                    ->where('tires.vehicle_id', $this->vehicle->id);
            })
            ->latest('starting_odometer')
            ->first();
    }


    #[Layout('layouts.app')]
    public function render(): View
    {
        return view('livewire.rotation-dashboard');
    }
}
