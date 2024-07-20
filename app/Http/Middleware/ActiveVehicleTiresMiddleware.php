<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ActiveVehicleTiresMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // The number of tires in the vehicle's related model must match
        // the number set by the user when creating the vehicle.
        if (session()->exists('vehicle') && session('vehicle')->tires_count !== session('vehicle')->tire_count) {
            return to_route('vehicles.create')->with('status', 'Add all '.session('vehicle')->tire_count.' tires to your vehicle before continuing.');
        }

        return $next($request);
    }
}
