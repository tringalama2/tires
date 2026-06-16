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
    public function currentOdometer(): int
    {
        return $this->latestRotation?->odometer ?? $this->vehicle->starting_odometer;
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
        return $this->allTiresSortedByMilesLeft
            ->filter(fn ($r) => $r['projected_miles'] !== null && $r['projected_miles'] <= 10000)
            ->values();
    }

    /**
     * All tires sorted by projected miles remaining (ascending, nulls last).
     */
    #[Computed]
    public function allTiresSortedByMilesLeft(): Collection
    {
        return app(WearReportService::class)->wearByTire($this->vehicle)
            ->sortBy(fn ($r) => $r['projected_miles'] ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * Current tire in each position (FL→FR→RL→RR→SP order).
     */
    #[Computed]
    public function currentPositions(): Collection
    {
        return app(WearReportService::class)->wearByTire($this->vehicle)
            ->filter(fn ($r) => $r['current_position'] !== null)
            ->sortBy(fn ($r) => array_search($r['current_position']->value, ['FL', 'FR', 'RL', 'RR', 'SP']))
            ->values();
    }

    /**
     * Position with the highest average wear rate, or null if insufficient data.
     */
    #[Computed]
    public function fastestWearPosition(): ?array
    {
        return app(WearReportService::class)->wearByPosition($this->vehicle)
            ->whereNotNull('avg_wear_per_1000mi')
            ->sortByDesc('avg_wear_per_1000mi')
            ->first();
    }

    /**
     * Warn message when fastest position wears ≥1.5× the average of the others.
     */
    #[Computed]
    public function unevenWearAlert(): ?string
    {
        $rows = app(WearReportService::class)->wearByPosition($this->vehicle)
            ->whereNotNull('avg_wear_per_1000mi');

        if ($rows->count() < 2) {
            return null;
        }

        $fastest = $rows->sortByDesc('avg_wear_per_1000mi')->first();
        $othersAvg = $rows
            ->filter(fn ($r) => $r['position'] !== $fastest['position'])
            ->avg('avg_wear_per_1000mi');

        if ($othersAvg > 0 && $fastest['avg_wear_per_1000mi'] >= 1.5 * $othersAvg) {
            return $fastest['position']->label().' is wearing significantly faster than the rest. Check alignment or rotate more frequently.';
        }

        return null;
    }

    #[Layout('layouts.app')]
    public function render(): View
    {
        return view('livewire.rotation-dashboard');
    }
}
