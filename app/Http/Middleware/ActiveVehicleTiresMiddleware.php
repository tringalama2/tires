<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActiveVehicleTiresMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $vehicle = session('vehicle');

        if ($vehicle && ! $vehicle->isSetupComplete()) {
            return to_route('vehicles.setuptires.index', $vehicle)
                ->with('status', 'Add all '.$vehicle->tire_count.' tires to your vehicle before continuing.');
        }

        return $next($request);
    }
}
