<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ActiveVehicleTiresMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // The number of installed (current) tires in the vehicle's related model
        // must match the number set by the user when creating the vehicle.

        if (session()->exists('vehicle') && session('vehicle')->loadCount('installedTires')->installed_tires_count != session('vehicle')->tire_count) {
            return to_route('vehicles.setuptires.index', session('vehicle'))
                ->with('status', 'Add all '.session('vehicle')->tire_count.' tires to your vehicle before continuing.');
        }

        return $next($request);
    }
}
