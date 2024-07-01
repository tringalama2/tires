<?php

namespace App\Http\Middleware;

use App\Livewire\Actions\SelectVehicle;
use App\Models\Vehicle;
use Closure;
use Illuminate\Http\Request;

class FirstVehicleMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->guest()) {
            return $next($request);
        }

        if (session()->exists('vehicle')) {
            return $next($request);
        }

        $lastSelectedVehicle = Vehicle::where('user_id', auth()->id())->latest('last_selected_at')->first();

        if ($lastSelectedVehicle) {
            // the last selected vehicle will be loaded into session
            (new SelectVehicle)($lastSelectedVehicle);
        }

        // A user must create a vehicle after account is created (or if
        // their last vehicle is deleted).  all rounds should redirect
        // back to vehicles create except for the create/store and
        // associated livewire internal routes.
        if ($lastSelectedVehicle === null &&
            ! in_array($request->route()->getName(),[
                'vehicles.create',
                'vehicles.store',
                'livewire.update'
            ])) {
            return to_route('vehicles.create')->with('status', 'Please add your first vehicle');;
        }

        return $next($request);
    }
}
