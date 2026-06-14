<?php

namespace App\Livewire;

use App\Actions\SelectVehicle;
use App\Models\Rotation;
use App\Models\Vehicle;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class RotationDashboard extends Component
{
    public ?int $vehicle_id;

    protected Vehicle $vehicle;

    public function mount(SelectVehicle $selectVehicle): void
    {
        if (isset($this->vehicle_id)) {
            $this->vehicle = Vehicle::findOrFail($this->vehicle_id);
            $selectVehicle($this->vehicle);
        } else {
            $this->vehicle = session('vehicle');
        }

        $this->vehicle_id = $this->vehicle->id;

        if ($this->vehicle->tires()->count() === 0) {
            $this->redirect(route('vehicles.setuptires.index', ['vehicle' => $this->vehicle]));
        }
    }

    #[Computed]
    public function latestRotation(): ?Rotation
    {
        // TODO Phase 4: expand dashboard with last rotation summary and replacement alerts
        return $this->vehicle->rotations()
            ->where('is_setup', false)
            ->orderByDesc('odometer')
            ->first();
    }

    #[Layout('layouts.app')]
    public function render(): View
    {
        return view('livewire.rotation-dashboard');
    }
}
