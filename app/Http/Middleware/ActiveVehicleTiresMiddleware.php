<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ActiveVehicleTiresMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (session()->exists('vehicle') && ! session('vehicle')->isSetupComplete()) {
            return to_route('vehicles.setuptires.index', session('vehicle'))
                ->with('status', 'Add all '.session('vehicle')->tire_count.' tires to your vehicle before continuing.');
        }

        return $next($request);
    }
}
