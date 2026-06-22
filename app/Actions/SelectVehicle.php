<?php

namespace App\Actions;

use App\Models\Vehicle;

class SelectVehicle
{
    public function __invoke(Vehicle $vehicle): void
    {
        $vehicle->forceFill(['last_selected_at' => now()])->save();

        session(['vehicle' => $vehicle]);
    }
}
