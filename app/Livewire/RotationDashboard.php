<?php

namespace App\Livewire;

use App\Actions\SelectVehicle;
use App\Models\Rotation;
use App\Models\Vehicle;
use App\Services\WearReportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
        return $this->vehicle->rotations()
            ->where('is_setup', false)
            ->orderByDesc('odometer')
            ->first();
    }

    #[Computed]
    public function daysSinceRotation(): ?int
    {
        if (! $this->latestRotation) {
            return null;
        }

        return (int) Carbon::parse($this->latestRotation->rotated_on)->diffInDays(Carbon::today());
    }

    /**
     * Tires projected to hit 2/32" within 10,000 miles.
     * Returns collection of ['tire', 'projected_miles', 'current_position'].
     */
    #[Computed]
    public function replacementAlerts(): Collection
    {
        $report = app(WearReportService::class)->wearByTire($this->vehicle);

        return $report
            ->filter(fn ($r) => $r['projected_miles'] !== null && $r['projected_miles'] <= 10000)
            ->sortBy('projected_miles')
            ->values();
    }

    #[Layout('layouts.app')]
    public function render(): View
    {
        return view('livewire.rotation-dashboard');
    }
}
