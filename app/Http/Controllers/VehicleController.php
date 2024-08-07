<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleCreateRequest;
use App\Http\Requests\VehicleUpdateRequest;
use App\Actions\SelectVehicle;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Gate;

class VehicleController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Vehicle::class);

        return view('vehicles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VehicleCreateRequest $request, SelectVehicle $selectVehicle)
    {
        Gate::authorize('create', Vehicle::class);

        $vehicle = auth()->user()->vehicles()->create($request->validated());

        $selectVehicle($vehicle);

        return redirect()->route('vehicles.setuptires.index', $vehicle)->with('status', 'Vehicle saved.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vehicle $vehicle)
    {
        Gate::authorize('update', $vehicle);

        return view('vehicles.edit', compact('vehicle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VehicleUpdateRequest $request, Vehicle $vehicle, SelectVehicle $selectVehicle)
    {
        Gate::authorize('update', $vehicle);

        $vehicle->update($request->validated());

        $selectVehicle($vehicle);

        return redirect()->route('vehicles.index')->with('status', 'Vehicle saved.');
    }
}
