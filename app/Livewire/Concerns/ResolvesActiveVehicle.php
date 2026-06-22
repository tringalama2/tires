<?php

namespace App\Livewire\Concerns;

use App\Actions\SelectVehicle;
use App\Models\Vehicle;

trait ResolvesActiveVehicle
{
    /**
     * Resolve the vehicle for this request: decode a route-bound hashid (if present),
     * authorize and select it, or fall back to the session's active vehicle.
     * Sets $this->vehicle_id and returns the resolved vehicle.
     */
    protected function resolveVehicle(SelectVehicle $selectVehicle): Vehicle
    {
        if (isset($this->vehicle_id)) {
            $id = is_string($this->vehicle_id) ? hashid_decode($this->vehicle_id) : $this->vehicle_id;
            $vehicle = Vehicle::findOrFail($id);
            $this->authorize('view', $vehicle);
            $selectVehicle($vehicle);
        } else {
            $vehicle = session('vehicle');
        }

        $this->vehicle_id = $vehicle->id;

        return $vehicle;
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::findOrFail($this->vehicle_id);
    }
}
