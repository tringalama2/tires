<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleCreateRequest;
use App\Http\Requests\VehicleUpdateRequest;
use App\Livewire\Actions\SelectVehicle;
use App\Models\Vehicle;

class VehicleController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('vehicles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VehicleCreateRequest $request, SelectVehicle $selectVehicle)
    {
        $vehicle = Vehicle::create(array_merge($request->validated(), [
            'user_id' => auth()->id(),
        ]));

        $selectVehicle($vehicle);

        return redirect()->route('vehicles.index')->with('status', 'Vehicle saved.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vehicle $vehicle)
    {
        return view('vehicles.edit', compact('vehicle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VehicleUpdateRequest $request, Vehicle $vehicle, SelectVehicle $selectVehicle)
    {
        $vehicle->update($request->validated());

        $selectVehicle($vehicle);

        return redirect()->route('vehicles.index')->with('status', 'Vehicle saved.');
    }
}
