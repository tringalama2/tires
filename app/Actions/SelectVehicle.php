<?php

namespace App\Actions;

use App\Models\Vehicle;
use Illuminate\Support\Carbon;

class SelectVehicle
{
    public function __invoke(Vehicle $vehicle): void
    {
        $vehicle->last_selected_at = Carbon::now();
        $vehicle->save();

        session(['vehicle' => $vehicle]);
    }
}
