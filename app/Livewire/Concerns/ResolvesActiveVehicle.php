<?php

namespace App\Livewire\Concerns;

use App\Actions\SelectVehicle;
use App\Models\Vehicle;
use Livewire\Attributes\Locked;

trait ResolvesActiveVehicle
{
    /**
     * Resolve the vehicle for this request: decode a route-bound hashid (if present),
     * authorize and select it, or fall back to the session's active vehicle.
     * Sets $this->vehicle_id and returns the resolved vehicle.
     *
     * $vehicle_id must be #[Locked] on the consuming component — otherwise Livewire
     * re-hydrates it from client state on every subsequent request, letting a tampered
     * value bypass the authorization check that only runs here, in mount(). This is
     * enforced at runtime rather than left as a documentation rule, since the alternative
     * is each new component author having to remember the rule.
     */
    protected function resolveVehicle(SelectVehicle $selectVehicle): Vehicle
    {
        $property = new \ReflectionProperty($this, 'vehicle_id');
        if ($property->getAttributes(Locked::class) === []) {
            throw new \LogicException(
                static::class.'::$vehicle_id must be #[Locked] — without it, resolveVehicle()\'s '
                .'authorization check can be bypassed by tampering with the property after mount().'
            );
        }

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
