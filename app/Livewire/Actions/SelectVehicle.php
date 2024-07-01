<?php

namespace App\Livewire\Actions;

use App\Models\Vehicle;

class SelectVehicle
{
    public function __invoke(Vehicle $vehicle): void
    {
        session(['vehicle' => $vehicle]);

        $vehicle->update(['last_selected_at' => now()]);
    }
}
